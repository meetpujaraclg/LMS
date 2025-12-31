<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$courseId = $_POST['course_id'] ?? null;

if (!$courseId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid course']);
    exit;
}

// Check if already favorited
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND course_id = ?");
$stmt->execute([$userId, $courseId]);
$fav = $stmt->fetch();

if ($fav) {
    // Remove favorite
    $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND course_id = ?");
    $del->execute([$userId, $courseId]);
    echo json_encode(['status' => 'removed']);
} else {
    // Add favorite
    $add = $pdo->prepare("INSERT INTO favorites (user_id, course_id) VALUES (?, ?)");
    $add->execute([$userId, $courseId]);
    echo json_encode(['status' => 'added']);
}