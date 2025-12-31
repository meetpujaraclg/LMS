<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTech - <?php echo $pageTitle ?? 'Learning Management System'; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.png">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <script>
        // Apply saved theme instantly before render
        (function () {
            const savedTheme = localStorage.getItem("theme") || "light";
            document.documentElement.setAttribute("data-theme", savedTheme);
        })();
    </script>

    <style>
        :root {
            --nav-blue-start: #0f62ff;
            --nav-blue-end: #2d9eff;
            --nav-text-main: #0f172a;
            --nav-text-muted: #64748b;
        }

        body {
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Blue glass navbar */
        .navbar-edutech {
            background: linear-gradient(120deg, rgba(15, 98, 255, 0.98), rgba(45, 158, 255, 0.98));
            backdrop-filter: blur(14px);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.25);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .navbar-edutech .navbar-brand {
            color: #e5edff !important;
            letter-spacing: 0.03em;
        }

        .navbar-edutech .navbar-brand i {
            color: #ffb545 !important;
        }

        .navbar-edutech .nav-link {
            color: #e5edff !important;
            font-weight: 500;
            margin-right: .75rem;
            position: relative;
        }

        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            z-index: 1055 !important;
            animation: fadeInDown 0.25s ease;
        }

        .dropdown-item:hover {
            background-color: #eaf3ff;
            color: #007bff;
        }

        .logout {
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            z-index: 1055 !important;
            animation: fadeInDown 0.25s ease;
        }

        .logout:hover {
            background-color: #eaf3ff;
            color: #ff0000ff;
        }

        .navbar-edutech .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: #ffb545;
            transition: width .2s ease;
        }

        .navbar-edutech .nav-link:hover::after,
        .navbar-edutech .nav-link.active::after {
            width: 100%;
        }

        .navbar-edutech .btn-outline-primary {
            color: #e5edff;
            border-color: #e5edff;
            border-radius: 999px;
            font-weight: 500;
        }

        .navbar-edutech .btn-outline-primary:hover {
            background-color: #e5edff;
            color: var(--nav-blue-start);
        }

        .navbar-edutech .btn-primary {
            border-radius: 999px;
            border: none;
            background: linear-gradient(120deg, var(--nav-blue-start), #0043ce);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.35);
            font-weight: 600;
        }

        .navbar-edutech .dropdown-menu {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
        }

        .navbar-edutech .dropdown-item:active {
            background-color: rgba(37, 99, 235, 0.08);
        }

        .navbar-toggler {
            border-color: rgba(229, 231, 235, 0.7);
        }

        .navbar-toggler-icon {
            filter: invert(1);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-edutech fixed-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i> EduTech
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Left Menu -->
                <ul class="navbar-nav me-auto align-items-md-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pageTitle ?? '') === 'Home' ? 'active' : ''; ?>"
                            href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pageTitle ?? '') === 'Courses' ? 'active' : ''; ?>"
                            href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pageTitle ?? '') === 'Become Instructor' ? 'active' : ''; ?>"
                            href="request_instructor.php">Become an Instructor</a>
                    </li>
                </ul>

                <!-- Right Menu -->
                <ul class="navbar-nav align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $userId = $_SESSION['user_id'];
                        $stmt = $pdo->prepare("SELECT profile_picture, first_name FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                        $profilePic = $userData['profile_picture'] ?? null;
                        $firstName = $userData['first_name'] ?? 'User';

                        $defaultPic = "uploads/profile_pictures/default.png";
                        $profilePath = (!empty($profilePic) && file_exists($profilePic))
                            ? htmlspecialchars($profilePic)
                            : $defaultPic;
                        ?>
                        <li class="nav-item dropdown d-flex align-items-center">
                            <a class="nav-link dropdown-toggle fw-semibold d-flex align-items-center" href="#"
                                id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?php echo $profilePath; ?>" alt="Profile Picture" class="rounded-circle me-2"
                                    style="width: 35px; height: 35px; object-fit: cover; border: 2px solid #ffb545;">
                                <?php echo htmlspecialchars($firstName); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item profile-drop" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a>
                                </li>
                                <li><a class="dropdown-item" href="dashboard.php"><i
                                            class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item logout" href="logout.php"><i
                                            class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary" href="login.php">Log In</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div style="height: 80px;"></div>