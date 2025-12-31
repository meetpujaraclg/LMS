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

// clamp 0–100
$percent = max(0, min(100, $percent));

// threshold to consider lesson completed (same as MIN_WATCH_THRESHOLD * 100)
$COMPLETE_THRESHOLD = 90;  // 90%

global $pdo;

// 1) Read existing watched_percent for this user+lesson
$oldStmt = $pdo->prepare("
    SELECT watched_percent, completed 
    FROM user_progress 
    WHERE user_id = ? AND lesson_id = ?
");
$oldStmt->execute([$userId, $lessonId]);
$oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC);
$oldPercent = $oldRow ? (int) $oldRow['watched_percent'] : 0;
$oldCompleted = $oldRow ? (int) $oldRow['completed'] : 0;

// never go backwards for a lesson
if ($percent < $oldPercent) {
    $percent = $oldPercent;
}

// mark completed when >= threshold, and never un-complete
$newCompletedFlag = ($percent >= $COMPLETE_THRESHOLD || $oldCompleted === 1) ? 1 : 0;

// 2) Insert or update user_progress, keeping max values
$stmt = $pdo->prepare("
    INSERT INTO user_progress (user_id, lesson_id, completed, completed_at, watched_percent)
    VALUES (?, ?, ?, NOW(), ?)
    ON DUPLICATE KEY UPDATE 
        completed       = GREATEST(completed, VALUES(completed)),
        completed_at    = IF(VALUES(completed)=1, NOW(), completed_at),
        watched_percent = GREATEST(watched_percent, VALUES(watched_percent))
");
$stmt->execute([$userId, $lessonId, $newCompletedFlag, $percent]);

// 3) Recalculate course-level progress (completed lessons / total)
$totalStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM lessons l
    JOIN course_modules cm ON l.module_id = cm.id
    WHERE cm.course_id = ?
");
$totalStmt->execute([$courseId]);
$totalLessons = (int) $totalStmt->fetchColumn();

$completedStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM user_progress up
    JOIN lessons l ON up.lesson_id = l.id
    JOIN course_modules cm ON l.module_id = cm.id
    WHERE up.user_id = ?
      AND up.completed = 1
      AND cm.course_id = ?
");
$completedStmt->execute([$userId, $courseId]);
$completedLessons = (int) $completedStmt->fetchColumn();

$courseProgress = $totalLessons > 0 ? (int) round($completedLessons * 100 / $totalLessons) : 0;

// 4) Response used by JS to update UI
echo json_encode([
    'status' => 'success',
    'lesson_percent' => $percent,
    'completed' => $newCompletedFlag,
    'course_progress' => $courseProgress
]);
