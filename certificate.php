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

// ==================================================
// Custom FPDF class with Circle() support
// ==================================================
class PDF extends FPDF
{
    function Circle($x, $y, $r, $style = 'D')
    {
        $op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $this->k, ($this->h - $y) * $this->k));
        $this->_Arc($x + $r, $y - $r * $MyArc, $x + $r * $MyArc, $y - $r, $x, $y - $r);
        $this->_Arc($x - $r * $MyArc, $y - $r, $x - $r, $y - $r * $MyArc, $x - $r, $y);
        $this->_Arc($x - $r, $y + $r * $MyArc, $x - $r * $MyArc, $y + $r, $x, $y + $r);
        $this->_Arc($x + $r * $MyArc, $y + $r, $x + $r, $y + $r * $MyArc, $x + $r, $y);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }
}

// ==================================================
// LinkedIn-style Edutech LMS Certificate (Single Page)
// ==================================================
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AddPage();

$primary = [59, 130, 246];  // Edutech blue
$accent = [255, 215, 0];   // Gold
$gray = [90, 90, 90];

// Background
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, 297, 210, 'F');

// Left blue stripe
$pdf->SetFillColor(...$primary);
$pdf->Rect(0, 0, 25, 210, 'F');

// Circular badge
$centerX = 12.5;
$centerY = 50;
$radius = 20;
$pdf->SetDrawColor(...$accent);
$pdf->SetLineWidth(2);
$pdf->Circle($centerX, $centerY, $radius);
$pdf->SetFillColor(255, 255, 255);
$pdf->Circle($centerX, $centerY, $radius - 1.5, 'F');

// Logo in circle
$logoPath = __DIR__ . '/images/edutech_logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $centerX - 10, $centerY - 10, 20, 20);
}

// --------------------------------------------------
// Main text area
// --------------------------------------------------
$pdf->SetXY(40, 30);
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(...$primary);
$pdf->Cell(0, 10, "Edutech LMS", 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetX(40);
$pdf->Cell(0, 10, "Certificate of Completion", 0, 1, 'L');

$pdf->SetFont('Arial', '', 14);
$pdf->SetX(40);
$pdf->Cell(0, 10, "Congratulations, " . $user['first_name'] . " " . $user['last_name'], 0, 1, 'L');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(...$primary);
$pdf->SetX(40);
$pdf->Cell(0, 10, $course['title'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX(40);
$pdf->Cell(0, 8, "Course completed on " . date('M d, Y'), 0, 1, 'L');

// --------------------------------------------------
// Footer with signature and Certificate ID (same page)
// --------------------------------------------------
$pdf->SetY(150);

// Signature
$signaturePath = __DIR__ . '/images/edutech_signature.png';
if (file_exists($signaturePath)) {
    $pdf->Image($signaturePath, 40, 151, 45);
}

$pdf->SetY(165);
$pdf->SetX(40);
$pdf->SetFont('Arial', 'I', 12);
$pdf->SetTextColor(...$gray);
$pdf->Cell(80, 6, "Team Edutech LMS", 0, 0, 'L');

// Certificate ID (bottom-right corner)
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(130, 130, 130);
$pdf->SetXY(210, 165);
$certificateId = "EDU-" . strtoupper(substr(md5($userId . $courseId . date('Ymd')), 0, 10));
$pdf->Cell(80, 6, "Certificate ID: $certificateId", 0, 0, 'R');

// Output
$pdf->Output('I', 'Certificate.pdf');
exit();
