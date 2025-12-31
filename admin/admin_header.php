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
        :root {
            --primary-blue: #0d6efd;
            --blue-gradient: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #084298 100%);
            --sidebar-bg: linear-gradient(180deg, #f8fbff 0%, #e8f2ff 100%);
            --sidebar-hover: rgba(13, 110, 253, 0.08);
            --sidebar-active: var(--blue-gradient);
            --navbar-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --glass-bg: rgba(255, 255, 255, 0.92);
            --shadow-blue: 0 10px 30px rgba(13, 110, 253, 0.2);
            --shadow-hover: 0 20px 40px rgba(13, 110, 253, 0.3);
        }

        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* 🌟 Enhanced Navbar */
        .admin-navbar {
            background: var(--navbar-gradient) !important;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-blue);
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.75rem 1.75rem !important;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* 🧑‍💼 Premium Profile Dropdown */
        #adminDropdown {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        #adminDropdown:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.4);
        }

        #adminDropdown img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        #adminDropdown span {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        /* 🌈 Modern Sidebar */
        .admin-sidebar {
            min-height: calc(100vh - 80px);
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(13, 110, 253, 0.1);
            box-shadow: var(--shadow-blue);
            position: sticky;
            top: 80px;
        }

        .admin-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(13, 110, 253, 0.3), transparent);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            background: rgba(13, 110, 253, 0.03);
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
            margin-bottom: 1.5rem;
        }

        .sidebar-avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: var(--blue-gradient);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
        }

        /* Enhanced Sidebar Links */
        .admin-sidebar .nav-link {
            color: #64748b;
            padding: 1rem 1.5rem;
            border-radius: 0 20px 20px 0;
            margin: 0.25rem 1rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .admin-sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%) scaleX(0);
            height: 2px;
            width: 4px;
            background: var(--primary-blue);
            transition: transform 0.3s ease;
            border-radius: 0 2px 2px 0;
        }

        .admin-sidebar .nav-link i {
            width: 22px;
            margin-right: 14px;
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .admin-sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--primary-blue);
            transform: translateX(8px);
            box-shadow: inset 4px 0 0 var(--primary-blue);
        }

        .admin-sidebar .nav-link:hover i {
            transform: scale(1.15);
        }

        .admin-sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: white;
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
            transform: translateX(5px);
        }

        .admin-sidebar .nav-link.active::before {
            transform: translateY(-50%) scaleX(1);
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            padding: 1rem 0;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            margin-top: 0.75rem;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            margin: 0 0.25rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-blue);
            transform: translateX(4px);
        }

        .dropdown-item i {
            width: 18px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            padding: 2.5rem;
            min-height: 100vh;
        }

        .page-title {
            background: rgba(13, 110, 253, 0.05);
            border: 1px solid rgba(13, 110, 253, 0.1);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- 🌟 Premium Blue Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Edutech LMS Admin
            </a>

            <div class="dropdown ms-auto">
                <a class="btn d-flex align-items-center text-white" id="adminDropdown" href="#" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $adminProfile; ?>" alt="Admin" class="me-3">
                    <span><?php echo substr($adminName, 0, 20); ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                    <li>
                        <a class="dropdown-item" href="admin_profile.php">
                            <i class="fas fa-user-gear text-primary"></i>
                            Edit Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger fw-semibold" href="admin_logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 🌈 Blue Themed Layout -->
    <div class="container-fluid mt-5 pt-4">
        <div class="row">
            <!-- Enhanced Sidebar -->
            <div class="col-lg-2 col-md-3 admin-sidebar px-0">
                <div class="sidebar-header">
                    <div class="sidebar-avatar mb-3">
                        <i class="fas fa-shield-alt text-white fa-lg"></i>
                    </div>
                    <h6 class="fw-bold text-primary mb-0"><?php echo substr($adminName, 0, 15); ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>

                <nav class="nav flex-column px-2">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                        href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"
                        href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Students</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>"
                        href="courses.php">
                        <i class="fas fa-book"></i>
                        <span>Manage Courses</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_requests.php' ? 'active' : ''; ?>"
                        href="instructor_requests.php">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Instructor Requests</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'instructors.php' ? 'active' : ''; ?>"
                        href="instructors.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Manage Instructors</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'enrollments.php' ? 'active' : ''; ?>"
                        href="enrollments.php">
                        <i class="fas fa-user-graduate"></i>
                        <span>Enrollments</span>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"
                        href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </nav>
            </div>

            <!-- Main Content Area -->
            <main class="col-lg-10 col-md-9 main-content">
                <?php if (isset($admin_pageTitle)): ?>
                    <div class="page-title">
                        <h1 class="h2 fw-bold text-primary mb-0">
                            <?php echo $admin_pageTitle; ?>
                        </h1>
                    </div>
                <?php endif; ?>