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

// Profile image path resolution
$profile_img_name = !empty($instructor['profile_picture']) && file_exists(__DIR__ . '/../uploads/instructors/' . $instructor['profile_picture'])
    ? $instructor['profile_picture']
    : 'default.png';
// Actual path used in HTML
$profile_img_path = '../uploads/instructors/' . htmlspecialchars($profile_img_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTech - <?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 250px;
            z-index: 100;
            padding: 20px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
            background-color: #f5f7fb;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                width: 100%;
                position: relative;
                top: 0;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                margin-top: 0;
            }
        }
        .navbar-brand {
            padding: 0.5rem 1rem;
        }
        .nav-link {
            padding: 0.75rem 1rem;
            color: #333;
            border-radius: 0.375rem;
            margin: 2px 10px;
        }
        .nav-link:hover,
        .nav-link.active {
            background-color: #e9ecef;
            color: #007bff;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
        }
        .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
        .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
        .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
        .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
        .text-xs { font-size: 0.7rem; }
        .text-gray-800 { color: #5a5c69 !important; }
        .shadow { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important; }
        body {
            background-color: #f5f7fb;
            padding-top: 56px;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: 0.5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .avatar-navbar {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 8px;
        }
        .profile-tooltip-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 8px auto;
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
<nav class="navbar navbar-dark bg-dark fixed-top navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-chalkboard-teacher"></i> Instructor Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                
                <!-- Profile image in dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <img src="<?php echo $profile_img_path; ?>"
                             alt="Profile"
                             class="avatar-navbar">
                        <span><?php echo htmlspecialchars($instructor['first_name']); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" id="btn-edit" href="instructor_profile.php"><i class="fas fa-user-edit"></i>
                                Edit Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" id="btn-logout" href="instructor_logout.php"><i
                                    class="fas fa-sign-out-alt"></i> Logout</a></li>
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
