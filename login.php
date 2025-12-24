<?php
$pageTitle = "Login";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // ✅ Check if account exists before trying to log in
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Account doesn't exist
        $error = 'Account does not exist!';
    } else {
        // Account exists → check password
        if (password_verify($password, $user['password'])) {
            // ✅ Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            redirect('dashboard.php');
        } else {
            // Password mismatch
            $error = 'Invalid password!';
        }
    }
}

include 'includes/header.php';
?>

<!-- 🔹 Inline page-specific CSS for login -->
<style>
    /* ====== Login Page Blue Theme ====== */
    .login-card {
        background-color: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .login-card-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        padding: 20px;
        text-align: center;
        color: #fff;
    }

    .login-card-header h4 {
        margin: 0;
        font-weight: 600;
    }

    .login-card-body {
        background-color: #f8fbff;
        padding: 30px;
    }

    .login-btn {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        border-radius: 10px;
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

    .login-btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
        transition: all 0.6s ease;
    }

    .login-btn:hover::before {
        left: 100%;
    }

    .login-btn:hover {
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
        background: linear-gradient(135deg, #0056b3, #00408a);
    }

    .login-btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 10px rgba(0, 86, 179, 0.4);
    }

    .login-register-text {
        font-size: 15px;
    }
</style>

<!-- 🔹 Page Structure -->
<div class="d-flex flex-column min-vh-50">
    <div class="container mt-5 mb-5 flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="col-md-6">
            <div class="login-card">
                <div class="login-card-header">
                    <h4><i class="fas fa-sign-in-alt me-2"></i>Login to Your Account</h4>
                </div>
                <div class="login-card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn login-btn w-100">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="login-register-text">
                            Don't have an account?
                            <a href="register.php" class="text-decoration-none">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>