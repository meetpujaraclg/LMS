<?php
$pageTitle = "Dashboard";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

include 'includes/header.php';
global $pdo;

// Current user
$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'User';

/**
 * Helper: compute course progress (0–100)
 */
function getCourseProgress(PDO $pdo, int $userId, int $courseId): int
{
    // Total lessons in course
    $totalStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lessons l
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE cm.course_id = ?
    ");
    $totalStmt->execute([$courseId]);
    $totalLessons = (int) $totalStmt->fetchColumn();

    if ($totalLessons === 0)
        return 0;

    // Completed lessons
    $completedStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_progress up
        JOIN lessons l ON up.lesson_id = l.id
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE up.user_id = ? AND up.completed = 1 AND cm.course_id = ?
    ");
    $completedStmt->execute([$userId, $courseId]);
    $completedLessons = (int) $completedStmt->fetchColumn();

    return (int) round($completedLessons * 100 / $totalLessons);
}

// === ENROLLED COURSES ===
$stmt = $pdo->prepare("
    SELECT c.*, e.enrolled_at 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.user_id = ? 
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$userId]);
$allEnrolledCourses = $stmt->fetchAll();

// Separate by progress
$continueCourses = [];
$completedCoursesList = [];

foreach ($allEnrolledCourses as &$courseRow) {
    $progress = getCourseProgress($pdo, $userId, (int) $courseRow['id']);
    $courseRow['progress'] = $progress;
    if ($progress === 100) {
        $completedCoursesList[] = $courseRow;
    } else {
        $continueCourses[] = $courseRow;
    }
}
unset($courseRow);

// === FAVORITES ===
$favStmt = $pdo->prepare("
    SELECT c.*
    FROM favorites f
    JOIN courses c ON f.course_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 5
");
$favStmt->execute([$userId]);
$favCourses = $favStmt->fetchAll();

// === STATS ===
$totalCourses = count($allEnrolledCourses);
$completedCourses = count($completedCoursesList);
$certificateCount = $completedCourses; // 1 certificate per completed course
?>

<style>
    :root {
        --primary-start: #0f62ff;
        --primary-end: #2d9eff;
        --accent: #ffb545;
        --bg-page: #f3f6ff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-soft: rgba(148, 163, 184, 0.25);
        --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.16);
        --radius-lg: 18px;
        --radius-md: 14px;
    }

    body {
        background: radial-gradient(circle at top left, #e0ebff 0, #f3f6ff 40%, #f9fafb 100%);
        font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: var(--text-main);
    }

    .dashboard-hero {
        background: linear-gradient(120deg, var(--primary-start), var(--primary-end));
        border-radius: var(--radius-lg);
        padding: 1.6rem 1.8rem;
        color: #e5edff;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(255, 255, 255, 0.22);
        margin-bottom: 1.8rem;
    }

    .dashboard-hero h2 {
        font-weight: 700;
        color: #fff;
    }

    .dashboard-hero p {
        color: #dbeafe;
    }

    .stat-card {
        border-radius: var(--radius-md);
        border: 1px solid var(--border-soft);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        background: rgba(255, 255, 255, 0.98);
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
    }

    .card.course-card {
        border-radius: var(--radius-md);
        border: 1px solid var(--border-soft);
    }

    .progress {
        background-color: rgba(148, 163, 184, 0.25);
        border-radius: 999px;
        overflow: hidden;
    }

    .progress-bar {
        border-radius: 999px;
        background: linear-gradient(120deg, var(--primary-start), var(--primary-end));
    }

    .card-header i {
        vertical-align: middle;
    }

    .hover-lift:hover {
        transform: translateY(-3px);
        transition: 0.2s ease;
    }
</style>

<div class="container mt-4 mb-5">

    <!-- Hero -->
    <div class="dashboard-hero">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
                <h2 class="mb-1">Welcome back, <?= htmlspecialchars($userName); ?>!</h2>
                <p class="mb-0">Here is a snapshot of your learning progress.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="courses.php" class="btn btn-light btn-sm px-3 fw-semibold">
                    <i class="fas fa-search me-2"></i>Browse Courses
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Courses Enrolled</p>
                        <h4 class="mb-0"><?= $totalCourses; ?></h4>
                    </div>
                    <div class="stat-icon bg-primary"><i class="fas fa-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Courses Completed</p>
                        <h4 class="mb-0"><?= $completedCourses; ?></h4>
                    </div>
                    <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Certificates</p>
                        <h4 class="mb-0"><?= $certificateCount; ?></h4>
                    </div>
                    <div class="stat-icon bg-warning"><i class="fas fa-award"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Continue Learning -->
    <div class="card course-card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-play-circle me-2"></i>Continue Learning</h5>
        </div>
        <div class="card-body">
            <?php if (empty($continueCourses)): ?>
                <p class="text-muted mb-3">You haven't enrolled in any active courses yet.</p>
                <a href="courses.php" class="btn btn-primary">Browse Courses</a>
            <?php else: ?>
                <?php foreach ($continueCourses as $course): ?>
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($course['title']); ?></h6>
                                    <p class="text-muted mb-2 small">
                                        Enrolled on <?= date('M j, Y', strtotime($course['enrolled_at'])); ?>
                                    </p>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?= $course['progress']; ?>%;"></div>
                                    </div>
                                    <small class="text-muted"><?= $course['progress']; ?>% completed</small>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <a href="course-detail.php?id=<?= $course['id']; ?>" class="btn btn-sm btn-primary">
                                        Continue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Completed Courses -->
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-check-circle me-2"></i>Completed Courses</h5>
        </div>
        <div class="card-body">
            <?php if (empty($completedCoursesList)): ?>
                <p class="text-muted mb-0">No courses completed yet.</p>
            <?php else: ?>
                <?php foreach ($completedCoursesList as $course): ?>
                    <div class="card mb-3 border-0 shadow-sm hover-lift">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1 text-success"><?= htmlspecialchars($course['title']); ?></h6>
                                <small class="text-muted">Completed ✅</small>
                            </div>
                            <a href="certificate.php?course_id=<?= $course['id']; ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-award me-1"></i> View Certificate
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Favorite Courses -->
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-heart me-2"></i>Favorite Courses</h5>
        </div>
        <div class="card-body">
            <?php if (empty($favCourses)): ?>
                <p class="text-muted mb-0">You haven’t added any favorites yet.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($favCourses as $fav): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100 hover-lift">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($fav['title']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <?= htmlspecialchars(mb_strimwidth($fav['description'], 0, 80, '...')); ?>
                                    </p>
                                    <a href="course-detail.php?id=<?= $fav['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play me-1"></i>View Course
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>