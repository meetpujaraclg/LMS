<?php
$pageTitle = "Profile";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

include 'includes/header.php';

global $pdo;
$userId = $_SESSION['user_id'];

// Fetch user data
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// =============================
// ✅ Handle profile update
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $bio = sanitize($_POST['bio']);

    // Keep old profile picture if unchanged
    $profilePicName = $user['profile_picture'];

    // ✅ Handle new profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $fileTmp = $_FILES['profile_picture']['tmp_name'];
        $fileExt = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
        $targetDir = "uploads/profile_pictures/";
        $targetPath = $targetDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileType = mime_content_type($fileTmp);

        if (in_array($fileType, $allowedTypes)) {
            if (!is_dir($targetDir))
                mkdir($targetDir, 0777, true);

            if (move_uploaded_file($fileTmp, $targetPath)) {
                // Delete old image if exists
                if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
                $profilePicName = $targetPath;
            } else {
                $error = "Error uploading profile picture. Try again.";
            }
        } else {
            $error = "Invalid file format! Only JPG, PNG, and WEBP allowed.";
        }
    }

    // ✅ Update user record
    $updateStmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, profile_picture = ? WHERE id = ?");
    if ($updateStmt->execute([$firstName, $lastName, $bio, $profilePicName, $userId])) {
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['profile_picture'] = $profilePicName;

        $success = "Profile updated successfully!";
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Failed to update profile.";
    }
}

// =============================
// ✅ Handle password change
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($passwordStmt->execute([$hashedPassword, $userId])) {
                $passwordSuccess = "Password changed successfully!";
            } else {
                $passwordError = "Failed to change password.";
            }
        } else {
            $passwordError = "New passwords do not match!";
        }
    } else {
        $passwordError = "Current password is incorrect!";
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <!-- =============================
             LEFT: Profile Summary
        ============================== -->
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $profilePath = (!empty($user['profile_picture']) && file_exists($user['profile_picture']))
                        ? htmlspecialchars($user['profile_picture'])
                        : "uploads/profile_pictures/default.png";
                    ?>
                    <img src="<?php echo $profilePath; ?>" class="rounded-circle mb-3" width="150" height="150"
                        style="object-fit: cover; border: 3px solid #0d6efd;">

                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="mt-2 text-muted"><?php echo htmlspecialchars($user['bio'] ?: 'No bio available.'); ?></p>
                    <p class="text-secondary"><small>Member since
                            <?php echo date('F Y', strtotime($user['created_at'])); ?></small></p>
                </div>
            </div>
        </div>

        <!-- =============================
             RIGHT: Edit Profile + Password
        ============================== -->
        <div class="col-md-8">

            <!-- ✅ Profile Edit -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control"
                                rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>

                        <!-- ✅ Profile Picture Upload -->
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            <div class="mt-3">
                                <img src="<?php echo $profilePath; ?>" alt="Current Picture" class="rounded" width="100"
                                    height="100" style="object-fit: cover; border: 2px solid #ccc;">
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- ✅ Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($passwordSuccess)): ?>
                        <div class="alert alert-success"><?php echo $passwordSuccess; ?></div>
                    <?php endif; ?>
                    <?php if (isset($passwordError)): ?>
                        <div class="alert alert-danger"><?php echo $passwordError; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>