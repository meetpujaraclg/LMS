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

<style>
    .users-page {
        --primary-blue: #0d6efd;
        --glass-bg: rgba(255, 255, 255, 0.95);
        --shadow-blue: 0 20px 40px rgba(13, 110, 253, 0.15);
    }

    .card.users-card {
        border: none;
        border-radius: 24px;
        box-shadow: var(--shadow-blue);
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        transition: all 0.3s ease;
    }

    .card.users-card:hover {
        box-shadow: 0 30px 60px rgba(13, 110, 253, 0.25);
        transform: translateY(-4px);
    }

    .search-input {
        border: 2px solid rgba(13, 110, 253, 0.2);
        border-radius: 16px;
        padding: 0.75rem 1.25rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        transform: scale(1.02);
    }

    .btn-search {
        background: var(--blue-gradient);
        border: none;
        border-radius: 16px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(13, 110, 253, 0.4);
    }

    .table-users {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .table-users thead th {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        font-weight: 600;
        border: none;
        padding: 1.25rem 1.5rem;
    }

    .table-users tbody tr {
        transition: all 0.2s ease;
    }

    .table-users tbody tr:hover {
        background: rgba(13, 110, 253, 0.05);
        transform: scale(1.01);
    }

    .user-avatar-img img {
        transition: all 0.3s ease;
    }

    .user-avatar-img:hover img {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
    }


    .btn-delete-user {
        border-radius: 12px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-delete-user:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
    }

    .modal-delete {
        --bs-modal-header-bg: linear-gradient(135deg, #dc3545, #c82333);
        --bs-modal-content-shadow: 0 20px 60px rgba(220, 53, 69, 0.3);
    }

    .pagination-blue .page-link {
        border-radius: 12px;
        margin: 0 0.25rem;
        border: 1px solid rgba(13, 110, 253, 0.2);
        color: var(--primary-blue);
        font-weight: 500;
    }

    .pagination-blue .page-item.active .page-link {
        background: var(--blue-gradient);
        border-color: var(--primary-blue);
    }

    .alert-custom {
        border: none;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        backdrop-filter: blur(10px);
    }
</style>

<div class="users-page">

    <!-- Success/Error Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-custom shadow-sm mb-4 animate__animated animate__fadeIn">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-custom shadow-sm mb-4 animate__animated animate__fadeIn">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Enhanced Search Card -->
    <div class="card users-card mb-5">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-lg-8 col-md-6">
                    <div class="position-relative">
                        <input type="text" name="search" class="form-control search-input shadow-sm"
                            placeholder="🔍 Search by email..." value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search position-absolute end-0 top-50 translate-middle-y pe-3 text-muted"></i>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3">
                    <button type="submit" class="btn btn-search btn-secondary w-100 shadow-sm">
                        <i class="fas fa-magnifying-glass me-2"></i>Search
                    </button>
                </div>
                <?php if ($search): ?>
                    <div class="col-lg-2 col-md-3">
                        <a href="users.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Enhanced Users Table -->
    <div class="card users-card">
        <div class="card-header bg-transparent border-0 py-3">
            <h5 class="card-title mb-0 fw-bold text-primary">
                <i class="fas fa-list me-2"></i>
                Students List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-users mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">
                                <i class="fas fa-hashtag"></i>
                            </th>
                            <th>
                                <i class="fas fa-user me-2"></i>Student
                            </th>
                            <th>
                                <i class="fas fa-envelope me-2"></i>Email
                            </th>
                            <th>
                                <i class="fas fa-calendar-check me-2"></i>Joined
                            </th>
                            <th class="text-center" style="width: 120px;">
                                <i class="fas fa-cogs"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-users-slash fa-3x mb-3 opacity-50"></i>
                                        <h5>No students found</h5>
                                        <p class="mb-0">Try adjusting your search criteria</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr class="align-middle">
                                    <td class="text-center fw-bold text-primary"><?= $u['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="user-avatar-img"
                                                style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.3); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                                <img src="../<?= htmlspecialchars($u['profile_picture'] ?? '../uploads/profile_pictures/default.png') ?>"
                                                    alt="<?= htmlspecialchars($u['first_name']) ?>"
                                                    style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                            </div>
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($u['first_name'] . " " . $u['last_name']) ?>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block"><?= htmlspecialchars($u['email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                                            <?= date("M j, Y", strtotime($u['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-user shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?= $u['id'] ?>"
                                                data-user-name="<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>">
                                                <i class="fas fa-trash-alt me-1"></i>Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Self</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Enhanced Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-transparent border-0 pt-4 pb-3">
                <nav aria-label="Students pagination">
                    <ul class="pagination pagination-blue justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left me-1"></i> Previous
                            </a>
                        </li>
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        if ($start > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link"
                                    href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                Next <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Premium Delete Modal -->
<div class="modal fade modal-delete" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <div class="modal-header text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 p-3 rounded-circle me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Delete Student Account</h5>
                            <small class="opacity-75">This action cannot be undone</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div class="alert alert-warning border-0 mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Warning:</strong> This will permanently delete the student account and send them an
                        email notification.
                    </div>
                    <div class="mb-4 p-3 bg-light rounded-3">
                        <strong class="d-block mb-2 text-danger" id="deleteUserName"></strong>
                        <div class="row text-muted small">
                            <div class="col-6"><i class="fas fa-envelope me-1"></i> Email notification sent</div>
                            <div class="col-6"><i class="fas fa-lock me-1"></i> Permanent deletion</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Deletion <span
                                class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control shadow-sm" rows="4" required
                            placeholder="Provide a clear reason for this deletion (e.g., policy violation, spam activity, etc.)"
                            style="border-radius: 12px; border: 2px solid rgba(13, 110, 253, 0.2);"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom p-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" name="delete_user" class="btn btn-danger px-4 shadow-sm">
                        <i class="fas fa-trash-alt me-2"></i>Delete Account
                    </button>
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

        // Animate table rows on load
        const rows = document.querySelectorAll('.table-users tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.5s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<?php require_once 'admin_footer.php'; ?>