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

    
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="index.php">
                <i class="fas fa-graduation-cap me-2 text-warning"></i> EduTech
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Left Menu -->
                <ul class="navbar-nav me-auto align-items-md-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="request_instructor.php">Become an Instructor</a></li>
                </ul>

                <!-- Right Menu -->
                <ul class="navbar-nav align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // 🆕 Fetch profile picture from database
                        $userId = $_SESSION['user_id'];
                        $stmt = $pdo->prepare("SELECT profile_picture, first_name FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                        $profilePic = $userData['profile_picture'] ?? null;
                        $firstName = $userData['first_name'] ?? 'User';

                        // Default image path
                        $defaultPic = "uploads/profile_pictures/default.png";
                        ?>

                        <li class="nav-item dropdown d-flex align-items-center">
                            <a class="nav-link dropdown-toggle fw-semibold d-flex align-items-center" href="#"
                                id="navbarDropdown" role="button" data-bs-toggle="dropdown">

                                <!-- 🖼️ Profile Picture Display -->
                                <?php
                                $profilePath = !empty($profilePic) && file_exists($profilePic)
                                    ? htmlspecialchars($profilePic)
                                    : $defaultPic;
                                ?>
                                <img src="<?php echo $profilePath; ?>" alt="Profile Picture" class="rounded-circle me-2"
                                    style="width: 35px; height: 35px; object-fit: cover; border: 2px solid #007bff;">

                                <?php echo htmlspecialchars($firstName); ?>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i
                                            class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="dashboard.php"><i
                                            class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" style="" href="logout.php"><i
                                            class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-outline-primary" href="login.php">Log In</a></li>
                        <li class="nav-item"><a class="btn btn-primary" href="register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div style="height: 80px;"></div>