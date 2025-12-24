<?php
$pageTitle = "Dashboard";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

include 'includes/header.php';
global $pdo;

// 🧠 Get current student info
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'User';

// 🧾 Fetch enrolled courses
$stmt = $pdo->prepare("
    SELECT c.*, e.progress, e.enrolled_at 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.user_id = ? 
    ORDER BY e.enrolled_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$enrolledCourses = $stmt->fetchAll();

// 📊 Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCourses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND progress = 100");
$stmt->execute([$userId]);
$completedCourses = $stmt->fetchColumn();
?>

<!-- 🧭 Add space at top and bottom -->
<div class="container mt-5 mb-5">

    <div class="row mb-4">
        <div class="col-md-12 text-center text-md-start">
            <h2 class="fw-bold">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p class="text-muted">Here's your learning dashboard</p>
        </div>
    </div>

    <!-- 📊 Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <h4><?php echo $totalCourses; ?></h4>
                        <p>Courses Enrolled</p>
                    </div>
                    <i class="fas fa-book fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <h4><?php echo $completedCourses; ?></h4>
                        <p>Courses Completed</p>
                    </div>
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <h4>0</h4>
                        <p>Certificates</p>
                    </div>
                    <i class="fas fa-award fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 📚 Continue Learning -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">Continue Learning</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolledCourses)): ?>
                        <p class="text-muted">You haven't enrolled in any courses yet.</p>
                        <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                    <?php else: ?>
                        <?php foreach ($enrolledCourses as $course): ?>
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></h6>
                                            <p class="text-muted mb-2">Enrolled on:
                                                <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?></p>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted"><?php echo $course['progress']; ?>% completed</small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="course-detail.php?id=<?php echo $course['id']; ?>"
                                                class="btn btn-sm btn-primary mt-3">Continue</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 🕒 Recent Activity -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-semibold">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">No recent activity.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>