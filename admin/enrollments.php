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
    } elseif ($_GET['action'] == 'toggle_complete') {
        $stmt = $admin_pdo->prepare("UPDATE enrollments SET completed = NOT completed, completed_at = IF(completed = 0, NOW(), NULL) WHERE id = ?");
        if ($stmt->execute([$enrollmentId])) {
            $_SESSION['success'] = "Enrollment status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update enrollment status!";
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
        } elseif ($bulkAction == 'mark_completed') {
            $stmt = $admin_pdo->prepare("UPDATE enrollments SET completed = 1, progress = 100, completed_at = NOW() WHERE id IN ($placeholders)");
            if ($stmt->execute($enrollmentIds)) {
                $_SESSION['success'] = count($enrollmentIds) . " enrollment(s) marked as completed!";
            } else {
                $_SESSION['error'] = "Failed to update enrollments!";
            }
        } elseif ($bulkAction == 'reset_progress') {
            $stmt = $admin_pdo->prepare("UPDATE enrollments SET progress = 0, completed = 0, completed_at = NULL WHERE id IN ($placeholders)");
            if ($stmt->execute($enrollmentIds)) {
                $_SESSION['success'] = count($enrollmentIds) . " enrollment(s) progress reset!";
            } else {
                $_SESSION['error'] = "Failed to reset enrollments!";
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
$limit = 15;
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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Enrollments</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search students or courses..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
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
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress
                    </option>
                    <option value="not_started" <?= $status_filter == 'not_started' ? 'selected' : '' ?>>Not Started
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="enrollments.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Enrollments Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Enrollments (<?= $totalEnrollments ?>)</h5>
        <span class="badge bg-primary">Page <?= $page ?> of <?= $totalPages ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($enrollments)): ?>
            <form method="POST" id="bulkForm">
                <div class="mb-3">
                    <select name="bulk_action" class="form-select form-select-sm d-inline-block w-auto">
                        <option value="">Bulk Actions</option>
                        <option value="mark_completed">Mark Completed</option>
                        <option value="reset_progress">Reset Progress</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll"></th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Enrolled</th>
                                <th>Status</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $e): ?>
                                <tr>
                                    <td><input type="checkbox" name="enrollment_ids[]" value="<?= $e['id'] ?>"
                                            class="enrollment-checkbox"></td>
                                    <td>
                                        <strong><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($e['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($e['course_title']) ?></td>
                                    <td><?= $e['instructor_first_name'] ? htmlspecialchars($e['instructor_first_name'] . ' ' . $e['instructor_last_name']) : '<span class="text-muted">N/A</span>' ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($e['enrolled_at'])) ?></td>
                                    <td>
                                        <?php if ($e['completed']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="enrollments.php?action=toggle_complete&id=<?= $e['id'] ?>"
                                                class="btn btn-outline-success" title="Toggle Status">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <a href="enrollments.php?action=delete&id=<?= $e['id'] ?>"
                                                class="btn btn-outline-danger" title="Delete"
                                                onclick="return confirm('Delete this enrollment?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

        <?php else: ?>
            <p class="text-center text-muted my-4">No enrollments found.</p>
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