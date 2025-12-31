<?php
// instructor/instructor_students.php
$pageTitle = "Manage Students";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

// Pagination, filters, backend — unchanged
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int) $_GET['course'] : 0;
$status_filter = isset($_GET['status']) ? admin_sanitize($_GET['status']) : '';

$where = "c.instructor_id = ?";
$params = [$instructorId];
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

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE $where
");
$countStmt->execute($params);
$totalStudents = $countStmt->fetchColumn();
$totalPages = ceil($totalStudents / $limit);

$stmt = $pdo->prepare("
    SELECT 
        e.*, u.first_name, u.last_name, u.email, u.profile_picture,
        c.title as course_title, c.duration as course_duration,
        (SELECT COUNT(*) FROM lessons l 
         JOIN course_modules cm ON l.module_id = cm.id 
         WHERE cm.course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM user_progress up 
         JOIN lessons l ON up.lesson_id = l.id 
         JOIN course_modules cm ON l.module_id = cm.id 
         WHERE up.user_id = u.id AND cm.course_id = c.id AND up.completed = 1) as completed_lessons
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE $where
    ORDER BY e.enrolled_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

$instructorCourses = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title");
$instructorCourses->execute([$instructorId]);
$courses = $instructorCourses->fetchAll();
?>

<style>
    /* 🎨 Modern Blue UI Styling */
    .page-header {
        background: linear-gradient(90deg, #0062E6, #33AEFF);
        color: #fff;
        padding: 18px 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .page-header h2 {
        font-weight: 600;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        transition: transform .2s, box-shadow .2s;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .table thead {
        background-color: #f0f4ff;
    }

    .table thead th {
        color: #0056d6;
        font-weight: 600;
    }

    .badge {
        font-size: .8rem;
        border-radius: 8px;
    }

    .progress {
        height: 16px;
        border-radius: 8px;
    }

    .progress-bar {
        border-radius: 8px;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(90deg, #0062E6, #33AEFF);
        border: none;
    }

    .pagination .page-link {
        border-radius: 6px;
    }

    .summary-card {
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        transition: transform .2s;
    }

    .summary-card:hover {
        transform: translateY(-4px);
    }

    .summary-card .card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<!-- 🔹 Page Header -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h2><i class="fas fa-user-graduate me-2"></i>Manage Students</h2>
    <span class="badge bg-light text-primary fs-6"><?php echo $totalStudents; ?> Enrollments</span>
</div>

<!-- 🔍 Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control shadow-sm"
                    placeholder="Search students or courses..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="course" class="form-select shadow-sm">
                    <option value="0">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select shadow-sm">
                    <option value="">All Status</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed
                    </option>
                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In
                        Progress</option>
                    <option value="not_started" <?php echo $status_filter == 'not_started' ? 'selected' : ''; ?>>Not
                        Started</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="instructor_students.php" class="btn btn-secondary w-100 shadow-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- 👥 Students Table -->
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary"><i class="fas fa-users me-2"></i>Student Enrollments</h5>
        <span class="badge bg-primary text-white">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Enrolled</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-user-graduate fa-3x mb-3"></i><br>
                                No student enrollments found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">

                                        <div>
                                            <strong><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></strong><br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($enrollment['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($enrollment['course_title']); ?></strong><br>
                                    <small class="text-muted">Duration: <?php echo $enrollment['course_duration']; ?>h</small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?><br>
                                    <small
                                        class="text-muted"><?php echo date('g:i A', strtotime($enrollment['enrolled_at'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $progress = $enrollment['total_lessons'] > 0 ? round(($enrollment['completed_lessons'] / $enrollment['total_lessons']) * 100) : 0;
                                    ?>
                                    <div class="progress" style="width:120px;">
                                        <div class="progress-bar <?php echo $progress == 100 ? 'bg-success' : ($progress > 50 ? 'bg-info' : 'bg-warning'); ?>"
                                            style="width:<?php echo $progress; ?>%;">
                                            <?php echo $progress; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($enrollment['completed']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo $enrollment['progress'] > 0 ? 'primary' : 'secondary'; ?>">
                                            <?php echo $enrollment['progress'] > 0 ? 'In Progress' : 'Not Started'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $lastActivity = $enrollment['updated_at'] ?: $enrollment['enrolled_at'];
                                    echo date('M j, Y', strtotime($lastActivity)); ?><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($lastActivity)); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo $course_filter; ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo $course_filter; ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&course=<?php echo $course_filter; ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- 📈 Summary Cards -->
<div class="row mt-4 g-3">
    <div class="col-md-4">
        <div class="card summary-card bg-primary text-white">
            <div class="card-body">
                <div>
                    <h3><?php echo $totalStudents; ?></h3>
                    <p class="mb-0">Total Students</p>
                </div>
                <i class="fas fa-users fa-2x"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card summary-card bg-success text-white">
            <div class="card-body">
                <div>
                    <?php
                    $completedStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE c.instructor_id = ? AND e.completed = 1
                    ");
                    $completedStmt->execute([$instructorId]);
                    $completedCount = $completedStmt->fetchColumn();
                    ?>
                    <h3><?php echo $completedCount; ?></h3>
                    <p class="mb-0">Course Completions</p>
                </div>
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card summary-card bg-info text-white">
            <div class="card-body">
                <div>
                    <?php
                    $activeStmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT user_id) FROM enrollments e 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE c.instructor_id = ? AND e.progress > 0 AND e.completed = 0
                    ");
                    $activeStmt->execute([$instructorId]);
                    $activeCount = $activeStmt->fetchColumn();
                    ?>
                    <h3><?php echo $activeCount; ?></h3>
                    <p class="mb-0">Active Students</p>
                </div>
                <i class="fas fa-user-clock fa-2x"></i>
            </div>
        </div>
    </div>
</div>

<?php require_once 'instructor_footer.php'; ?>