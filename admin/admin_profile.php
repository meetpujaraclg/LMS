<?php

$admin_pageTitle = "Manage Profile";

require_once 'admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once 'admin_header.php';

$adminId = $_SESSION['admin_id'];
$success = $error = "";

// Fetch current admin data
$stmt = $admin_pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found!");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $profile_picture = $admin['profile_picture'];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/admins/";
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $fileExt = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($fileExt), $allowed)) {
            $fileName = time() . "_admin." . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                // Delete old image if exists
                if (!empty($admin['profile_picture']) && file_exists($uploadDir . $admin['profile_picture'])) {
                    unlink($uploadDir . $admin['profile_picture']);
                }
                $profile_picture = $fileName;
            }
        }
    }

    // Validate password match if changing
    if (!empty($new_password) || !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $admin_pdo->prepare("UPDATE admins SET first_name=?, last_name=?, email=?, password=?, profile_picture=? WHERE id=?");
            $result = $update->execute([$first_name, $last_name, $email, $hashed, $profile_picture, $adminId]);
            if ($result) {
                $_SESSION['admin_first_name'] = $first_name;
                $_SESSION['admin_last_name'] = $last_name;
                $success = "Profile updated successfully (password changed)!";
            } else {
                $error = "Failed to update profile.";
            }
        }
    } else {
        // Update without password change
        $update = $admin_pdo->prepare("UPDATE admins SET first_name=?, last_name=?, email=?, profile_picture=? WHERE id=?");
        $result = $update->execute([$first_name, $last_name, $email, $profile_picture, $adminId]);
        if ($result) {
            $_SESSION['admin_first_name'] = $first_name;
            $_SESSION['admin_last_name'] = $last_name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    }

    // Refresh admin data
    $stmt = $admin_pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-user-gear"></i> Edit Profile</h4>
            <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-3 text-center">
                    <img src="uploads/admins/<?= htmlspecialchars($admin['profile_picture'] ?: 'default_admin.png'); ?>"
                        class="rounded-circle border mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <input type="file" name="profile_picture" class="form-control form-control-sm mt-2"
                        accept="image/*">
                </div>

                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($admin['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($admin['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($admin['email']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control"
                                placeholder="Leave blank to keep current password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control"
                                placeholder="Retype new password">
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary mt-2">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>