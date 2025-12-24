<?php
require_once 'admin_auth.php';
require_once __DIR__ . '/../config/database.php';

// Fetch current admin profile picture from DB
$adminId = $_SESSION['admin_id'];
$stmt = $admin_pdo->prepare("SELECT profile_picture, first_name, last_name FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$profilePath = __DIR__ . "/uploads/admins/" . $admin['profile_picture'];
$adminProfile = (!empty($admin['profile_picture']) && file_exists($profilePath))
    ? "uploads/admins/" . htmlspecialchars($admin['profile_picture'])
    : "uploads/admins/default_admin.png";

$adminName = htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']);
$admin_pageTitle = $admin_pageTitle ?? "Admin Panel";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $admin_pageTitle; ?></title>

    <!-- Bootstrap + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* 🌈 Navbar */
        .admin-navbar {
            background: linear-gradient(90deg, #1a252f, #243b55);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }

        /* Branding */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
            color: #fff !important;
        }

        /* 🌟 Profile Dropdown Styling */
        #adminDropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 10px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        #adminDropdown:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        /* 🧑‍💼 Profile Image */
        #adminDropdown img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Profile name text */
        #adminDropdown span {
            font-weight: 500;
            color: #fff;
            font-size: 0.95rem;
        }

        /* 🌙 Sidebar */
        .admin-sidebar {
            min-height: calc(100vh - 56px);
            background: #1f2c38;
            box-shadow: inset -2px 0 4px rgba(0, 0, 0, 0.2);
        }

        /* Sidebar links */
        .admin-sidebar .nav-link {
            color: #bdc3c7;
            padding: 14px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1rem;
        }

        /* Hover + active effects */
        .admin-sidebar .nav-link:hover {
            background: #2c3e50;
            color: #fff;
        }

        .admin-sidebar .nav-link.active {
            background: #34495e;
            color: #3498db;
            border-left-color: #3498db;
            font-weight: 600;
        }

        /* Dropdown menu */
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            animation: fadeIn 0.2s ease;
        }

        /* Animation for dropdown */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overall page background */
        body {
            background: #f5f6fa;
        }
    </style>
</head>

<body>
    <!-- 🌟 Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar py-2">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cogs me-1"></i> Admin Panel
            </a>

            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button"
                    id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $adminProfile; ?>" alt="Admin" class="me-2">
                    <span><?php echo $adminName; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                    <li>
                        <a class="dropdown-item" href="admin_profile.php">
                            <i class="fas fa-user-gear"></i> Edit Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="admin_logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 🌙 Sidebar + Main Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar p-0">
                <nav class="nav flex-column">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                        href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"
                        href="users.php">
                        <i class="fas fa-users"></i> Manage Students
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>"
                        href="courses.php">
                        <i class="fas fa-book"></i> Manage Courses
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_requests.php' ? 'active' : ''; ?>"
                        href="instructor_requests.php">
                        <i class="fas fa-envelope-open-text"></i> Instructor Requests
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructors.php' ? 'active' : ''; ?>"
                        href="instructors.php">
                        <i class="fas fa-chalkboard-teacher"></i> Manage Instructors
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'enrollments.php' ? 'active' : ''; ?>"
                        href="enrollments.php">
                        <i class="fas fa-user-graduate"></i> Enrollments
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"
                        href="categories.php">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
                        href="reports.php">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">