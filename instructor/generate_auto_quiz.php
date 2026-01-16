<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/auto_quiz_error.log');
error_reporting(E_ALL);

try {
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    require_once '../includes/config.php';

    if (!isset($_POST['lesson_id'])) {
        throw new Exception('Lesson ID missing');
    }

    $lessonId = (int) $_POST['lesson_id'];

    // Fetch lesson info + content
    $stmt = $pdo->prepare("SELECT id, title, content FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        throw new Exception('Lesson not found');
    }

    $lessonTitle = $lesson['title'];
    $lessonContent = $lesson['content'] ?? '';

    // --- STEP 1: Fetch Wikipedia summary ---
    $wikiUrl = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($lessonTitle);
    $wikiResp = @file_get_contents($wikiUrl);
    $wikiData = json_decode($wikiResp, true);
    $wikiText = $wikiData['extract'] ?? $lessonTitle;

    // --- STEP 2: UNIVERSAL SMART MCQ GENERATOR ---
    function generate_universal_smart_mcqs($wikiText, $lessonTitle, $lessonContent = '')
    {
        $text = strtolower(strip_tags($wikiText . ' ' . ($lessonContent ?: $lessonTitle)));

        // Extract key terms (fast single pass)
        preg_match_all('/\b([a-z]{4,12})\b/', $text, $words);
        $allWords = array_count_assoc(array_filter($words[1], fn($w) => strlen($w) > 3));
        arsort($allWords);
        $keyTerms = array_slice(array_keys($allWords), 0, 5);

        // 🌍 UNIVERSAL TOPIC DETECTION
        $smartOptions = get_universal_smart_options($wikiText, $lessonTitle, $keyTerms);

        $questions = [];
        $letters = ['A', 'B', 'C', 'D'];

        // Q1: What best describes [Topic]?
        $q1_correct = ucfirst($lessonTitle) . " is used for " . $smartOptions['purpose'];
        $q1_options = [$q1_correct, $smartOptions['wrong1'], $smartOptions['wrong2'], $smartOptions['wrong3']];
        shuffle($q1_options);

        $questions[] = [
            'question' => "What best describes '{$lessonTitle}'?",
            'option_a' => $q1_options[0],
            'option_b' => $q1_options[1],
            'option_c' => $q1_options[2],
            'option_d' => $q1_options[3],
            'correct_option' => get_correct_letter($q1_correct, $q1_options)
        ];

        // Q2: Key feature/term
        $feature = $smartOptions['feature'];
        $q2_correct = "Used to " . $smartOptions['action'] . " " . $feature;
        $q2_options = [$q2_correct, "Create web pages", "Manage databases", "Build mobile apps"];
        shuffle($q2_options);

        $questions[] = [
            'question' => "In '{$lessonTitle}', '{$feature}' is:",
            'option_a' => $q2_options[0],
            'option_b' => $q2_options[1],
            'option_c' => $q2_options[2],
            'option_d' => $q2_options[3],
            'correct_option' => get_correct_letter($q2_correct, $q2_options)
        ];

        // Q3: Main benefit
        $q3_correct = $lessonTitle . " provides " . $smartOptions['benefit'];
        $q3_options = [$q3_correct, "Faster performance", "Better security", "Cost savings"];
        shuffle($q3_options);

        $questions[] = [
            'question' => "What main benefit does '{$lessonTitle}' provide?",
            'option_a' => $q3_options[0],
            'option_b' => $q3_options[1],
            'option_c' => $q3_options[2],
            'option_d' => $q3_options[3],
            'correct_option' => get_correct_letter($q3_correct, $q3_options)
        ];

        shuffle($questions);
        return $questions;
    }

    function get_universal_smart_options($wikiText, $lessonTitle, $keyTerms)
    {
        $text = strtolower(strip_tags($wikiText));

        // 🌍 100+ TOPIC PATTERNS
        $topicPatterns = [
            // Programming & Tech
            'programming' => ['program', 'code', 'variable', 'function', 'algorithm', 'loop', 'array', 'class', 'int', 'float', 'java', 'python'],
            'database' => ['database', 'sql', 'mysql', 'query', 'table', 'record', 'index', 'postgresql'],
            'webdev' => ['html', 'css', 'javascript', 'react', 'angular', 'website', 'frontend', 'backend', 'php'],
            'network' => ['network', 'protocol', 'http', 'tcp', 'ip', 'server', 'client', 'router'],
            'mobile' => ['android', 'ios', 'app', 'mobile', 'flutter', 'react native'],

            // Creative Arts
            'art' => ['art', 'drawing', 'painting', 'sketch', 'canvas', 'brush', 'color', 'illustration'],
            'music' => ['music', 'song', 'instrument', 'note', 'melody', 'rhythm', 'chord', 'guitar'],
            'photography' => ['photo', 'camera', 'lens', 'exposure', 'aperture', 'shutter', 'photograph'],
            'design' => ['design', 'ui', 'ux', 'graphic', 'logo', 'typography', 'layout', 'adobe'],

            // Science & Math
            'math' => ['math', 'calculate', 'equation', 'formula', 'geometry', 'algebra', 'trigonometry', 'statistics'],
            'physics' => ['physics', 'force', 'motion', 'energy', 'gravity', 'velocity', 'mass', 'quantum'],
            'chemistry' => ['chemistry', 'atom', 'molecule', 'element', 'reaction', 'compound', 'periodic'],
            'biology' => ['biology', 'cell', 'dna', 'gene', 'organism', 'evolution', 'photosynthesis'],

            // History & Social Studies
            'history' => ['history', 'century', 'empire', 'war', 'king', 'dynasty', 'revolution', 'timeline'],
            'geography' => ['geography', 'continent', 'country', 'river', 'mountain', 'ocean', 'capital', 'climate'],

            // Business & Economics
            'business' => ['business', 'market', 'company', 'profit', 'sales', 'strategy', 'management', 'entrepreneur'],
            'finance' => ['finance', 'money', 'stock', 'investment', 'bank', 'currency', 'budget', 'accounting'],

            // Sports & Fitness
            'sports' => ['sport', 'football', 'basketball', 'cricket', 'tennis', 'team', 'player', 'olympics'],
            'fitness' => ['fitness', 'exercise', 'workout', 'gym', 'muscle', 'cardio', 'yoga', 'protein'],

            // Food & Lifestyle
            'cooking' => ['cooking', 'recipe', 'ingredient', 'bake', 'fry', 'boil', 'chef', 'kitchen'],

            // Health & Medicine
            'medicine' => ['medicine', 'health', 'disease', 'treatment', 'doctor', 'patient', 'virus', 'vaccine'],

            // Engineering & Architecture
            'engineering' => ['engineering', 'mechanical', 'electrical', 'civil', 'construction', 'design'],
            'architecture' => ['architecture', 'building', 'structure', 'foundation', 'blueprint', 'design']
        ];

        // Detect topic
        foreach ($topicPatterns as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return get_topic_options($topic, $lessonTitle, $keyTerms);
                }
            }
        }

        // Generic fallback
        return [
            'purpose' => 'its core function',
            'action' => 'perform main task',
            'benefit' => 'achieve key objectives',
            'feature' => !empty($keyTerms) ? $keyTerms[0] : 'main concept',
            'wrong1' => 'Hardware device',
            'wrong2' => 'Network protocol',
            'wrong3' => 'Database system'
        ];
    }

    function get_topic_options($topic, $lessonTitle, $keyTerms)
    {
        $options = [
            // Programming
            'programming' => ['purpose' => 'storing/manipulating data', 'action' => 'store data', 'benefit' => 'efficient memory management', 'feature' => !empty($keyTerms) ? $keyTerms[0] : 'variable', 'wrong1' => 'hardware component', 'wrong2' => 'network protocol', 'wrong3' => 'database system'],
            'database' => ['purpose' => 'data storage/retrieval', 'action' => 'store records', 'benefit' => 'fast querying', 'feature' => 'table', 'wrong1' => 'art technique', 'wrong2' => 'coding method', 'wrong3' => 'web framework'],
            'webdev' => ['purpose' => 'building interactive websites', 'action' => 'render pages', 'benefit' => 'user-friendly interfaces', 'feature' => 'html', 'wrong1' => 'desktop software', 'wrong2' => 'mobile apps', 'wrong3' => 'database tool'],

            // Arts
            'art' => ['purpose' => 'visual artwork creation', 'action' => 'draw/sketch', 'benefit' => 'artistic expression', 'feature' => 'brush', 'wrong1' => 'coding technique', 'wrong2' => 'data storage', 'wrong3' => 'web framework'],
            'music' => ['purpose' => 'musical composition/performance', 'action' => 'play notes', 'benefit' => 'emotional expression', 'feature' => 'melody', 'wrong1' => 'programming code', 'wrong2' => 'math formula', 'wrong3' => 'business strategy'],

            // Science
            'math' => ['purpose' => 'mathematical computation', 'action' => 'solve equations', 'benefit' => 'precise calculations', 'feature' => 'formula', 'wrong1' => 'art technique', 'wrong2' => 'programming language', 'wrong3' => 'network tool'],
            'physics' => ['purpose' => 'physical phenomena study', 'action' => 'measure force', 'benefit' => 'motion prediction', 'feature' => 'velocity', 'wrong1' => 'art drawing', 'wrong2' => 'business strategy', 'wrong3' => 'cooking recipe'],

            // History/Geography
            'history' => ['purpose' => 'past events study', 'action' => 'record timeline', 'benefit' => 'learn from history', 'feature' => 'century', 'wrong1' => 'math formula', 'wrong2' => 'cooking method', 'wrong3' => 'sports technique'],
            'geography' => ['purpose' => 'earth features study', 'action' => 'map locations', 'benefit' => 'spatial understanding', 'feature' => 'continent', 'wrong1' => 'programming code', 'wrong2' => 'music note', 'wrong3' => 'cooking ingredient'],

            // Business/Sports/Food
            'business' => ['purpose' => 'commercial operations', 'action' => 'generate profit', 'benefit' => 'economic growth', 'feature' => 'market', 'wrong1' => 'art technique', 'wrong2' => 'math equation', 'wrong3' => 'sports play'],
            'sports' => ['purpose' => 'physical competition', 'action' => 'score points', 'benefit' => 'teamwork skills', 'feature' => 'player', 'wrong1' => 'database query', 'wrong2' => 'cooking recipe', 'wrong3' => 'programming code'],
            'cooking' => ['purpose' => 'food preparation', 'action' => 'cook ingredients', 'benefit' => 'delicious meals', 'feature' => 'recipe', 'wrong1' => 'network protocol', 'wrong2' => 'math formula', 'wrong3' => 'art painting']
        ];

        return $options[$topic] ?? [
            'purpose' => 'its core function',
            'action' => 'perform main task',
            'benefit' => 'achieve key objectives',
            'feature' => !empty($keyTerms) ? $keyTerms[0] : 'main concept',
            'wrong1' => 'Hardware device',
            'wrong2' => 'Network protocol',
            'wrong3' => 'Database system'
        ];
    }

    function array_count_assoc($array)
    {
        $result = [];
        foreach ($array as $item) {
            $result[$item] = ($result[$item] ?? 0) + 1;
        }
        return $result;
    }

    function get_correct_letter($correct, $options)
    {
        foreach ($options as $i => $option) {
            if ($option === $correct) {
                return ['A', 'B', 'C', 'D'][$i];
            }
        }
        return 'A';
    }

    // Generate UNIVERSAL MCQs
    $quiz = generate_universal_smart_mcqs($wikiText, $lessonTitle, $lessonContent);

    // Delete existing quizzes
    $pdo->prepare("DELETE FROM quizzes WHERE lesson_id = ?")->execute([$lessonId]);

    // Insert NEW MCQs
    $inserted = 0;
    foreach ($quiz as $q) {
        $stmt = $pdo->prepare("
            INSERT INTO quizzes (lesson_id, question, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$lessonId, $q['question'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_option']]);
        $inserted++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Quiz generated for '{$lessonTitle}'! ($inserted questions)",
        'quiz' => $quiz,
        'lesson_title' => $lessonTitle
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '⚠️ ' . $e->getMessage()
    ]);
}
