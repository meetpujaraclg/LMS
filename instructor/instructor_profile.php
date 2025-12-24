<?php
ob_start();
$pageTitle = "Instructor Profile";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

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

    if (!$instructor) {
        $instructor = [
            'first_name' => 'System',
            'last_name' => 'User',
            'email' => '',
            'bio' => '',
            'profile_picture' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    $socialLinks = [];
    if (isset($columns['social_links']) && $instructor['social_links']) {
        $dec = json_decode($instructor['social_links'], true);
        if (is_array($dec))
            $socialLinks = $dec;
    }
} catch (PDOException $e) {
    $instructor = [
        'first_name' => 'System',
        'last_name' => 'User',
        'email' => '',
        'bio' => '',
        'profile_picture' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

$profile_img_name = !empty($instructor['profile_picture']) && file_exists(__DIR__ . '/../uploads/instructors/' . $instructor['profile_picture'])
    ? $instructor['profile_picture']
    : 'default.png';
$profilePicture = '../uploads/instructors/' . htmlspecialchars($profile_img_name);

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<style>
    .profile-card-avatar {
        position: absolute;
        top: 120px;
        left: 50%;
        transform: translateX(-50%);
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.16);
        background: #fff;
        object-fit: cover;
    }

    .card-body.profile-content {
        padding-top: 80px;
        text-align: center;
    }

    .bg-primary-card-section {
        height: 180px;
        background: #1074fa;
        border-radius: 0;
    }

    .card {
        position: relative;
        overflow: visible;
        border-radius: 12px;
    }

    #btn-change {
        color: #FEBE10;
    }
</style>

<div class="container mt-4 mb-4">
    <?php if (isset($success)): ?>
        <div class="alert alert-success shadow-sm"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
    <?php endif; ?>

    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Profile</h1>
    </div>
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-lg border-0">
                <div class="bg-primary-card-section"></div>
                <img src="<?php echo $profilePicture; ?>" class="profile-card-avatar" alt="Profile Image">
                <div class="card-body profile-content">
                    <h2 class="card-title mb-1">
                        <?php echo htmlspecialchars($instructor['first_name'] . " " . $instructor['last_name']); ?>
                    </h2>
                    <a href="mailto:<?php echo htmlspecialchars($instructor['email']); ?>"
                        class="text-muted text-decoration-none">
                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($instructor['email']); ?>
                    </a>
                    <?php if (isset($columns['bio']) && !empty($instructor['bio'])): ?>
                        <div class="mt-3 fs-6"><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center">
                    <small class="text-muted">Member since:
                        <?php echo date('M j, Y', strtotime($instructor['created_at'])); ?></small>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-1 text-primary"></i> Update Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-12">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($instructor['first_name']); ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($instructor['last_name']); ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($instructor['email']); ?>" disabled>
                        </div>
                        <?php if (isset($columns['bio'])): ?>
                            <div class="col-md-12">
                                <label for="bio" class="form-label">Bio/Description</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"
                                    placeholder="Describe yourself..."><?php echo htmlspecialchars($instructor['bio'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($columns['profile_picture'])): ?>
                            <div class="col-md-12">
                                <label for="profile_picture" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture"
                                    accept="image/*">
                                <div class="form-text">JPG, PNG, GIF, WebP. Max 2MB.</div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-12 text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4 w-100 text-white"><i
                                    class="fas fa-save me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-key text-warning me-1"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning w-100 text-white"><i
                                class="fas fa-exchange-alt me-1"></i> Change Password</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="fas fa-award text-primary fa-2x me-2"></i>
                    <h5 class="mb-0">Profile Completeness</h5>
                </div>
                <div class="card-body">
                    <?php
                    $completeness = 0;
                    if (!empty($instructor['first_name']) && !empty($instructor['last_name']))
                        $completeness += 40;
                    if (!empty($instructor['email']))
                        $completeness += 30;
                    if (isset($columns['profile_picture']) && !empty($instructor['profile_picture']))
                        $completeness += 15;
                    if (isset($columns['bio']) && !empty($instructor['bio']))
                        $completeness += 15;
                    ?>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width:<?php echo $completeness; ?>%;"></div>
                    </div>
                    <small class="text-muted"><?php echo $completeness; ?>% complete</small>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="fas fa-info-circle text-info fa-2x me-2"></i>
                    <h5 class="mb-0">Account Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Joined:</strong>
                        <span
                            class="text-muted"><?php echo date('M j, Y', strtotime($instructor['created_at'])); ?></span>
                    </p>
                    <p><strong>Last Updated:</strong>
                        <span
                            class="text-muted"><?php echo date('M j, Y g:i A', strtotime($instructor['updated_at'])); ?></span>
                    </p>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-rocket text-success me-1"></i> Quick Actions</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="instructor_courses.php" class="btn btn-outline-secondary">
                        <i class="fas fa-book"></i> Manage Courses
                    </a>
                    <a href="instructor_students.php" class="btn btn-outline-info">
                        <i class="fas fa-users"></i> View Students
                    </a>
                    <a href="instructor_logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('profile_picture');
        if (input) {
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.querySelector('img.profile-card-avatar');
                        if (img) img.src = e.target.result;
                    }
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