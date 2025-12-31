<?php
ob_start();
$pageTitle = "Instructor Profile";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

// ✅ Entire backend logic unchanged
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $bio = $_POST['bio'] ?? null;

    $stmt = $pdo->prepare("SELECT profile_picture FROM instructors WHERE id = ?");
    $stmt->execute([$instructorId]);
    $instructorData = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldProfile = $instructorData['profile_picture'] ?? 'default.png';
    $profile_picture = $oldProfile;

    if (empty($first_name) || empty($last_name)) {
        $_SESSION['error'] = "First and last name are required.";
        header("Location: instructor_profile.php");
        exit();
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/instructors/';
        $fileTmp = $_FILES['profile_picture']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['profile_picture']['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileType, $allowed)) {
            $_SESSION['error'] = "Invalid image format. Use JPG, PNG, GIF, or WebP.";
            header("Location: instructor_profile.php");
            exit();
        }
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Image size must be under 2MB.";
            header("Location: instructor_profile.php");
            exit();
        }
        if (move_uploaded_file($fileTmp, $targetFile)) {
            $profile_picture = $fileName;
            if (!empty($oldProfile) && $oldProfile !== 'default.png' && file_exists($uploadDir . $oldProfile)) {
                unlink($uploadDir . $oldProfile);
            }
        } else {
            $_SESSION['error'] = "Error uploading file.";
            header("Location: instructor_profile.php");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE instructors SET first_name=?, last_name=?, bio=?, profile_picture=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$first_name, $last_name, $bio, $profile_picture, $instructorId]);
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: instructor_profile.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        header("Location: instructor_profile.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $_SESSION['error'] = "All password fields are required.";
        header("Location: instructor_profile.php");
        exit();
    }
    if ($new !== $confirm) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: instructor_profile.php");
        exit();
    }
    if (strlen($new) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
        header("Location: instructor_profile.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM instructors WHERE id=?");
        $stmt->execute([$instructorId]);
        $currentHash = $stmt->fetchColumn();

        if ($currentHash && password_verify($current, $currentHash)) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE instructors SET password=?, updated_at=NOW() WHERE id=?");
            $updateStmt->execute([$newHash, $instructorId]);
            $_SESSION['success'] = "Password changed successfully!";
        } else {
            $_SESSION['error'] = "Current password is incorrect.";
        }
        header("Location: instructor_profile.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error changing password: " . $e->getMessage();
        header("Location: instructor_profile.php");
        exit();
    }
}

try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM instructors");
    $stmt->execute();
    $allColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_flip($allColumns);
} catch (PDOException $e) {
    $columns = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
    $stmt->execute([$instructorId]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $instructor = [];
}

$profile_img_name = !empty($instructor['profile_picture']) && file_exists(__DIR__ . '/../uploads/instructors/' . $instructor['profile_picture'])
    ? $instructor['profile_picture']
    : 'default.png';
$profilePicture = '../uploads/instructors/' . htmlspecialchars($profile_img_name);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<style>
    /* 🌈 Modern Blue Profile Styling */
    .profile-header {
        background: linear-gradient(90deg, #0062E6, #33AEFF);
        color: #fff;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }

    .profile-header h2 {
        font-weight: 600;
    }

    .profile-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform .2s;
    }

    .profile-card:hover {
        transform: translateY(-3px);
    }

    .profile-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        position: absolute;
        top: 110px;
        left: 50%;
        transform: translateX(-50%);
        object-fit: cover;
    }

    .profile-banner {
        height: 180px;
        background: linear-gradient(90deg, #007bff, #33b5ff);
    }

    .card-body.profile-info {
        padding-top: 80px;
        text-align: center;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
    }

    .btn {
        border-radius: 8px;
    }

    .summary-card {
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    }

    .summary-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="container mt-4 mb-5">

    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="profile-header d-flex justify-content-between align-items-center flex-wrap">
        <h2><i class="fas fa-id-badge me-2"></i>My Profile</h2>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8 mb-4">
            <div class="card profile-card">
                <div class="profile-banner"></div>
                <img src="<?php echo $profilePicture; ?>" class="profile-avatar" alt="Profile">
                <div class="card-body profile-info">
                    <h3 class="fw-semibold mb-1">
                        <?php echo htmlspecialchars($instructor['first_name'] . " " . $instructor['last_name']); ?></h3>
                    <a href="mailto:<?php echo htmlspecialchars($instructor['email']); ?>"
                        class="text-muted text-decoration-none">
                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($instructor['email']); ?>
                    </a>
                    <?php if (!empty($instructor['bio'])): ?>
                        <p class="mt-3 text-secondary small"><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">Member since
                        <?php echo date('M j, Y', strtotime($instructor['created_at'])); ?></small>
                </div>
            </div>

            <!-- Update Profile -->
            <div class="card shadow-sm mt-4 border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="fas fa-user-edit me-2"></i>Update Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required
                                value="<?php echo htmlspecialchars($instructor['first_name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required
                                value="<?php echo htmlspecialchars($instructor['last_name']); ?>">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" disabled
                                value="<?php echo htmlspecialchars($instructor['email']); ?>">
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label">Bio/Description</label>
                            <textarea class="form-control" name="bio" rows="3"
                                placeholder="Tell something about yourself..."><?php echo htmlspecialchars($instructor['bio']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="profile_picture" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF, WebP (max 2MB)</small>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="update_profile"
                                class="btn btn-primary w-100 text-white shadow-sm">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-warning"><i class="fas fa-key me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning w-100 text-white shadow-sm">
                            <i class="fas fa-exchange-alt me-1"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4 mb-4">
            <!-- Profile Completeness -->
            <div class="card summary-card mb-4">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="fas fa-award text-primary fa-lg me-2"></i>
                    <h6 class="mb-0">Profile Completeness</h6>
                </div>
                <div class="card-body">
                    <?php
                    $completeness = 0;
                    if (!empty($instructor['first_name']) && !empty($instructor['last_name']))
                        $completeness += 40;
                    if (!empty($instructor['email']))
                        $completeness += 30;
                    if (!empty($instructor['profile_picture']))
                        $completeness += 15;
                    if (!empty($instructor['bio']))
                        $completeness += 15;
                    ?>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width:<?php echo $completeness; ?>%;"></div>
                    </div>
                    <small class="text-muted d-block mt-2"><?php echo $completeness; ?>% complete</small>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card summary-card mb-4">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="fas fa-info-circle text-info fa-lg me-2"></i>
                    <h6 class="mb-0">Account Info</h6>
                </div>
                <div class="card-body">
                    <p><strong>Joined:</strong> <span
                            class="text-muted"><?php echo date('M j, Y', strtotime($instructor['created_at'])); ?></span>
                    </p>
                    <p><strong>Last Updated:</strong> <span
                            class="text-muted"><?php echo date('M j, Y g:i A', strtotime($instructor['updated_at'])); ?></span>
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card summary-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 text-success"><i class="fas fa-rocket me-1"></i>Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="instructor_courses.php" class="btn btn-outline-primary"><i class="fas fa-book"></i> Manage
                        Courses</a>
                    <a href="instructor_students.php" class="btn btn-outline-info"><i class="fas fa-users"></i> View
                        Students</a>
                    <a href="instructor_logout.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i>
                        Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('profile_picture');
        if (input) {
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const img = document.querySelector('.profile-avatar');
                        if (img) img.src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
</script>

<?php
ob_end_flush();
require_once 'instructor_footer.php';
?>