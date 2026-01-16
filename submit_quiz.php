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

// --- Save quiz result ---
$stmt = $pdo->prepare("
    INSERT INTO user_quiz_results (user_id, lesson_id, score, passed)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE score = VALUES(score), passed = VALUES(passed)
");
$stmt->execute([$userId, $lessonId, $score, $passed]);

// --- Fetch existing video progress ---
$stmt = $pdo->prepare("SELECT completed AS video_completed, watched_percent, quiz_completed FROM user_progress WHERE user_id=? AND lesson_id=?");
$stmt->execute([$userId, $lessonId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$videoCompleted = $row ? (int) $row['video_completed'] : 0;
$watchedPercent = $row ? (int) $row['watched_percent'] : 0;

// --- Determine lesson completion (video + quiz) ---
$quizCompleted = $passed ? 1 : ($row ? (int) $row['quiz_completed'] : 0);
$lessonCompleted = ($videoCompleted && $quizCompleted) ? 1 : 0;

// --- Update user_progress ---
$stmt = $pdo->prepare("
    INSERT INTO user_progress (user_id, lesson_id, completed, completed_at, watched_percent, quiz_completed)
    VALUES (?, ?, ?, IF(?=1, NOW(), NULL), ?, ?)
    ON DUPLICATE KEY UPDATE
        completed = GREATEST(completed, VALUES(completed)),
        completed_at = IF(VALUES(completed)=1, NOW(), completed_at),
        watched_percent = GREATEST(watched_percent, VALUES(watched_percent)),
        quiz_completed = GREATEST(quiz_completed, VALUES(quiz_completed))
");
$stmt->execute([$userId, $lessonId, $lessonCompleted, $lessonCompleted, $watchedPercent, $quizCompleted]);

// --- Get course id ---
$stmt = $pdo->prepare("
    SELECT cm.course_id 
    FROM lessons l 
    JOIN course_modules cm ON l.module_id = cm.id
    WHERE l.id = ?
");
$stmt->execute([$lessonId]);
$courseId = (int) $stmt->fetchColumn();

// --- Recalculate course progress (only fully completed lessons) ---
$courseProgress = 0;
if ($courseId) {
    $totalLessonsStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lessons l
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE cm.course_id = ?
    ");
    $totalLessonsStmt->execute([$courseId]);
    $totalLessons = (int) $totalLessonsStmt->fetchColumn();

    $completedLessonsStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_progress up
        JOIN lessons l ON up.lesson_id = l.id
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE up.user_id = ? AND up.completed = 1 AND cm.course_id = ?
    ");
    $completedLessonsStmt->execute([$userId, $courseId]);
    $completedLessons = (int) $completedLessonsStmt->fetchColumn();

    if ($totalLessons > 0) {
        $courseProgress = round($completedLessons * 100 / $totalLessons);
    }
}

// --- JSON Response ---
echo json_encode([
    'status' => $passed ? 'passed' : 'failed',
    'score' => $score,
    'correct' => $correct,
    'total' => $total,
    'video_completed' => $videoCompleted,
    'quiz_completed' => $quizCompleted,
    'lesson_completed' => $lessonCompleted,
    'course_progress' => $courseProgress
]);
