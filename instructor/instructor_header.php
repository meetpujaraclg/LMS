<?php
// instructor/instructor_header.php

error_reporting(0);
ini_set('display_errors', 0);

session_start();

$config_path = __DIR__ . '/../includes/config.php';

if (!file_exists($config_path)) {
    die("Configuration file not found at: " . $config_path);
}

require_once $config_path;

if (!isset($_SESSION['instructor_id'])) {
    header("Location: ../instructor_login.php");
    exit();
}

$instructorId = $_SESSION['instructor_id'];

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_picture FROM instructors WHERE id = ?");
    $stmt->execute([$instructorId]);
    $instructor = $stmt->fetch();
    if (!$instructor) {
        session_destroy();
        header("Location: ../instructor_login.php");
        exit();
    }
} catch (PDOException $e) {
    session_destroy();
    header("Location: ../instructor_login.php");
    exit();
}

function admin_sanitize($data)
{
    return htmlspecialchars(trim($data));
}

if (!isset($pageTitle)) {
    $pageTitle = "Instructor Panel";
}

$profile_img_name = !empty($instructor['profile_picture']) && file_exists(__DIR__ . '/../uploads/instructors/' . $instructor['profile_picture'])
    ? $instructor['profile_picture']
    : 'default.png';
$profile_img_path = '../uploads/instructors/' . htmlspecialchars($profile_img_name);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTech - <?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-gradient: linear-gradient(90deg, #0062E6, #33AEFF);
            --sidebar-bg: #ffffff;
            --main-bg: #f4f6fb;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
            padding-top: 56px;
        }

        /* 🔹 Navbar */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* 🧭 Sidebar */
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-sticky {
            position: relative;
            height: calc(100vh - 56px);
            padding-top: 1rem;
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 5px 12px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link i {
            width: 20px;
        }

        .nav-link:hover {
            background-color: #eaf3ff;
            color: #007bff;
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: 0 2px 6px rgba(0, 98, 230, 0.3);
        }

        .nav-link.active i {
            color: #fff;
        }

        .sidebar hr {
            margin: 1rem 0;
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* 🧱 Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 25px;
            background-color: var(--main-bg);
            min-height: calc(100vh - 56px);
        }

        /* 👤 Profile Avatar + Dropdown */
        .avatar-navbar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            border: 2px solid #fff;
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

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                top: 56px;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                margin-top: 0;
            }
        }

        #btn-edit:hover {
            color: #FEBE10;
        }

        #btn-logout:hover {
            color: red;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-chalkboard-teacher me-2"></i> Instructor Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button"
                            data-bs-toggle="dropdown">
                            <img src="<?php echo $profile_img_path; ?>" alt="Profile" class="avatar-navbar">
                            <span><?php echo htmlspecialchars($instructor['first_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="text-center p-2">
                                <img src="<?php echo $profile_img_path; ?>" alt="" class="rounded-circle" width="70"
                                    height="70" style="object-fit:cover;">
                                <div class="fw-semibold mt-2">
                                    <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($instructor['email']); ?></small>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" id="btn-edit" href="instructor_profile.php"><i
                                        class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                            <li><a class="dropdown-item" id="btn-logout" href="instructor_logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                                href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_courses.php' ? 'active' : ''; ?>"
                                href="instructor_courses.php">
                                <i class="fas fa-book"></i> My Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_students.php' ? 'active' : ''; ?>"
                                href="instructor_students.php">
                                <i class="fas fa-users"></i> Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_profile.php' ? 'active' : ''; ?>"
                                href="instructor_profile.php">
                                <i class="fas fa-user-edit"></i> Profile
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View Site
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">