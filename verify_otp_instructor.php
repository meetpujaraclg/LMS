<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/mailer_config.php';

$pageTitle = "EduTech - Verify OTP (Instructor)";

if (!isset($_SESSION['temp_instructor'], $_SESSION['instructor_otp'])) {
    header("Location: request_instructor.php");
    exit;
}

$instructor = $_SESSION['temp_instructor'];
$storedOTP = $_SESSION['instructor_otp'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOTP = trim($_POST['otp']);

    if ($enteredOTP === '') {
        $error = "Please enter the OTP.";
    } elseif ($enteredOTP != $storedOTP) {
        $error = "Invalid OTP! Please try again.";
    } else {
        // Move temp files to final directory
        $TEMP_UPLOAD_DIR = __DIR__ . '/storage/temp_uploads/';
        $FINAL_UPLOAD_DIR = __DIR__ . '/uploads/instructors/';
        if (!is_dir($FINAL_UPLOAD_DIR))
            mkdir($FINAL_UPLOAD_DIR, 0755, true);

        $movedFiles = [];
        foreach (['profile_picture', 'qualification', 'id_proof', 'demo_video'] as $fileKey) {
            $tempFile = $TEMP_UPLOAD_DIR . $instructor[$fileKey];
            $finalFile = $FINAL_UPLOAD_DIR . $instructor[$fileKey];

            if (file_exists($tempFile)) {
                if (rename($tempFile, $finalFile)) {
                    $movedFiles[$fileKey] = $instructor[$fileKey];
                } else {
                    $error = "Failed to move file: " . htmlspecialchars($fileKey);
                    break; // Stop further processing if any move fails
                }
            } else {
                $error = ucfirst(str_replace('_', ' ', $fileKey)) . " file not found.";
                break;
            }
        }


        try {
            $stmt = $pdo->prepare("
                INSERT INTO instructors 
                (first_name, last_name, email, password, profile_picture, bio, id_proof, qualification, demo_video, experience, expertise_area, profile_status, verified, email_verified, created_at, updated_at)
                VALUES 
                (:first_name, :last_name, :email, :password, :profile_picture, :bio, :id_proof, :qualification, :demo_video, :experience, :expertise_area, 'pending', 0, 1, NOW(), NOW())
            ");
            $stmt->execute([
                ':first_name' => $instructor['first_name'],
                ':last_name' => $instructor['last_name'],
                ':email' => $instructor['email'],
                ':password' => $instructor['password'],
                ':profile_picture' => $movedFiles['profile_picture'] ?? null,
                ':bio' => $instructor['bio'],
                ':id_proof' => $movedFiles['id_proof'] ?? null,
                ':qualification' => $movedFiles['qualification'] ?? null,
                ':demo_video' => $movedFiles['demo_video'] ?? null, // ✅ new column
                ':experience' => $instructor['experience'],
                ':expertise_area' => $instructor['expertise_area']
            ]);

            unset($_SESSION['instructor_otp'], $_SESSION['temp_instructor']);
            $success = "🎉 Your instructor registration request has been submitted successfully!";
            header("refresh:3;url=index.php");

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .verify-card {
            border-radius: 10px;
            margin-top: 50px;
        }

        .verify-header {
            background: linear-gradient(135deg, #007bff, #0056d2);
        }

        .verify-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 8px;
            color: #fff !important;
            font-weight: 600;
            padding: 12px 0;
        }

        .verify-btn:hover {
            background: linear-gradient(135deg, #0056b3, #00408a);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0 verify-card">
                    <div class="card-header text-center py-3 verify-header text-white fw-bold">
                        <i class="fas fa-key me-2"></i> Verify Your Email
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <?php if (!$success): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="otp" class="form-label fw-semibold">Enter OTP</label>
                                    <input type="text" class="form-control" id="otp" name="otp"
                                        placeholder="Enter 6-digit OTP" required>
                                </div>
                                <button type="submit" class="btn w-100 verify-btn">
                                    <i class="fas fa-check-circle me-1"></i> Verify OTP
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <p>Didn’t get the OTP?
                                    <a href="request_instructor.php"
                                        class="text-primary fw-semibold text-decoration-none">Request again</a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>