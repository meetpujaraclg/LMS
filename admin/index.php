<?php
$admin_pageTitle = "Dashboard";
require_once 'admin_header.php';

// Get statistics
$totalUsers = $admin_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $admin_pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalEnrollments = $admin_pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$totalInstructors = $admin_pdo->query("SELECT COUNT(*) FROM instructors")->fetchColumn();
$totalStudents = $admin_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get recent users
$recentUsers = $admin_pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get recent courses
$recentCourses = $admin_pdo->query("
    SELECT c.*, u.first_name, u.last_name 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    ORDER BY c.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get enrollment stats
$enrollmentStats = $admin_pdo->query("
    SELECT 
        DATE_FORMAT(enrolled_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM enrollments 
    WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
    ORDER BY month
")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
            <button type="button" class="btn btn-sm btn-outline-secondary">Print</button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalUsers; ?></h4>
                        <p>Total Users</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalStudents; ?></h4>
                        <p>Students</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalInstructors; ?></h4>
                        <p>Instructors</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalCourses; ?></h4>
                        <p>Courses</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalEnrollments; ?></h4>
                        <p>Enrollments</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-sign-in-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card stat-card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($totalEnrollments / max($totalStudents, 1), 1); ?></h4>
                        <p>Avg/Student</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Users</h5>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></strong>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo date('M j', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Courses</h5>
                <a href="courses.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Instructor</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCourses as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $course['title']; ?></strong>
                                        <?php if ($course['category']): ?>
                                            <br><small class="text-muted"><?php echo $course['category']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $course['first_name'] . ' ' . $course['last_name']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $course['is_published'] ? 'success' : 'warning'; ?>">
                                            <?php echo $course['is_published'] ? 'Published' : 'Draft'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j', strtotime($course['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enrollment Statistics -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Enrollment Statistics (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($enrollmentStats)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <?php foreach ($enrollmentStats as $stat): ?>
                                        <th><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Enrollments</strong></td>
                                    <?php foreach ($enrollmentStats as $stat): ?>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $stat['count']; ?></span>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No enrollment data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>