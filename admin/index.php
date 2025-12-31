<?php
$admin_pageTitle = "Dashboard";
require_once 'admin_header.php';

// Get statistics
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

<style>
.instructor-blue-theme {
    --primary-blue: #0d6efd;
    --blue-gradient: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #084298 100%);
    --card-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
    --hover-shadow: 0 20px 40px rgba(13, 110, 253, 0.4);
}

.stat-card {
    border: none;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--blue-gradient);
}

.stat-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--hover-shadow);
}

.bg-primary-blue {
    background: var(--blue-gradient);
    color: white;
}

.bg-success-blue {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
}

.bg-info-blue {
    background: linear-gradient(135deg, #0dcaf0 0%, #0aa3bb 100%);
    color: white;
}

.bg-warning-blue {
    background: linear-gradient(135deg, #ffc107 0%, #d4a017 100%);
    color: white;
}

.bg-danger-blue {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    color: white;
}

.bg-secondary-blue {
    background: linear-gradient(135deg, #6c757d 0%, #565e64 100%);
    color: white;
}

.card.instructor-card {
    border: none;
    border-radius: 20px;
    box-shadow: var(--card-shadow);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.card.instructor-card:hover {
    box-shadow: var(--hover-shadow);
    transform: translateY(-5px);
}

.btn-instructor {
    background: var(--blue-gradient);
    border: none;
    border-radius: 12px;
    padding: 8px 20px;
    font-weight: 500;
}

.btn-instructor:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(13, 110, 253, 0.4);
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.badge-instructor {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: 500;
}
</style>


<div class="row mb-5 g-4">
    
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="card stat-card bg-success-blue h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-user-graduate fa-3x opacity-75"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo $totalStudents; ?></h3>
                <p class="mb-0 fw-medium opacity-90">Students</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="card stat-card bg-warning-blue h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-chalkboard-teacher fa-3x opacity-75"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo $totalInstructors; ?></h3>
                <p class="mb-0 fw-medium opacity-90">Instructors</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="card stat-card bg-info-blue h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-book fa-3x opacity-75"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo $totalCourses; ?></h3>
                <p class="mb-0 fw-medium opacity-90">Courses</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="card stat-card bg-danger-blue h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-sign-in-alt fa-3x opacity-75"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo $totalEnrollments; ?></h3>
                <p class="mb-0 fw-medium opacity-90">Enrollments</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
        <div class="card stat-card bg-secondary-blue h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-chart-line fa-3x opacity-75"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($totalEnrollments / max($totalStudents, 1), 1); ?></h3>
                <p class="mb-0 fw-medium opacity-90">Avg/Student</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Cards -->
<div class="row g-4 mb-5">
    <!-- Recent Users -->
    <div class="col-xl-6">
        <div class="card instructor-card h-100">
            <div class="card-header bg-gradient bg-primary text-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-light fw-medium">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Name</th>
                                <th class="py-3">Email</th>
                                <th class="pe-4 py-3 text-end">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="border-top">
                            <?php foreach ($recentUsers as $user): ?>
                            <tr class="align-middle">
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="pe-4 fw-medium text-primary"><?php echo date('M j', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="col-xl-6">
        <div class="card instructor-card h-100">
            <div class="card-header bg-gradient bg-primary text-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">Recent Courses</h5>
                    <a href="courses.php" class="btn btn-sm btn-light fw-medium">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Title</th>
                                <th class="py-3">Instructor</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="pe-4 py-3 text-end">Created</th>
                            </tr>
                        </thead>
                        <tbody class="border-top">
                            <?php foreach ($recentCourses as $course): ?>
                            <tr class="align-middle">
                                <td class="ps-4">
                                    <div>
                                        <strong class="fw-semibold"><?php echo htmlspecialchars($course['title']); ?></strong>
                                        <?php if ($course['category']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($course['category']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fw-medium"><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                <td class="text-center">
                                    <span class="badge badge-instructor bg-primary">
                                        <?php echo $course['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td class="pe-4 fw-medium text-primary"><?php echo date('M j', strtotime($course['created_at'])); ?></td>
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
    <div class="col-12">
        <div class="card instructor-card">
            <div class="card-header bg-gradient bg-primary text-white border-0 py-3">
                <h5 class="card-title mb-0 fw-bold">Enrollment Statistics (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($enrollmentStats)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="fw-bold text-center">Metric</th>
                                <?php foreach ($enrollmentStats as $stat): ?>
                                <th class="fw-bold text-center text-white"><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-primary">Enrollments</td>
                                <?php foreach ($enrollmentStats as $stat): ?>
                                <td class="text-center">
                                    <span class="badge badge-instructor bg-primary fs-6"><?php echo $stat['count']; ?></span>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No enrollment data available yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<?php require_once 'admin_footer.php'; ?>
