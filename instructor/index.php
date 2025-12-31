<?php
// instructor/index.php
ob_start();

$pageTitle = "Dashboard";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

// ✅ Backend untouched
try {
    $courseStmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM courses WHERE instructor_id = ?");
    $courseStmt->execute([$instructorId]);
    $courseCount = $courseStmt->fetchColumn();

    $studentStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.user_id) as student_count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $studentStmt->execute([$instructorId]);
    $studentCount = $studentStmt->fetchColumn();

    $revenueStmt = $pdo->prepare("
        SELECT SUM(c.price) as total_revenue 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ? AND c.price > 0
    ");
    $revenueStmt->execute([$instructorId]);
    $totalRevenue = $revenueStmt->fetchColumn();

    $recentCoursesStmt = $pdo->prepare("
        SELECT * FROM courses 
        WHERE instructor_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentCoursesStmt->execute([$instructorId]);
    $recentCourses = $recentCoursesStmt->fetchAll();
} catch (PDOException $e) {
    $courseCount = 0;
    $studentCount = 0;
    $totalRevenue = 0;
    $recentCourses = [];
}
?>

<style>
    /* 🌈 Modern Blue Dashboard Theme */
    .dashboard-header {
        background: linear-gradient(90deg, #0062E6, #33AEFF);
        color: #fff;
        border-radius: 10px;
        padding: 18px 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        transition: transform .2s, box-shadow .2s;
    }

    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    }

    .card .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .card h6 {
        font-weight: 600;
    }

    .border-left-primary {
        border-left: 4px solid #0062E6;
    }

    .border-left-success {
        border-left: 4px solid #1cc88a;
    }

    .border-left-info {
        border-left: 4px solid #36b9cc;
    }

    .border-left-warning {
        border-left: 4px solid #f6c23e;
    }

    .quick-btn {
        border-radius: 12px;
        font-weight: 500;
        padding: 15px 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        transition: transform .2s, box-shadow .2s;
    }

    .quick-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 14px rgba(0, 0, 0, 0.12);
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
    }
</style>

<!-- 🔹 Page Header -->
<div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap">
    <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Instructor Dashboard</h2>
    <a href="instructor_courses.php?action=add" class="btn btn-light text-primary fw-semibold shadow-sm">
        <i class="fas fa-plus"></i> New Course
    </a>
</div>

<!-- 📊 Stats Cards -->
<div class="row g-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary p-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Total Courses</div>
                    <h4 class="fw-bold mb-0 mt-1"><?php echo $courseCount; ?></h4>
                </div>
                <div><i class="fas fa-book fa-2x text-primary"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-success p-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Total Students</div>
                    <h4 class="fw-bold mb-0 mt-1"><?php echo $studentCount; ?></h4>
                </div>
                <div><i class="fas fa-users fa-2x text-success"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-info p-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Total Revenue</div>
                    <h4 class="fw-bold mb-0 mt-1">₹<?php echo number_format($totalRevenue, 2); ?></h4>
                </div>
                <div><i class="fas fa-rupee-sign fa-2x text-info"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-warning p-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Avg Rating</div>
                    <h4 class="fw-bold mb-0 mt-1">4.8 / 5.0</h4>
                </div>
                <div><i class="fas fa-star fa-2x text-warning"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- 📘 Recent Courses -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-semibold text-primary"><i class="fas fa-clock me-2"></i>Recent Courses</h6>
        <a href="instructor_courses.php" class="btn btn-sm btn-outline-primary">
            View All
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($recentCourses)): ?>
            <div class="text-center py-4">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No courses created yet</h5>
                <p class="text-muted">Get started by creating your first course</p>
                <a href="instructor_courses.php?action=add" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus"></i> Create First Course
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Category</th>   
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentCourses as $course): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($course['created_at'])); ?></td>
                                <td><a href="instructor_courses.php?action=edit&id=<?php echo $course['id']; ?>"
                                        class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ⚙️ Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="m-0 fw-semibold text-primary"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <a href="instructor_courses.php?action=add" class="btn btn-primary w-100 quick-btn">
                    <i class="fas fa-plus fa-lg mb-2"></i><br>Create Course
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="instructor_courses.php" class="btn btn-success w-100 quick-btn">
                    <i class="fas fa-book fa-lg mb-2"></i><br>Manage Courses
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="instructor_students.php" class="btn btn-info w-100 quick-btn text-white">
                    <i class="fas fa-users fa-lg mb-2"></i><br>View Students
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="instructor_profile.php" class="btn btn-warning w-100 quick-btn">
                    <i class="fas fa-user-edit fa-lg mb-2"></i><br>Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php
ob_end_flush();
require_once 'instructor_footer.php';
?>