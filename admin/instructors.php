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
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "profile_status = 'active'"; // ✅ Only approved instructors
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

// ✅ Messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Instructors</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- 🔍 Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search instructors..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Search</button></div>
            <div class="col-md-2"><a href="instructors.php" class="btn btn-secondary w-100">Reset</a></div>
        </form>
    </div>
</div>

<!-- 🧾 Instructors Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Instructors (<?= $totalInstructors ?>)</h5>
        <span class="badge bg-primary">Page <?= $page ?> of <?= $totalPages ?></span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Courses</th>
                    <th>Enrollments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($instructors)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="fas fa-chalkboard-teacher fa-2x mb-2 d-block"></i>
                            No approved instructors found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($instructors as $i): ?>
                        <tr>
                            <td><?= $i['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?></strong>
                                <?php if (!empty($i['profile_picture'])): ?>
                                    <br>
                                    <img src="../uploads/instructors/<?= htmlspecialchars($i['profile_picture']) ?>"
                                        class="rounded mt-1" width="50" height="50" style="object-fit:cover;">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($i['email']) ?></td>
                            <td><span class="badge bg-primary"><?= $i['course_count'] ?></span></td>
                            <td><span class="badge bg-info"><?= $i['total_enrollments'] ?></span></td>

                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_instructor.php?id=<?= $i['id'] ?>" class="btn btn-outline-secondary"
                                        title="View Full Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($i['id'] != $_SESSION['admin_id']): ?>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal" data-instructor-id="<?= $i['id'] ?>"
                                            data-instructor-name="<?= htmlspecialchars($i['first_name'] . ' ' . $i['last_name']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Page navigation">
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
</div>

<!-- 🧾 Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Delete Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="instructor_id" id="deleteInstructorId">
                    <p>Are you sure you want to delete <strong id="deleteInstructorName"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Deletion <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Enter the reason..."></textarea>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="fas fa-envelope"></i> The reason will be emailed to the instructor before deletion.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_instructor" class="btn btn-danger">Delete Instructor</button>
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

<?php require_once 'admin_footer.php'; ob_end_flush(); ?>