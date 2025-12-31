<?php
require_once '../includes/config.php';
require_once 'instructor_header.php';

if (!isset($_GET['lesson_id'])) {
    die("Lesson ID missing!");
}

$lessonId = (int) $_GET['lesson_id'];

// Fetch lesson content
$stmt = $pdo->prepare("SELECT title, content FROM lessons WHERE id = ?");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    die("Lesson not found.");
}

// --- Extract keywords from title + content ---
$content = strtolower($lesson['title'] . ' ' . $lesson['content']);
$words = preg_split('/[\s,.\-:;()]+/', $content);
$keywords = array_unique(array_filter($words, fn($w) => strlen($w) > 5 && !is_numeric($w)));

// Take first 5 unique words as base keywords
$keywords = array_slice($keywords, 0, 5);

// Delete any old quizzes (if regenerating)
$pdo->prepare("DELETE FROM quizzes WHERE lesson_id = ?")->execute([$lessonId]);

if (empty($keywords)) {
    echo "<div class='container mt-5 text-center'>
            <h3 class='text-danger'>❌ Not enough content to generate quiz.</h3>
            <p>Add more text or keywords to this lesson.</p>
            <a href='instructor_lessons.php?module_id=" . $_GET['module_id'] . "' class='btn btn-primary mt-3'>Back</a>
          </div>";
    exit();
}

// Generate questions
foreach ($keywords as $word) {
    $question = ucfirst("What is $word?");
    $options = [
        ucfirst("$word is a key term related to this lesson."),
        "It is a random concept.",
        "It’s unrelated to this topic.",
        "None of the above."
    ];

    shuffle($options);
    $correctIndex = array_search(ucfirst("$word is a key term related to this lesson."), $options);
    $correctLetter = chr(65 + $correctIndex);

    $stmt = $pdo->prepare("INSERT INTO quizzes 
        (lesson_id, question, option_a, option_b, option_c, option_d, correct_option)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$lessonId, $question, $options[0], $options[1], $options[2], $options[3], $correctLetter]);
}

echo "<div class='container mt-5 text-center'>
        <h2 class='text-success mb-3'>✅ Quiz Generated Successfully!</h2>
        <p>Questions created automatically for <strong>" . htmlspecialchars($lesson['title']) . "</strong></p>
        <a href='instructor_lessons.php?module_id=" . htmlspecialchars($_GET['module_id']) . "' class='btn btn-primary mt-3'>
            <i class='fas fa-arrow-left'></i> Back to Lessons
        </a>
      </div>";
?>