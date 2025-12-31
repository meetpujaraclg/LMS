<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once 'includes/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
if (!isset($_POST['lesson_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lesson not found']);
    exit;
}

$lessonId = (int) $_POST['lesson_id'];

// --- Fetch quiz questions ---
$stmt = $pdo->prepare("SELECT id, correct_option FROM quizzes WHERE lesson_id = ?");
$stmt->execute([$lessonId]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($quizzes);
if ($total === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No quiz found']);
    exit;
}

// --- Evaluate answers ---
$correct = 0;
foreach ($quizzes as $q) {
    $qid = $q['id'];
    $answer = $_POST["q$qid"] ?? '';
    if (strtoupper($answer) === strtoupper($q['correct_option'])) {
        $correct++;
    }
}

$score = round(($correct / $total) * 100);
$passed = $score >= 60 ? 1 : 0;

// === Save quiz result ===
$stmt = $pdo->prepare("
    INSERT INTO user_quiz_results (user_id, lesson_id, score, passed)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE score = VALUES(score), passed = VALUES(passed)
");
$stmt->execute([$userId, $lessonId, $score, $passed]);

// === If passed, mark lesson complete ===
if ($passed) {
    // Mark lesson complete
    $stmt = $pdo->prepare("
        INSERT INTO user_progress (user_id, lesson_id, completed, watched_percent)
        VALUES (?, ?, 1, 100)
        ON DUPLICATE KEY UPDATE completed = 1, watched_percent = 100
    ");
    $stmt->execute([$userId, $lessonId]);

    // Get course id of this lesson
    $stmt = $pdo->prepare("
        SELECT cm.course_id 
        FROM lessons l 
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE l.id = ?
    ");
    $stmt->execute([$lessonId]);
    $courseId = (int) $stmt->fetchColumn();

    // === Recalculate course progress (count both lessons + quizzes) ===
    if ($courseId) {
        // Count total lessons + quizzes
        $totalStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) 
             FROM lessons l 
             JOIN course_modules cm ON l.module_id = cm.id 
             WHERE cm.course_id = :cid)
          + (SELECT COUNT(*) 
             FROM quizzes q 
             JOIN lessons l ON q.lesson_id = l.id 
             JOIN course_modules cm ON l.module_id = cm.id 
             WHERE cm.course_id = :cid)
        AS total_items
    ");
        $totalStmt->execute([':cid' => $courseId]);
        $totalItems = (int) $totalStmt->fetchColumn();

        // Count completed lessons (videos)
        $completedLessonsStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_progress up
        JOIN lessons l ON up.lesson_id = l.id
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE cm.course_id = :cid AND up.user_id = :uid AND up.completed = 1
    ");
        $completedLessonsStmt->execute([':cid' => $courseId, ':uid' => $userId]);
        $completedLessons = (int) $completedLessonsStmt->fetchColumn();

        // Count passed quizzes
        $passedQuizzesStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_quiz_results uq
        JOIN lessons l ON uq.lesson_id = l.id
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE cm.course_id = :cid AND uq.user_id = :uid AND uq.passed = 1
    ");
        $passedQuizzesStmt->execute([':cid' => $courseId, ':uid' => $userId]);
        $passedQuizzes = (int) $passedQuizzesStmt->fetchColumn();

        // Combine and calculate %
        $completedItems = $completedLessons + $passedQuizzes;
        $courseProgress = $totalItems > 0 ? round($completedItems * 100 / $totalItems) : 0;
    }


    echo json_encode([
        'status' => 'passed',
        'score' => $score,
        'correct' => $correct,
        'total' => $total,
        'course_progress' => $courseProgress
    ]);
    exit;
}

// --- If failed ---
echo json_encode([
    'status' => 'failed',
    'score' => $score,
    'correct' => $correct,
    'total' => $total
]);
