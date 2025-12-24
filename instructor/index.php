<?php
// instructor/index.php

// Turn off error reporting
// error_reporting(0);
// ini_set('display_errors', 0);

// Start output buffering
ob_start();

$pageTitle = "Dashboard";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

// Get instructor statistics
try {
    // Course count
    $courseStmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM courses WHERE instructor_id = ?");
    $courseStmt->execute([$instructorId]);
    $courseCount = $courseStmt->fetchColumn();
    
    // Student count
    $studentStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.user_id) as student_count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $studentStmt->execute([$instructorId]);
    $studentCount = $studentStmt->fetchColumn();
    
    // Total revenue (if you have payment system)
    $revenueStmt = $pdo->prepare("
        SELECT SUM(c.price) as total_revenue 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ? AND c.price > 0
    ");
    $revenueStmt->execute([$instructorId]);
    $totalRevenue = $revenueStmt->fetchColumn();
    
    // Recent courses
    $recentCoursesStmt = $pdo->prepare("
        SELECT * FROM courses 
        WHERE instructor_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentCoursesStmt->execute([$instructorId]);
    $recentCourses = $recentCoursesStmt->fetchAll();
    
} catch (PDOException $e) {
    // Set default values if queries fail
    $courseCount = 0;
    $studentCount = 0;
    $totalRevenue = 0;
    $recentCourses = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="instructor_courses.php?action=add" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> New Course
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Courses
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $courseCount; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Students
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $studentCount; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Revenue
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            $<?php echo number_format($totalRevenue, 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Avg Rating
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            // You can add rating calculation here
                            echo "4.8/5.0"; 
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Courses -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Courses</h6>
                <a href="instructor_courses.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentCourses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No courses created yet</h5>
                        <p class="text-muted">Get started by creating your first course</p>
                        <a href="instructor_courses.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Course
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCourses as $course): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $course['is_published'] ? 'success' : 'warning'; ?>">
                                                <?php echo $course['is_published'] ? 'Published' : 'Draft'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="instructor_courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="instructor_courses.php?action=add" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i><br>
                            Create Course
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="instructor_courses.php" class="btn btn-success w-100">
                            <i class="fas fa-book"></i><br>
                            Manage Courses
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="instructor_students.php" class="btn btn-info w-100">
                            <i class="fas fa-users"></i><br>
                            View Students
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="instructor_profile.php" class="btn btn-warning w-100">
                            <i class="fas fa-user-edit"></i><br>
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// End output buffering and flush
ob_end_flush();
require_once 'instructor_footer.php'; 
?>