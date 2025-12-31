<?php
$admin_pageTitle = "Manage Enrollments";
require_once 'admin_header.php';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $enrollmentId = (int) $_GET['id'];

    if ($_GET['action'] == 'delete') {
        $stmt = $admin_pdo->prepare("DELETE FROM enrollments WHERE id = ?");
        if ($stmt->execute([$enrollmentId])) {
            $_SESSION['success'] = "Enrollment deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete enrollment!";
        }
        header("Location: enrollments.php");
        exit();
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulkAction = admin_sanitize($_POST['bulk_action']);
    $enrollmentIds = isset($_POST['enrollment_ids']) ? $_POST['enrollment_ids'] : [];

    if (!empty($enrollmentIds)) {
        $placeholders = str_repeat('?,', count($enrollmentIds) - 1) . '?';

        if ($bulkAction == 'delete') {
            $stmt = $admin_pdo->prepare("DELETE FROM enrollments WHERE id IN ($placeholders)");
            if ($stmt->execute($enrollmentIds)) {
                $_SESSION['success'] = count($enrollmentIds) . " enrollment(s) deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete enrollments!";
            }
        }
    }
    header("Location: enrollments.php");
    exit();
}

// Messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Pagination & filters
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 8; // Reduced for card layout
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int) $_GET['course'] : 0;
$status_filter = isset($_GET['status']) ? admin_sanitize($_GET['status']) : '';

$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($course_filter)) {
    $where .= " AND e.course_id = ?";
    $params[] = $course_filter;
}

if (!empty($status_filter)) {
    if ($status_filter == 'completed')
        $where .= " AND e.completed = 1";
    elseif ($status_filter == 'in_progress')
        $where .= " AND e.completed = 0 AND e.progress > 0";
    elseif ($status_filter == 'not_started')
        $where .= " AND e.progress = 0";
}

// Get total
$countStmt = $admin_pdo->prepare("
    SELECT COUNT(*) 
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE $where
");
$countStmt->execute($params);
$totalEnrollments = $countStmt->fetchColumn();
$totalPages = ceil($totalEnrollments / $limit);

// Get data
$stmt = $admin_pdo->prepare("
    SELECT e.*, u.first_name, u.last_name, u.email, u.profile_picture,
           c.title as course_title, c.duration as course_duration, c.thumbnail as course_thumbnail,
           inst.first_name as instructor_first_name, inst.last_name as instructor_last_name
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN instructors inst ON c.instructor_id = inst.id
    WHERE $where
    ORDER BY e.enrolled_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Get courses for dropdown filter
$courses = $admin_pdo->query("SELECT id, title FROM courses ORDER BY title")->fetchAll();
?>

<style>
    /* Blue Glassmorphism - Single Card Enrollments */
    :root {
        --primary-blue: #3b82f6;
        --primary-blue-dark: #1e40af;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.3);
        --glass-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
    }

    /* Page Background */
    #content,
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    /* Single Glass Card */
    .enrollments-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        padding: 2.5rem;
        max-width: 1200px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }

    .enrollments-card:hover {
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

    /* Filters Form */
    .filters-form {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2rem;
    }

    /* Enrollment Item */
    .enrollment-item {
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

    .enrollment-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(59, 130, 246, 0.15);
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(59, 130, 246, 0.3);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.2);
    }

    .enrollment-info {
        flex: 1;
    }

    .student-name {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 0.25rem;
    }

    .course-title {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .enrollment-details {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .detail-item {
        background: rgba(59, 130, 246, 0.1);
        padding: 0.5rem 1rem;
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .detail-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.9rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .status-completed {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .status-progress {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }

    /* Bulk Actions */
    .bulk-actions {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        border-radius: 12px;
        border: none;
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(12px);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    /* Checkbox */
    .enrollment-checkbox {
        width: 20px;
        height: 20px;
        accent-color: var(--primary-blue);
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
        .enrollments-card {
            margin: 1rem;
            padding: 2rem;
        }

        .enrollment-item {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .enrollment-details {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="enrollments-card">

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-form">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search students or courses..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Course</label>
                    <select name="course" class="form-select">
                        <option value="0">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="enrollments.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>

        <?php if (!empty($enrollments)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Total: <?= $totalEnrollments ?> enrollments</h5>
                <span class="badge bg-primary">Page <?= $page ?> of <?= $totalPages ?></span>
            </div>

            <form method="POST" id="bulkForm">
                <div class="bulk-actions">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <input type="checkbox" id="selectAll" class="me-2">
                        </div>
                        <div class="col">
                            <select name="bulk_action" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="">Bulk Actions</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-2">Apply</button>
                        </div>
                    </div>
                </div>

                <?php foreach ($enrollments as $e): ?>
                    <div class="enrollment-item">
                        <input type="checkbox" name="enrollment_ids[]" value="<?= $e['id'] ?>" class="enrollment-checkbox me-3">

                        <img src="../<?= htmlspecialchars($e['profile_picture'] ?? 'default-avatar.png') ?>"
                            class="student-avatar" onerror="this.src='../uploads/profile_pictures/default.png'"
                            alt="<?= htmlspecialchars($e['first_name']) ?>">

                        <div class="enrollment-info">
                            <div class="student-name">
                                <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?>
                            </div>
                            <div class="course-title"><?= htmlspecialchars($e['course_title']) ?></div>

                            <div class="enrollment-details">
                                <div class="detail-item">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><?= htmlspecialchars($e['email']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Instructor</div>
                                    <div class="detail-value">
                                        <?= $e['instructor_first_name'] ? htmlspecialchars($e['instructor_first_name'] . ' ' . $e['instructor_last_name']) : 'N/A' ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Enrolled</div>
                                    <div class="detail-value"><?= date('M j, Y', strtotime($e['enrolled_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="enrollments.php?action=delete&id=<?= $e['id'] ?>" class="btn btn-danger"
                                title="Delete" onclick="return confirm('Delete this enrollment?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <div class="empty-title">No enrollments found</div>
                <p>No students have enrolled in any courses yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const selectAll = document.getElementById('selectAll');
        const boxes = document.querySelectorAll('.enrollment-checkbox');
        if (selectAll) selectAll.addEventListener('change', () => boxes.forEach(b => b.checked = selectAll.checked));

        document.getElementById('bulkForm')?.addEventListener('submit', e => {
            const checked = document.querySelectorAll('.enrollment-checkbox:checked').length;
            if (!checked) {
                alert("Select at least one enrollment.");
                e.preventDefault();
            }
        });
    });
</script>

<?php require_once 'admin_footer.php'; ?>