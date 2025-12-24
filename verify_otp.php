<?php
$pageTitle = "Verify OTP";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOTP = trim($_POST['otp']);
    $storedOTP = $_SESSION['otp'] ?? null;
    $otpEmail = $_SESSION['otp_email'] ?? null;
    $tempUser = $_SESSION['temp_user'] ?? null;

    if (!$storedOTP || !$otpEmail || !$tempUser) {
        $error = "Session expired. Please register again.";
    } elseif ($enteredOTP != $storedOTP) {
        $error = "Invalid OTP! Please try again.";
    } else {
        global $pdo;

        $profilePicture = !empty($tempUser['profile_picture'])
            ? $tempUser['profile_picture']
            : 'uploads/profile_pictures/default.png';

        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, profile_picture)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $tempUser['first_name'],
            $tempUser['last_name'],
            $tempUser['email'],
            $tempUser['password'],
            $profilePicture
        ]);

        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['temp_user']);

        $success = "Account created successfully! Redirecting to login...";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2500);
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - EduTech</title>

    <!-- ✅ Bootstrap + FontAwesome + Google Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f4f8ff;
            font-family: 'Poppins', sans-serif;
        }

        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .verify-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
        }

        .verify-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            padding: 22px;
            text-align: center;
            color: #fff;
        }

        .verify-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .verify-body {
            padding: 35px;
            background-color: #f8fbff;
        }

        .verify-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 8px;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 0;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .verify-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .verify-btn:hover::before {
            left: 100%;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
            background: linear-gradient(135deg, #0056b3, #00408a);
        }

        .verify-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 86, 179, 0.4);
        }

        .resend-link {
            font-weight: 500;
            text-decoration: none;
            color: #007bff;
        }

        .resend-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
    </style>
</head>

<body>

    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <h4><i class="fas fa-key me-2"></i> Verify Your Email</h4>
            </div>
            <div class="verify-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center"><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success text-center"><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="otp" class="form-label fw-semibold">Enter OTP</label>
                        <input type="text" class="form-control form-control-lg" id="otp" name="otp"
                            placeholder="Enter 6-digit OTP" required>
                    </div>
                    <button type="submit" class="btn w-100 verify-btn">
                        <i class="fas fa-check-circle me-1"></i> Verify OTP
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">Didn't get the OTP?
                        <a href="register.php" class="resend-link">Register again</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>