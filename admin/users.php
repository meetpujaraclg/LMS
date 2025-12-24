<?php
$admin_pageTitle = "Manage Students";
require_once 'admin_header.php';
require_once __DIR__ . '/../includes/mailer_config.php'; // <-- Make sure this file has $mail setup

// Handle delete with reason
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int) $_POST['user_id'];
    $reason = trim($_POST['reason']);

    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a reason for deletion.";
        header("Location: users.php");
        exit();
    }

    $stmt = $admin_pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $email = $user['email'];
        $name = $user['first_name'] . " " . $user['last_name'];

        // Build email message
        $subject = "Your Account Has Been Deleted";
        $htmlMessage = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color:#d9534f;'>Account Deletion Notice - EduTech</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>Your account has been deleted by the EduTech Administrator.</p>
                <p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($reason)) . "</p>
                <p>If you believe this was a mistake or wish to appeal, please reach out to our support team.</p>
                <br>
                <p style='color:#555;'>Best regards,<br><strong>EduTech Team</strong></p>
            </body>
            </html>
        ";

        // Send the email using your global mailer
        if (sendEmail($email, $subject, $htmlMessage)) {
            // Delete the user after email is sent
            $deleteStmt = $admin_pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($deleteStmt->execute([$userId])) {
                $_SESSION['success'] = "User deleted and notified successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete user.";
            }
        } else {
            $_SESSION['error'] = "Failed to send deletion email. User not deleted.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }

    header("Location: users.php");
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

// Get total count
$countStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Get paginated users
$stmt = $admin_pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!-- Main List -->
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Students</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search students..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Students (<?= $totalUsers ?>)</h5>
        <span class="badge bg-primary">Page <?= $page ?> of <?= $totalPages ?></span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No students found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['first_name'] . " " . $u['last_name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= date("M j, Y", strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal" data-user-id="<?= $u['id'] ?>"
                                        data-user-name="<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Pagination" class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Delete Reason Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Delete Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Deletion <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Enter the reason..."></textarea>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="fas fa-envelope"></i> The reason will be emailed to the student before deletion.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const deleteModal = document.getElementById("deleteModal");
        deleteModal.addEventListener("show.bs.modal", event => {
            const button = event.relatedTarget;
            const userId = button.getAttribute("data-user-id");
            const userName = button.getAttribute("data-user-name");
            deleteModal.querySelector("#deleteUserId").value = userId;
            deleteModal.querySelector("#deleteUserName").textContent = userName;
        });
    });
</script>

<?php require_once 'admin_footer.php'; ?>