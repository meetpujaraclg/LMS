<?php
session_start();

// Database configuration
define('ADMIN_DB_HOST', 'localhost');
define('ADMIN_DB_USER', 'root');
define('ADMIN_DB_PASS', '');
define('ADMIN_DB_NAME', 'edtech_lms');

try {
    $admin_pdo = new PDO("mysql:host=" . ADMIN_DB_HOST . ";dbname=" . ADMIN_DB_NAME, ADMIN_DB_USER, ADMIN_DB_PASS);
    $admin_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Admin Database Connection failed: " . $e->getMessage());
}

// Check if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // ✅ Fetch admin details from the `admins` table
    $stmt = $admin_pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // ✅ Store admin session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_first_name'] = $admin['first_name'];
        $_SESSION['admin_last_name'] = $admin['last_name'];
        $_SESSION['admin_profile_picture'] = $admin['profile_picture'];

        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EduTech</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(#007bff, #0056b3);
            color: #fff;
            padding: 35px 20px;
            text-align: center;
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .login-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 2px solid #e3e6f0;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-login {
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

        .btn-login::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
            background: linear-gradient(135deg, #0056b3, #00408a);
        }

        .btn-login:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 86, 179, 0.4);
        }

        .login-links {
            text-align: center;
            margin-top: 20px;
        }

        .login-links a {
            display: block;
            color: #224abe;
            text-decoration: none;
            margin-top: 5px;
            transition: color 0.3s ease;
        }

        .login-links a:hover {
            color: #4e73df;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h3 class="fw-bold mb-0">Admin Login</h3>
            <p class="mb-0">EduTech</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email"
                        required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-links">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Main Site</a>
                <a href="../login.php"><i class="fas fa-user-graduate"></i> Student Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>