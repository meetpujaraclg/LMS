<?php
$pageTitle = "Register";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);

    // ✅ Correct upload handling
    $uploadDir = __DIR__ . "/uploads/profile_pictures/";  // absolute path (filesystem)
    $dbPathPrefix = "uploads/profile_pictures/";          // relative path (for DB/browser)

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $profilePicPath = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($_FILES['profile_picture']['name']));
        $targetFile = $uploadDir . $fileName;  // absolute path
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // ✅ Save only relative path for DB
                $profilePicPath = $dbPathPrefix . $fileName;
            } else {
                $error = "Failed to upload profile picture (permission issue).";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, and GIF formats are allowed!";
        }
    }

    // 🧠 Validation
    if (!$error) {
        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match!';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long!';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter!';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter!';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number!';
        } elseif (!preg_match('/[\W_]/', $password)) {
            $error = 'Password must contain at least one special character!';
        } else {
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'User with this email already exists!';
            } else {
                require_once 'includes/mailer_config.php';

                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_email'] = $email;
                $_SESSION['temp_user'] = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    // ✅ fallback to default if no file uploaded
                    'profile_picture' => $profilePicPath ?? $dbPathPrefix . "default.png"
                ];

                if (sendOTP($email, $otp)) {
                    redirect('verify_otp.php');
                } else {
                    $error = 'Failed to send OTP. Please check your email configuration.';
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card register-card shadow-lg border-0">
                <div class="card-header text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i> Create Your Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <!-- 🧠 Profile Picture Upload + Live Preview -->
                        <div class="mb-3 text-center">
                            <label for="profile_picture" class="form-label fw-semibold d-block">Profile Picture</label>

                            <!-- 🖼️ Preview Circle -->
                            <div class="preview-container mx-auto mb-2">
                                <img id="previewImage" src="uploads/profile_pictures/default.png" alt="Profile Preview">
                            </div>

                            <input type="file" class="form-control" id="profile_picture" name="profile_picture"
                                accept="image/*">
                            <small class="text-muted d-block mt-1">Supported formats: JPG, PNG, GIF (Max 2MB)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn register-btn w-100">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .register-card {
        border-radius: 12px;
        overflow: hidden;
    }

    .register-card .card-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        padding: 20px;
        font-weight: 600;
        font-size: 18px;
    }

    .register-btn {
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

    .register-btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
        transition: all 0.6s ease;
    }

    .register-btn:hover::before {
        left: 100%;
    }

    .register-btn:hover {
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
        background: linear-gradient(135deg, #0056b3, #00408a);
    }

    /* 🖼️ Profile Preview Styles */
    .preview-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #007bff;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }

    .preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        transition: transform 0.3s ease;
    }

    .preview-container:hover img {
        transform: scale(1.05);
    }
</style>

<!-- 💫 Live Preview Script -->
<script>
    document.getElementById('profile_picture').addEventListener('change', function (event) {
        const preview = document.getElementById('previewImage');
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = 'uploads/profile_pictures/default.png'; // fallback
        }
    });
</script>

<?php include 'includes/footer.php'; ?>