<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

function sendError($message)
{
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    if (!isset($_POST['lesson_id'])) {
        sendError('Lesson ID missing');
    }

    $lessonId = (int) $_POST['lesson_id'];

    // Fetch lesson and materials
    $stmt = $pdo->prepare("
        SELECT l.title, GROUP_CONCAT(m.file_path SEPARATOR ',') AS materials
        FROM lessons l
        LEFT JOIN lesson_materials m ON l.id = m.lesson_id
        WHERE l.id = ?
        GROUP BY l.id
    ");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();
    if (!$lesson)
        sendError('Lesson not found');
    if (empty($lesson['materials']))
        sendError('No PDF materials found for this lesson');

    $title = trim(strip_tags($lesson['title']));
    $materials = explode(',', $lesson['materials']);
    $textContent = '';

    // Extract text from PDFs
    foreach ($materials as $filePath) {
        $fullPath = "../uploads/" . trim($filePath);
        if (!file_exists($fullPath))
            continue;

        // Try pdftotext first
        $output = @shell_exec("pdftotext \"$fullPath\" -");
        if ($output && trim($output) !== '') {
            $textContent .= $output . "\n";
        } else {
            // fallback OCR using tesseract
            $ocrFile = tempnam(sys_get_temp_dir(), 'ocr_') . '.txt';
            @shell_exec("tesseract \"$fullPath\" \"$ocrFile\" 2>/dev/null");
            if (file_exists($ocrFile)) {
                $textContent .= file_get_contents($ocrFile) . "\n";
                @unlink($ocrFile);
            }
        }
    }

    if (empty(trim($textContent))) {
        sendError('No text could be extracted. PDF might be scanned, encrypted, or empty.');
    }

    // Preprocess content
    $textContent = preg_replace('/\s+/', ' ', strip_tags($textContent));
    $sentences = preg_split('/(?<=[.?!])\s+/', $textContent);

    // Skip meaningless sentences
    $skipPatterns = [
        '/^start learning/i',
        '/^this lesson/i',
        '/^the purpose/i',
        '/^in this chapter/i',
        '/^you will learn/i',
        '/^let us/i',
        '/^introduction/i'
    ];

    $filteredSentences = [];
    foreach ($sentences as $s) {
        $s = trim($s);
        if (strlen($s) < 5)
            continue;
        $skip = false;
        foreach ($skipPatterns as $p) {
            if (preg_match($p, $s)) {
                $skip = true;
                break;
            }
        }
        if (!$skip)
            $filteredSentences[] = $s;
    }

    // Helper functions
    function sanitizeLabel($text)
    {
        $text = trim($text);
        $text = preg_replace('/[\[\]\(\)<>"]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    function wrapText($text, $length = 30)
    {
        return wordwrap($text, $length, "\\n", true);
    }

    // Extract relations
    $relations = [];
    foreach ($filteredSentences as $s) {
        $s = strtolower($s);

        if (strpos($s, ' is ') !== false) {
            [$l, $r] = explode(' is ', $s, 2);
            $relations[] = [$l, 'is', $r];
        } elseif (strpos($s, ' are ') !== false) {
            [$l, $r] = explode(' are ', $s, 2);
            $relations[] = [$l, 'are', $r];
        } elseif (strpos($s, ' such as ') !== false) {
            [$l, $r] = explode(' such as ', $s, 2);
            $relations[] = [$l, 'has examples', $r];
        } elseif (strpos($s, ' stores ') !== false) {
            [$l, $r] = explode(' stores ', $s, 2);
            $relations[] = [$l, 'stores', $r];
        } elseif (strpos($s, ' called ') !== false) {
            [$l, $r] = explode(' called ', $s, 2);
            $relations[] = [$l, 'called', $r];
        } elseif (strpos($s, ' used to ') !== false) {
            [$l, $r] = explode(' used to ', $s, 2);
            $relations[] = [$l, 'used to', $r];
        } elseif (strpos($s, ' contain ') !== false) {
            [$l, $r] = explode(' contain ', $s, 2);
            $relations[] = [$l, 'contains', $r];
        }
    }


    // Start Mermaid diagram
    $diagram = "graph LR\n";
    $titleNodeId = "A0";
    $diagram .= "    {$titleNodeId}(\"" . wrapText(sanitizeLabel($title), 35) . "\"):::main\n";

    $count = 1;
    $nodesMap = [];

    $relationColors = [
        'is' => 'isClass',
        'are' => 'isClass',
        'has examples' => 'exampleClass',
        'contains' => 'containsClass',
        'stores' => 'containsClass',
        'used to' => 'usedClass',
        'called' => 'usedClass'
    ];

    foreach ($relations as [$from, $rel, $to]) {
        $from = ucfirst(trim(strtok($from, '.')));
        $to = ucfirst(trim(strtok($to, '.')));
        $rel = strtolower(trim($rel));

        if (strlen($from) < 3 || strlen($to) < 3)
            continue;

        $fromLabel = wrapText(sanitizeLabel($from), 30);
        $toLabel = wrapText(sanitizeLabel($to), 30);

        if (!isset($nodesMap[$fromLabel])) {
            $nodesMap[$fromLabel] = "N" . $count++;
            $class = match ($rel) {
                'has examples' => 'exampleNode',
                'used to', 'called', 'contains', 'stores' => 'functionNode',
                default => 'conceptNode',
            };
            $diagram .= "    {$nodesMap[$fromLabel]}(\"{$fromLabel}\"):::{$class}\n";
        }
        if (!isset($nodesMap[$toLabel])) {
            $nodesMap[$toLabel] = "N" . $count++;
            $class = match ($rel) {
                'has examples' => 'exampleNode',
                'used to', 'called', 'contains', 'stores' => 'functionNode',
                default => 'conceptNode',
            };
            $diagram .= "    {$nodesMap[$toLabel]}(\"{$toLabel}\"):::{$class}\n";
        }

        $classEdge = $relationColors[$rel] ?? 'relation';
        $diagram .= "    {$nodesMap[$fromLabel]} -->|" . wrapText(ucfirst($rel), 20) . "| {$nodesMap[$toLabel]}:::{$classEdge}\n";
    }

    // Mermaid styles
    $diagram .= <<<EOD

classDef main fill:#4CAF50,stroke:#2E7D32,stroke-width:3px,color:white,font-size:20px,padding:20px;
classDef conceptNode fill:#2196F3,stroke:#1976D2,stroke-width:2px,color:white,font-size:16px,padding:15px;
classDef exampleNode fill:#FF9800,stroke:#E65100,stroke-width:2px,color:white,font-size:16px,padding:15px;
classDef functionNode fill:#9C27B0,stroke:#6A1B9A,stroke-width:2px,color:white,font-size:16px,padding:15px;

classDef relation fill:#888888,stroke:#555555,stroke-width:1px,color:#fff,font-size:14px,padding:10px;
classDef isClass fill:#2196F3,stroke:#1976D2,color:white,font-size:14px;
classDef exampleClass fill:#FF9800,stroke:#E65100,color:white,font-size:14px;
classDef containsClass fill:#9C27B0,stroke:#6A1B9A,color:white,font-size:14px;
classDef usedClass fill:#4CAF50,stroke:#2E7D32,color:white,font-size:14px;

EOD;

    if (empty($relations)) {
        $diagram .= "    {$titleNodeId} -->|No relations detected| B0(\"Add more descriptive material\")\n";
    }

    echo json_encode([
        'status' => 'success',
        'diagram' => $diagram
    ]);

} catch (Exception $e) {
    sendError('Unexpected error: ' . $e->getMessage());
}
