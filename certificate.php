<?php
// certificate.php

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/fpdf186/fpdf.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (!isset($_GET['course_id'])) {
    die("No course specified.");
}

$courseId = (int) $_GET['course_id'];

// Check completion
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*) FROM lessons l
    JOIN course_modules cm ON l.module_id = cm.id
    WHERE cm.course_id = ?
");
$stmtTotal->execute([$courseId]);
$totalLessons = (int) $stmtTotal->fetchColumn();

$stmtCompleted = $pdo->prepare("
    SELECT COUNT(*) FROM user_progress up
    JOIN lessons l ON up.lesson_id = l.id
    JOIN course_modules cm ON l.module_id = cm.id
    WHERE up.user_id = ? AND cm.course_id = ? AND up.completed = 1
");
$stmtCompleted->execute([$userId, $courseId]);
$completedLessons = (int) $stmtCompleted->fetchColumn();

if ($totalLessons === 0 || $completedLessons < $totalLessons) {
    die("You have not completed this course yet.");
}

// Get user and course info
$userStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$courseStmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
$courseStmt->execute([$courseId]);
$course = $courseStmt->fetch();

if (!$user || !$course) {
    die("Invalid user or course.");
}

// --- Certificate Design ---
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Colors
$blue = [30, 144, 255];   // DodgerBlue
$yellow = [255, 215, 0];  // Gold
$darkBlue = [0, 51, 102];

// Background light gradient effect (optional, subtle)
for ($i = 0; $i < 210; $i += 3) {
    $pdf->SetFillColor(240 - $i / 4, 248 - $i / 4, 255);
    $pdf->Rect(0, $i, 297, 3, 'F');
}

// Outer border
$pdf->SetDrawColor(...$darkBlue);
$pdf->SetLineWidth(4);
$pdf->Rect(8, 8, 281, 194, 'D');

// Inner ribbon banner
$pdf->SetFillColor(...$blue);
$pdf->Rect(40, 30, 217, 20, 'F');

// Ribbon text
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(...$yellow);
$pdf->SetY(32);
$pdf->Cell(0, 10, "Certificate of Completion", 0, 1, 'C');

// Logo
$logoPath = __DIR__ . '/images/edutech_logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 20, 25, 30);
}

// Subtitle
$pdf->SetFont('Arial', '', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(15);
$pdf->Cell(0, 10, "This is to certify that", 0, 1, 'C');

// User name (single printing, bold blue)
$pdf->SetFont('Arial', 'B', 28);
$pdf->SetTextColor(...$blue);
$name = strtoupper($user['first_name'] . ' ' . $user['last_name']);
$pdf->Cell(0, 10, $name, 0, 1, 'C');

// Completed course text
$pdf->SetFont('Arial', '', 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);
$pdf->Cell(0, 10, "has successfully completed the course", 0, 1, 'C');

// Course title with blue highlight
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor(...$blue);
$pdf->Ln(3);
$pdf->Cell(0, 10, $course['title'], 0, 1, 'C');

// Decorative footer lines
$pdf->SetDrawColor(...$blue);
$pdf->SetLineWidth(1.5);
$pdf->Line(50, 160, 247, 160);
$pdf->Line(50, 162, 247, 162);

// Date and congratulations
$pdf->SetFont('Arial', 'I', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(-35);
$pdf->Cell(0, 10, "Date: " . date('d M Y'), 0, 1, 'C');

$pdf->Output('I', 'Certificate.pdf');
exit();
