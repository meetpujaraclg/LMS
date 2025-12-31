<?php
ob_start();
$admin_pageTitle = "Manage Instructors";
require_once 'admin_header.php';
require_once __DIR__ . '/../includes/mailer_config.php';

// 🧩 Handle delete instructor with reason
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_instructor'])) {
    $instructorId = (int) $_POST['instructor_id'];
    $reason = trim($_POST['reason']);

    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a reason for deletion.";
        header("Location: instructors.php");
        exit();
    }

    if ($instructorId != $_SESSION['admin_id']) {
        // Check if instructor has assigned courses
        $checkStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
        $checkStmt->execute([$instructorId]);
        $courseCount = $checkStmt->fetchColumn();

        if ($courseCount > 0) {
            $_SESSION['error'] = "Cannot delete instructor! They have $courseCount course(s) assigned. Reassign or remove them first.";
        } else {
            // Fetch instructor details for email
            $stmt = $admin_pdo->prepare("SELECT email, first_name, last_name FROM instructors WHERE id = ?");
            $stmt->execute([$instructorId]);
            $instructor = $stmt->fetch();

            if ($instructor) {
                $email = $instructor['email'];
                $name = $instructor['first_name'] . " " . $instructor['last_name'];

                // Build email message
                $subject = "Your Instructor Account Has Been Removed";
                $htmlMessage = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                        <h2 style='color:#d9534f;'>EduTech Account Removal Notice</h2>
                        <p>Dear <strong>$name</strong>,</p>
                        <p>We regret to inform you that your instructor account has been removed by the EduTech Administrator.</p>
                        <p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($reason)) . "</p>
                        <p>If you have any questions or believe this was a mistake, please reach out to our support team.</p>
                        <br>
                        <p style='color:#555;'>Best regards,<br><strong>EduTech Team</strong></p>
                    </body>
                    </html>
                ";

                // Send email and then delete
                if (sendEmail($email, $subject, $htmlMessage)) {
                    $deleteStmt = $admin_pdo->prepare("DELETE FROM instructors WHERE id = ?");
                    if ($deleteStmt->execute([$instructorId])) {
                        $_SESSION['success'] = "Instructor deleted and notified successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete instructor!";
                    }
                } else {
                    $_SESSION['error'] = "Failed to send deletion email. Instructor not deleted.";
                }
            } else {
                $_SESSION['error'] = "Instructor not found.";
            }
        }
    } else {
        $_SESSION['error'] = "You cannot delete your own admin account.";
    }

    header("Location: instructors.php");
    exit();
}

// 🧠 Pagination & Search setup
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 5; // Reduced for card layout
$offset = ($page - 1) * $limit;

$where = "profile_status = 'active'";
$params = [];
$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';

if (!empty($search)) {
    $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

// 🧾 Get total count
$countStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM instructors WHERE $where");
$countStmt->execute($params);
$totalInstructors = $countStmt->fetchColumn();
$totalPages = ceil($totalInstructors / $limit);

// 🧾 Fetch instructors
$stmt = $admin_pdo->prepare("
    SELECT i.*,
           (SELECT COUNT(*) FROM courses c WHERE c.instructor_id = i.id) AS course_count,
           (SELECT COUNT(*) FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            WHERE c.instructor_id = i.id) AS total_enrollments
    FROM instructors i
    WHERE $where
    ORDER BY i.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$instructors = $stmt->fetchAll();

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<style>
    /* Blue Glassmorphism - Single Card Instructors */
    :root {
        --primary-blue: #3b82f6;
        --primary-blue-dark: #1e40af;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.3);
        --glass-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
    }

    /* Single Glass Card */
    .instructors-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        padding: 2.5rem;
        max-width: 1000px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }

    .instructors-card:hover {
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.25);
        transform: translateY(-5px);
    }

    /* Alerts */
    .alert {
        border: none;
        border-radius: 16px;
        backdrop-filter: blur(12px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    /* Search Form */
    .search-form {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2rem;
    }

    /* Instructor Item */
    .instructor-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 1.75rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .instructor-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(59, 130, 246, 0.15);
    }

    .instructor-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(59, 130, 246, 0.3);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    }

    .instructor-info {
        flex: 1;
    }

    .instructor-name {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 0.25rem;
    }

    .instructor-email {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }

    .instructor-stats {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .stat-item {
        background: rgba(59, 130, 246, 0.1);
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.2);
        text-align: center;
    }

    .stat-number {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-blue);
    }

    .stat-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .btn {
        border-radius: 12px;
        border: none;
        font-weight: 600;
        padding: 0.625rem 1.25rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(12px);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-outline-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .btn-outline-secondary:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateY(-2px);
    }

    .btn-outline-danger {
        background: rgba(239, 68, 68, 0.2);
        color: white;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-outline-danger:hover {
        background: rgba(239, 68, 68, 0.4);
        color: white;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
    }

    .empty-icon {
        font-size: 5rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #475569;
    }

    /* Pagination */
    .pagination {
        justify-content: center;
        margin-top: 2rem;
    }

    .page-link {
        border-radius: 10px;
        margin: 0 0.25rem;
        border: 1px solid rgba(59, 130, 246, 0.3);
        color: var(--primary-blue);
    }

    .page-item.active .page-link {
        background: var(--primary-blue);
        border-color: var(--primary-blue);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .instructors-card {
            margin: 1rem;
            padding: 2rem;
        }

        .instructor-item {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .instructor-stats {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="instructors-card">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- 🔍 Search -->
            <div class="search-form">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control"
                            placeholder="🔍 Search instructors by name or email..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="instructors.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>

            <?php if (empty($instructors)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👨‍🏫</div>
                    <div class="empty-title">No approved instructors found</div>
                    <p>Approve instructor requests to see them here.</p>
                </div>
            <?php else: ?>
                <div class="instructors-count">
                    <h5>Total: <?= $totalInstructors ?> instructors</h5>
                </div>

                <?php foreach ($instructors as $i): ?>
                    <div class="instructor-item">
                        <img src="../uploads/instructors/<?= htmlspecialchars($i['profile_picture'] ?? 'default-avatar.png') ?>"
                            class="instructor-avatar" onerror="this.src='../uploads/instructors/default-avatar.png'"
                            alt="<?= htmlspecialchars($i['first_name']) ?>">

                        <div class="instructor-info">
                            <div class="instructor-name">
                                <?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?>
                            </div>
                            <div class="instructor-email"><?= htmlspecialchars($i['email']) ?></div>

                            <div class="instructor-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $i['course_count'] ?></div>
                                    <div class="stat-label">Courses</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $i['total_enrollments'] ?></div>
                                    <div class="stat-label">Enrollments</div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="view_instructor.php?id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($i['id'] != $_SESSION['admin_id']): ?>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal" data-instructor-id="<?= $i['id'] ?>"
                                    data-instructor-name="<?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
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
            <?php endif; ?>
        </div>
</div>

<!-- 🧾 Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <form method="POST">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i> Delete Instructor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="instructor_id" id="deleteInstructorId">
                    <p class="mb-4">Are you sure you want to delete <strong id="deleteInstructorName"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Deletion <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Enter detailed reason for deletion..." style="border-radius: 12px;"></textarea>
                    </div>
                    <div class="alert alert-warning p-3" style="border-radius: 12px;">
                        <i class="fas fa-envelope me-2"></i> The reason will be emailed to the instructor before
                        deletion.
                    </div>
                </div>
                <div class="modal-footer p-4"
                    style="border-top: 1px solid rgba(0,0,0,0.1); border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="border-radius: 12px;">Cancel</button>
                    <button type="submit" name="delete_instructor" class="btn btn-danger" style="border-radius: 12px;">
                        <i class="fas fa-trash me-2"></i> Delete Instructor
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
            const instructorId = button.getAttribute("data-instructor-id");
            const instructorName = button.getAttribute("data-instructor-name");
            deleteModal.querySelector("#deleteInstructorId").value = instructorId;
            deleteModal.querySelector("#deleteInstructorName").textContent = instructorName;
        });
    });
</script>

<?php require_once 'admin_footer.php';
ob_end_flush(); ?>