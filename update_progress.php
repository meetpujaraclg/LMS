<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$lessonId = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;
$courseId = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
$percent = isset($_POST['percent']) ? (int) $_POST['percent'] : 0;

if (!$lessonId || !$courseId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// Clamp percent to 0–100
$percent = max(0, min(100, $percent));

// Threshold for video completion
$COMPLETE_THRESHOLD = 90;  // 90%

global $pdo;

// --- Fetch existing user progress ---
$oldStmt = $pdo->prepare("SELECT watched_percent, completed, quiz_completed FROM user_progress WHERE user_id=? AND lesson_id=?");
$oldStmt->execute([$userId, $lessonId]);
$oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC);

$oldPercent = $oldRow ? (int) $oldRow['watched_percent'] : 0;
$oldCompleted = $oldRow ? (int) $oldRow['completed'] : 0;
$quizCompleted = $oldRow ? (int) $oldRow['quiz_completed'] : 0;

// Never decrease watched_percent
if ($percent < $oldPercent)
    $percent = $oldPercent;

// Check if video is considered completed
$videoCompleted = ($percent >= $COMPLETE_THRESHOLD || $oldCompleted === 1) ? 1 : 0;

// Lesson completed only if video + quiz are completed
$lessonCompleted = ($videoCompleted && $quizCompleted) ? 1 : 0;

// --- Insert / update user_progress ---
$stmt = $pdo->prepare("
    INSERT INTO user_progress (user_id, lesson_id, completed, completed_at, watched_percent, quiz_completed)
    VALUES (?, ?, ?, IF(?=1, NOW(), NULL), ?, ?)
    ON DUPLICATE KEY UPDATE
        completed = GREATEST(completed, VALUES(completed)),
        completed_at = IF(VALUES(completed)=1, NOW(), completed_at),
        watched_percent = GREATEST(watched_percent, VALUES(watched_percent)),
        quiz_completed = GREATEST(quiz_completed, VALUES(quiz_completed))
");
$stmt->execute([$userId, $lessonId, $lessonCompleted, $lessonCompleted, $percent, $quizCompleted]);

// --- Recalculate course progress (fully completed lessons only) ---
$courseProgress = 0;
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
    WHERE up.user_id=? AND up.completed=1 AND cm.course_id=?
");
$completedLessonsStmt->execute([$userId, $courseId]);
$completedLessons = (int) $completedLessonsStmt->fetchColumn();

if ($totalLessons > 0) {
    $courseProgress = round($completedLessons * 100 / $totalLessons);
}

// --- JSON Response ---
echo json_encode([
    'status' => 'success',
    'lesson_percent' => $percent,
    'video_completed' => $videoCompleted,
    'quiz_completed' => $quizCompleted,
    'lesson_completed' => $lessonCompleted,
    'course_progress' => $courseProgress
]);
