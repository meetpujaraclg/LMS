<?php
$admin_pageTitle = "Reports & Analytics";
require_once 'admin_header.php';

// Date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get comprehensive statistics
$totalUsers = $admin_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $admin_pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalEnrollments = $admin_pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$completedCourses = $admin_pdo->query("SELECT COUNT(*) FROM enrollments WHERE completed = 1")->fetchColumn();
$totalInstructors = $admin_pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
$totalStudents = $admin_pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// User growth data
$userGrowth = $admin_pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        (SELECT COUNT(*) FROM users u2 WHERE DATE_FORMAT(u2.created_at, '%Y-%m') <= month) as cumulative
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$userGrowth->execute();
$userGrowthData = $userGrowth->fetchAll();

// Enrollment statistics
$enrollmentStats = $admin_pdo->prepare("
    SELECT 
        DATE_FORMAT(enrolled_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM enrollments 
    WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
    ORDER BY month
");
$enrollmentStats->execute();
$enrollmentData = $enrollmentStats->fetchAll();

// Popular courses
$popularCourses = $admin_pdo->query("
    SELECT 
        c.id, c.title, c.category, c.level,
        u.first_name, u.last_name,
        COUNT(e.id) as enrollment_count,
        AVG(e.progress) as avg_progress,
        SUM(e.completed) as completed_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN users u ON c.instructor_id = u.id
    GROUP BY c.id
    ORDER BY enrollment_count DESC
    LIMIT 10
")->fetchAll();

// Instructor performance
$instructorPerformance = $admin_pdo->query("
    SELECT 
        u.id, u.first_name, u.last_name, u.email,
        COUNT(DISTINCT c.id) as course_count,
        COUNT(DISTINCT e.id) as student_count,
        AVG(e.progress) as avg_progress,
        SUM(e.completed) as completed_courses
    FROM users u
    LEFT JOIN courses c ON u.id = c.instructor_id
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE u.role = 'instructor'
    GROUP BY u.id
    ORDER BY student_count DESC
")->fetchAll();

// Course completion rates
$completionRates = $admin_pdo->query("
    SELECT 
        c.title,
        COUNT(e.id) as total_enrollments,
        SUM(e.completed) as completed_enrollments,
        ROUND((SUM(e.completed) / COUNT(e.id)) * 100, 2) as completion_rate
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    HAVING total_enrollments > 0
    ORDER BY completion_rate DESC
    LIMIT 10
")->fetchAll();

// User engagement levels
$userEngagement = $admin_pdo->query("
    SELECT 
        CASE 
            WHEN progress = 100 THEN 'Completed'
            WHEN progress >= 75 THEN 'Highly Engaged'
            WHEN progress >= 50 THEN 'Moderately Engaged'
            WHEN progress >= 25 THEN 'Minimally Engaged'
            ELSE 'Low Engagement'
        END as engagement_level,
        COUNT(*) as user_count
    FROM enrollments 
    GROUP BY engagement_level
    ORDER BY user_count DESC
")->fetchAll();

// Calculate total revenue from course prices (alternative approach)
$revenueData = $admin_pdo->query("
    SELECT 
        'Total Course Value' as metric,
        CONCAT('$', FORMAT(SUM(price), 2)) as value
    FROM courses 
    WHERE price > 0
    UNION ALL
    SELECT 
        'Free Courses' as metric,
        COUNT(*) as value
    FROM courses 
    WHERE price = 0
    UNION ALL
    SELECT 
        'Premium Courses' as metric,
        COUNT(*) as value
    FROM courses 
    WHERE price > 0
")->fetchAll();

// Get platform statistics
$platformStats = $admin_pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM courses WHERE is_published = 1) as published_courses,
        (SELECT COUNT(*) FROM courses WHERE is_published = 0) as draft_courses,
        (SELECT COUNT(*) FROM course_modules) as total_modules,
        (SELECT COUNT(*) FROM lessons) as total_lessons,
        (SELECT COUNT(*) FROM user_progress WHERE completed = 1) as completed_lessons
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports & Analytics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="reports.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Key Metrics -->
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
                        <h4><?php echo $completedCourses; ?></h4>
                        <p>Completed</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- User Growth -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">User Growth (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>New Users</th>
                                <th>Total Users</th>
                                <th>Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userGrowthData as $growth): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($growth['month'] . '-01')); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $growth['count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $growth['cumulative']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $prevMonth = date('Y-m', strtotime($growth['month'] . '-01 -1 month'));
                                        $prevGrowth = 0;
                                        foreach ($userGrowthData as $g) {
                                            if ($g['month'] == $prevMonth) {
                                                $prevGrowth = $g['cumulative'];
                                                break;
                                            }
                                        }
                                        $growthRate = $prevGrowth > 0 ? round((($growth['cumulative'] - $prevGrowth) / $prevGrowth) * 100, 1) : 100;
                                        ?>
                                        <span class="badge bg-<?php echo $growthRate >= 0 ? 'info' : 'warning'; ?>">
                                            <?php echo $growthRate >= 0 ? '+' : ''; ?><?php echo $growthRate; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment Statistics -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">Enrollment Statistics (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Enrollments</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prevCount = 0;
                            foreach ($enrollmentData as $stat): 
                                $trend = $prevCount > 0 ? round((($stat['count'] - $prevCount) / $prevCount) * 100, 1) : 100;
                                $prevCount = $stat['count'];
                            ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $stat['count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $trend >= 0 ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-arrow-<?php echo $trend >= 0 ? 'up' : 'down'; ?>"></i>
                                            <?php echo abs($trend); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popular Courses -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">Most Popular Courses</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Enrollments</th>
                                <th>Progress</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popularCourses as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $course['title']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $course['category']; ?> • <?php echo ucfirst($course['level']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo $course['first_name'] . ' ' . $course['last_name']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $course['enrollment_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $course['avg_progress'] > 50 ? 'success' : 'warning'; ?>">
                                            <?php echo round($course['avg_progress']); ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $course['completed_count']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Completion Rates -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">Course Completion Rates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Enrollments</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completionRates as $course): ?>
                                <tr>
                                    <td><?php echo $course['title']; ?></td>
                                    <td class="text-center"><?php echo $course['total_enrollments']; ?></td>
                                    <td class="text-center"><?php echo $course['completed_enrollments']; ?></td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px; width: 120px; margin: 0 auto;">
                                            <div class="progress-bar bg-<?php 
                                                echo $course['completion_rate'] > 50 ? 'success' : 
                                                     ($course['completion_rate'] > 25 ? 'warning' : 'danger'); 
                                            ?>" style="width: <?php echo $course['completion_rate']; ?>%;">
                                                <?php echo $course['completion_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Instructor Performance -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Instructor Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Instructor</th>
                                <th>Courses</th>
                                <th>Students</th>
                                <th>Avg Progress</th>
                                <th>Completed</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructorPerformance as $instructor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?></strong>
                                        <br><small class="text-muted"><?php echo $instructor['email']; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $instructor['course_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $instructor['student_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($instructor['avg_progress']): ?>
                                            <span class="badge bg-<?php echo $instructor['avg_progress'] > 50 ? 'success' : 'warning'; ?>">
                                                <?php echo round($instructor['avg_progress']); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $instructor['completed_courses']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $performance = 0;
                                        if ($instructor['student_count'] > 0 && $instructor['avg_progress']) {
                                            $performance = ($instructor['avg_progress'] * $instructor['student_count']) / 100;
                                        }
                                        ?>
                                        <span class="badge bg-<?php 
                                            echo $performance > 50 ? 'success' : 
                                                 ($performance > 25 ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo round($performance, 1); ?> pts
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Engagement -->
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">User Engagement Levels</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Engagement Level</th>
                                <th>Users</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalEngagedUsers = array_sum(array_column($userEngagement, 'user_count'));
                            foreach ($userEngagement as $engagement): 
                                $percentage = $totalEngagedUsers > 0 ? round(($engagement['user_count'] / $totalEngagedUsers) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($engagement['engagement_level']) {
                                                case 'Completed': echo 'success'; break;
                                                case 'Highly Engaged': echo 'info'; break;
                                                case 'Moderately Engaged': echo 'warning'; break;
                                                case 'Minimally Engaged': echo 'secondary'; break;
                                                default: echo 'danger';
                                            }
                                        ?>">
                                            <?php echo $engagement['engagement_level']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo $engagement['user_count']; ?></td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px; width: 150px; margin: 0 auto;">
                                            <div class="progress-bar bg-<?php 
                                                switch($engagement['engagement_level']) {
                                                    case 'Completed': echo 'success'; break;
                                                    case 'Highly Engaged': echo 'info'; break;
                                                    case 'Moderately Engaged': echo 'warning'; break;
                                                    case 'Minimally Engaged': echo 'secondary'; break;
                                                    default: echo 'danger';
                                                }
                                            ?>" style="width: <?php echo $percentage; ?>%;">
                                                <?php echo $percentage; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Statistics -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Platform Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Published Courses</td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo $platformStats['published_courses']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Draft Courses</td>
                                <td class="text-center">
                                    <span class="badge bg-warning"><?php echo $platformStats['draft_courses']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Total Modules</td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $platformStats['total_modules']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Total Lessons</td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $platformStats['total_lessons']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Completed Lessons</td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo $platformStats['completed_lessons']; ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Value Statistics -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Course Value Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueData as $stat): ?>
                                <tr>
                                    <td><?php echo $stat['metric']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php 
                                            echo $stat['metric'] == 'Total Course Value' ? 'success' : 
                                                 ($stat['metric'] == 'Premium Courses' ? 'info' : 'warning');
                                        ?>">
                                            <?php echo $stat['value']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToPDF() {
    alert('PDF export feature would be implemented here');
    // In a real implementation, this would generate a PDF report
}

function exportToExcel() {
    alert('Excel export feature would be implemented here');
    // In a real implementation, this would generate an Excel report
}

// Print optimization
window.addEventListener('beforeprint', function() {
    document.querySelectorAll('.btn-group, .card-header .btn-toolbar').forEach(el => {
        el.style.display = 'none';
    });
});

window.addEventListener('afterprint', function() {
    document.querySelectorAll('.btn-group, .card-header .btn-toolbar').forEach(el => {
        el.style.display = '';
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>