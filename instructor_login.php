<?php
// instructor_login.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once __DIR__ . '/instructor/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['instructor_id'])) {
    header("Location: instructor/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            // Fetch instructor details
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, email, password, profile_status
                FROM instructors
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($instructor && password_verify($password, $instructor['password'])) {
                if ($instructor['profile_status'] === 'active') {
                    // ✅ Approved instructor — allow login
                    $_SESSION['instructor_id'] = $instructor['id'];
                    $_SESSION['instructor_email'] = $instructor['email'];
                    $_SESSION['instructor_name'] = $instructor['first_name'] . ' ' . $instructor['last_name'];
                    header("Location: instructor/index.php");
                    exit();
                } elseif ($instructor['profile_status'] === 'pending') {
                    $error = "Your account is under admin approval. Please wait until it’s verified.";
                } elseif ($instructor['profile_status'] === 'inactive') {
                    $error = "Your instructor account was rejected by admin. Please contact support or reapply.";
                } else {
                    $error = "Unknown account status. Please contact support.";
                }
            } else {
                $error = "Invalid email or password!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTech - Instructor Login</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
            font-family: "Poppins", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            background: linear-gradient(#007bff, #0056b3);
            color: #fff;
            text-align: center;
            padding: 25px 15px;
        }

        .card-header i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1.5px solid #dee2e6;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .ins-btn {
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

        .ins-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .ins-btn:hover::before {
            left: 100%;
        }

        .ins-btn:hover {
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
            background: linear-gradient(135deg, #0056b3, #00408a);
        }

        .ins-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 86, 179, 0.4);
        }

        .alert {
            border-radius: 10px;
        }

        .text-muted a {
            color: #4e73df;
            text-decoration: none;
            font-weight: 500;
        }

        .text-muted a:hover {
            color: #224abe;
        }

        .mt-extra {
            margin-top: 80px;
        }
    </style>
</head>

<body>

    <div class="container mt-extra">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3 class="mb-0 fw-bold">Instructor Login</h3>
                        <p class="mb-0">EduTech</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required placeholder="Enter your email">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Enter your password">
                            </div>

                            <button type="submit" name="login" class="btn ins-btn w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>

                        <div class="text-center text-muted mt-3">
                            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Main Site</a><br>
                            <a href="login.php"><i class="fas fa-user-graduate"></i> Student Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>