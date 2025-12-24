<?php
$pageTitle = "Course Details";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    redirect('courses.php');
}

$courseId = $_GET['id'];
global $pdo;

// Get course details
$stmt = $pdo->prepare("
    SELECT 
        c.*, 
        u.first_name, 
        u.last_name, 
        u.bio AS instructor_bio, 
        u.profile_picture 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses.php');
}

// Check if user is enrolled
$isEnrolled = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    $isEnrolled = $stmt->fetch() ? true : false;
}

// Get modules and lessons
$modulesStmt = $pdo->prepare("
    SELECT cm.*, 
           (SELECT COUNT(*) FROM lessons l WHERE l.module_id = cm.id) as lesson_count 
    FROM course_modules cm 
    WHERE cm.course_id = ? 
    ORDER BY cm.sort_order
");
$modulesStmt->execute([$courseId]);
$modules = $modulesStmt->fetchAll();

foreach ($modules as &$module) {
    $lessonsStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order");
    $lessonsStmt->execute([$module['id']]);
    $module['lessons'] = $lessonsStmt->fetchAll();
}

// Handle enrollment
if (isLoggedIn() && !$isEnrolled && isset($_POST['enroll'])) {
    $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $courseId])) {
        $isEnrolled = true;
        $success = "Successfully enrolled in the course!";
    } else {
        $error = "Failed to enroll in the course. Please try again.";
    }
}

include 'includes/header.php';
?>

<style>
    /* ===== Udemy-Inspired Course Details ===== */
    .course-hero {
        position: relative;
        background: linear-gradient(135deg, #401b9c, #5624d0);
        color: white;
        padding: 3rem 1rem;
        border-radius: 12px;
    }

    .course-hero h1 {
        font-weight: 700;
        font-size: 2rem;
    }

    .course-hero p {
        color: #ddd;
    }

    .course-meta i {
        color: #cfc9ff;
        margin-right: 5px;
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }

    .accordion-button {
        font-weight: 600;
    }

    .accordion-button:focus {
        box-shadow: none;
    }

    .list-group-item {
        border: none;
        border-bottom: 1px solid #eee;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }

    .list-group-item .btn {
        font-size: 0.85rem;
    }

    .badge {
        font-size: 0.8rem;
    }

    .instructor-section img {
        border: 3px solid #eee;
    }

    .sidebar {
        position: sticky;
        top: 90px;
    }

    .btn-primary {
        background-color: #5624d0;
        border: none;
    }

    .btn-primary:hover {
        background-color: #401b9c;
    }

    .course-includes li {
        margin-bottom: 0.5rem;
    }
</style>

<div class="container mt-4 mb-5">

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- ===== Course Hero Section ===== -->
    <div class="course-hero mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
                <div class="course-meta d-flex flex-wrap gap-3 mt-3">
                    <span><i class="fas fa-signal"></i> <?php echo ucfirst($course['level']); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $course['duration']; ?> hours</span>
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <?php if ($course['thumbnail']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>"
                        class="rounded shadow-sm img-fluid" alt="Course Thumbnail"
                        style="max-height: 250px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-white text-dark py-5 rounded">No Image</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== Main Content ===== -->
    <div class="row">
        <!-- Left: Course Content + Instructor -->
        <div class="col-md-8">

            <!-- Course Content -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-list-ul me-2"></i>Course Content</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <p class="text-muted">No content available yet.</p>
                    <?php else: ?>
                        <div class="accordion" id="courseAccordion">
                            <?php foreach ($modules as $index => $module): ?>
                                <div class="accordion-item mb-2 border-0 shadow-sm">
                                    <h2 class="accordion-header" id="heading<?php echo $module['id']; ?>">
                                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse<?php echo $module['id']; ?>">
                                            <?php echo htmlspecialchars($module['title']); ?>
                                            <span class="badge bg-secondary ms-2"><?php echo $module['lesson_count']; ?>
                                                lessons</span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $module['id']; ?>"
                                        class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                        data-bs-parent="#courseAccordion">
                                        <div class="accordion-body">
                                            <p class="text-muted small"><?php echo htmlspecialchars($module['description']); ?>
                                            </p>
                                            <ul class="list-group">
                                                <?php foreach ($module['lessons'] as $lesson): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-play-circle text-primary me-2"></i>
                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                            <?php if ($lesson['duration'] > 0): ?>
                                                                <small class="text-muted">(<?php echo $lesson['duration']; ?>
                                                                    min)</small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($isEnrolled): ?>
                                                            <a href="#" class="btn btn-sm btn-outline-primary">Start</a>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Locked</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instructor Section -->
            <div class="card instructor-section">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>About the Instructor
                    </h4>
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <?php if (!empty($course['profile_picture'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($course['profile_picture']); ?>" alt="Instructor"
                                class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-4x text-primary me-3"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">
                            <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></h5>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars($course['instructor_bio'] ?: 'No bio available.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Sidebar -->
        <div class="col-md-4">
            <div class="card sidebar p-3">
                <div class="text-center mb-3">
                    <?php if ($course['price'] > 0): ?>
                        <h3 class="text-success">$<?php echo number_format($course['price'], 2); ?></h3>
                    <?php else: ?>
                        <h3 class="text-success">Free</h3>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <?php if ($isEnrolled): ?>
                        <button class="btn btn-success" disabled><i class="fas fa-check-circle me-2"></i>Enrolled</button>
                        <a href="#" class="btn btn-primary"><i class="fas fa-play me-2"></i>Continue Learning</a>
                    <?php else: ?>
                        <?php if (isLoggedIn()): ?>
                            <form method="POST">
                                <button type="submit" name="enroll" class="btn btn-primary w-100">
                                    <?php echo $course['price'] > 0 ? 'Enroll Now' : 'Enroll for Free'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary w-100">Login to Enroll</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <hr>

                <h6 class="fw-bold mb-3">This course includes:</h6>
                <ul class="list-unstyled course-includes text-muted">
                    <li><i class="fas fa-list-ul text-primary me-2"></i> <?php echo count($modules); ?> modules</li>
                    <li><i class="fas fa-clock text-primary me-2"></i> <?php echo $course['duration']; ?> hours total
                    </li>
                    <li><i class="fas fa-certificate text-primary me-2"></i> Certificate of completion</li>
                    <li><i class="fas fa-infinity text-primary me-2"></i> Lifetime access</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>