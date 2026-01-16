<?php
$pageTitle = "Course Details";
require_once 'includes/config.php';
require_once 'includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id'])) {
    redirect('courses.php');
}

$courseId = (int) $_GET['id'];
global $pdo;

// Get course details
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        i.first_name,
        i.last_name,
        i.bio AS instructor_bio,
        i.profile_picture
    FROM courses c
    JOIN instructors i ON c.instructor_id = i.id
    WHERE c.id = ?
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses.php');
}

// Check if user is enrolled
$isEnrolled = false;
$currentLesson = null;
$videoUrl = null;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    $isEnrolled = $stmt->fetch() ? true : false;

    if ($isEnrolled) {
        // Default current lesson = first lesson of this course
        $stmt = $pdo->prepare("
            SELECT l.*
            FROM lessons l
            JOIN course_modules cm ON l.module_id = cm.id
            WHERE cm.course_id = ?
            ORDER BY cm.sort_order, l.sort_order, l.id
            LIMIT 1
        ");
        $stmt->execute([$courseId]);
        $currentLesson = $stmt->fetch();
        if ($currentLesson && !empty($currentLesson['video_path'])) {
            $videoUrl = $currentLesson['video_path'];
        }
    }
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

foreach ($modules as $index => $module) {
    $lessonsStmt = $pdo->prepare("
        SELECT * FROM lessons 
        WHERE module_id = ? 
        ORDER BY sort_order, id
    ");
    $lessonsStmt->execute([$module['id']]);
    $modules[$index]['lessons'] = $lessonsStmt->fetchAll();
}

// Add materials to each lesson
foreach ($modules as $index => $module) {
    foreach ($module['lessons'] as $lessonIndex => $lesson) {
        $matStmt = $pdo->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY id DESC");
        $matStmt->execute([$lesson['id']]);
        $modules[$index]['lessons'][$lessonIndex]['materials'] = $matStmt->fetchAll();
    }
}

// Course progress + per-lesson progress
$courseProgress = 0;
$lessonProgressMap = [];

if (isLoggedIn()) {
    // Total lessons in this course
    $totalLessonsStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lessons l
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE cm.course_id = ?
    ");
    $totalLessonsStmt->execute([$courseId]);
    $totalLessons = (int) $totalLessonsStmt->fetchColumn();

    // Per-lesson progress for this user in this course
    $progressStmt = $pdo->prepare("
        SELECT up.lesson_id, up.completed, up.watched_percent
        FROM user_progress up
        JOIN lessons l ON up.lesson_id = l.id
        JOIN course_modules cm ON l.module_id = cm.id
        WHERE up.user_id = ?
          AND cm.course_id = ?
    ");
    $progressStmt->execute([$_SESSION['user_id'], $courseId]);
    while ($row = $progressStmt->fetch(PDO::FETCH_ASSOC)) {
        $lessonProgressMap[$row['lesson_id']] = $row;
    }

    $completedLessons = 0;
    $completedLessonIds = [];

    foreach ($lessonProgressMap as $lessonId => $lp) {
        if ((int) $lp['completed'] === 1) {
            $completedLessons++;
            $completedLessonIds[] = (int) $lessonId;
        }
    }

    if ($totalLessons > 0) {
        $courseProgress = (int) round($completedLessons * 100 / $totalLessons);
        $courseCompleted = ($courseProgress === 100); // <-- add this
    } else {
        $courseCompleted = false;
    }

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

<head>
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
</head>

<style>
    :root {
        --primary-start: #0f62ff;
        --primary-end: #2d9eff;
        --primary-deep: #0043ce;
        --accent: #ffb545;
        --bg-page: #f3f6ff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-soft: rgba(148, 163, 184, 0.25);
        --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.16);
        --radius-lg: 16px;
        --radius-md: 12px;
    }

    body {
        background: radial-gradient(circle at top left, #e0ebff 0, #f3f6ff 40%, #f9fafb 100%);
        font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: var(--text-main);
    }

    .course-hero {
        background: linear-gradient(90deg, var(--primary-start), var(--primary-end));
        color: #fff;
        border-radius: var(--radius-lg);
        padding: 1.6rem 1.8rem;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
    }

    .course-hero h1 {
        font-weight: 700;
        font-size: 1.9rem;
        letter-spacing: 0.02em;
        margin-bottom: .35rem;
    }

    .course-hero p {
        color: #e5edff;
        margin-bottom: 0.4rem;
    }

    .course-meta span {
        background: rgba(15, 23, 42, 0.25);
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.85rem;
        margin-right: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .course-meta i {
        font-size: 0.85rem;
    }

    .course-hero-thumbnail {
        border-radius: var(--radius-md);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.35);
    }

    .card {
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--border-soft);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(12px);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 55px rgba(15, 23, 42, 0.12);
        border-color: rgba(37, 99, 235, 0.35);
    }

    .card-header {
        background: linear-gradient(90deg, rgba(15, 23, 42, 0.02), rgba(148, 163, 184, 0.09));
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: var(--radius-md) var(--radius-md) 0 0 !important;
        font-weight: 600;
        padding: 0.9rem 1.2rem;
        color: var(--text-main);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-header i {
        color: #2563eb;
    }

    .card-body {
        padding: 1.1rem 1.2rem 1.3rem;
    }

    .btn-primary {
        background: linear-gradient(120deg, var(--primary-start), var(--primary-deep));
        border: none;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.7rem 1.4rem;
        letter-spacing: 0.02em;
        box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .btn-primary:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
        box-shadow: 0 16px 35px rgba(37, 99, 235, 0.45);
    }

    .btn-success {
        background: linear-gradient(120deg, #16a34a, #15803d);
        border: none;
        border-radius: 999px;
        font-weight: 600;
        box-shadow: 0 10px 22px rgba(22, 163, 74, 0.35);
    }

    .btn-outline-primary {
        border-radius: 999px;
        border-width: 1.6px;
        font-weight: 500;
    }

    .accordion-button {
        font-weight: 600;
        color: #0f172a;
        border-radius: 10px !important;
        background: transparent;
        padding: 0.75rem 1rem;
    }

    .accordion-button:not(.collapsed) {
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.10), rgba(37, 99, 235, 0.02));
        color: #1d4ed8;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.18);
    }

    .accordion-button:focus {
        box-shadow: none;
    }

    .accordion-body {
        background: transparent;
        padding-top: 0.75rem;
    }

    .badge.bg-secondary {
        background-color: rgba(191, 219, 254, 0.85) !important;
        color: #1e40af !important;
        border-radius: 999px;
        font-size: 0.75rem;
        padding: 0.25rem 0.65rem;
    }

    .list-group-item {
        border: none;
        border-bottom: 1px dashed rgba(148, 163, 184, 0.4);
        cursor: pointer;
        padding: 0.7rem 0.35rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: transparent;
        border-radius: 8px;
        transition: background-color .14s ease, transform .12s ease;
    }

    .list-group-item:hover {
        background: rgba(239, 246, 255, 0.9);
        transform: translateY(-1px);
    }

    .active-lesson {
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.12), rgba(191, 219, 254, 0.6));
        border-left: 4px solid var(--primary-start);
        font-weight: 600;
    }

    .list-group-item i {
        color: #2563eb;
        margin-right: .4rem;
    }

    .video-card .card-header {
        border-bottom: 1px solid rgba(15, 23, 42, 0.16);
    }

    .video-player-container {
        background: radial-gradient(circle at top, #020617 0, #020617 40%, #020617 100%);
        border-radius: var(--radius-md);
        overflow: hidden;
        height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        border: 1px solid rgba(15, 23, 42, 0.6);
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.6);
    }

    .video-player {
        width: 100%;
        height: 100%;
        object-fit: cover;
        background-color: #000;
    }

    .locked-video {
        color: var(--text-muted);
        text-align: center;
        padding: 2.3rem 1.2rem;
        font-size: 0.95rem;
    }

    .sidebar {
        position: sticky;
        top: 88px;
        border-radius: var(--radius-lg);
        background: radial-gradient(circle at top, #0f172a 0, #020617 55%);
        color: #e5e7eb;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(148, 163, 184, 0.45);
        padding: 1.4rem 1.4rem 1.6rem;
    }

    .sidebar .course-includes li {
        margin-bottom: 0.55rem;
        font-size: 0.9rem;
        color: #cbd5f5;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .sidebar .course-includes i {
        color: #60a5fa;
    }

    .price-tag {
        background: rgba(15, 23, 42, 0.9);
        color: #e5edff;
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .price-tag i {
        color: var(--accent);
    }

    /* Instructor avatar */
    .instructor-section img {
        border: 3px solid #60a5fa;
        border-radius: 999px;
        width: 80px;
        height: 80px;
        object-fit: cover;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.35);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .instructor-section img:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.48);
    }

    /* mini lesson bars */
    .lesson-progress {
        height: 3px;
        border-radius: 999px;
        background-color: rgba(148, 163, 184, 0.25);
        overflow: hidden;
        max-width: 180px;
    }

    .lesson-materials .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.4rem;
    }

    /* ADD height here */
    .lesson-progress .progress-bar {
        background-color: blue;
        height: 3px;
        /* <= important */
        border-radius: 999px;
    }

    .preview-material:hover {
        background-color: #0dcaf0 !important;
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(13, 202, 240, 0.4);
    }

    .modal iframe {
        border-radius: 0 0 12px 12px;
    }

    @media (max-width: 991.98px) {
        .course-hero {
            padding: 1.4rem 1.2rem;
        }

        .video-player-container {
            height: 260px;
        }
    }
</style>

<div class="container mt-4 mb-5">

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- HERO -->
    <div class="course-hero mb-4">
        <div class="row align-items-center w-100">
            <div class="col-lg-8 col-md-7">
                <h1><?= htmlspecialchars($course['title']); ?></h1>
                <p class="lead mb-1"><?= htmlspecialchars($course['description']); ?></p>

                <div class="course-meta mt-2">
                    <span><i class="fas fa-signal"></i><?= ucfirst($course['level']); ?></span>
                    <span><i class="fas fa-clock"></i><?= $course['duration']; ?> hours</span>
                    <span><i class="fas fa-tag"></i><?= htmlspecialchars($course['category']); ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-5 text-md-end text-center mt-3 mt-md-0">
                <?php if ($course['thumbnail']): ?>
                    <div class="course-hero-thumbnail d-inline-block">
                        <img src="uploads/<?= htmlspecialchars($course['thumbnail']); ?>" class="img-fluid"
                            alt="Course Thumbnail" style="max-height: 230px; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <div class="bg-white text-dark py-5 px-4 rounded">No Image</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="row g-4">

        <!-- LEFT: VIDEO + CONTENT + INSTRUCTOR -->
        <div class="col-lg-8">

            <?php if ($isEnrolled): ?>
                <!-- VIDEO PANEL -->
                <div class="card video-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-play-circle text-primary me-2"></i>
                            <?= $currentLesson ? htmlspecialchars($currentLesson['title']) : 'Watch Lesson'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="video-player-container" id="videoContainer">
                            <?php if ($videoUrl): ?>
                                <video class="video-player" controls preload="metadata">
                                    <source src="<?= htmlspecialchars($videoUrl); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <div class="locked-video">Select a lesson from the course content below.</div>
                            <?php endif; ?>
                        </div>
                        <div id="quizContainer" class="mt-4"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- COURSE CONTENT -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ul text-primary me-2"></i>Course Content
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <p class="text-muted">No lessons added yet.</p>
                    <?php else: ?>
                        <div class="accordion" id="courseAccordion">
                            <?php foreach ($modules as $index => $module): ?>
                                <div class="accordion-item mb-2 border-0 shadow-sm">
                                    <h2 class="accordion-header" id="heading<?= $module['id']; ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : ''; ?>" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapse<?= $module['id']; ?>">
                                            <?= htmlspecialchars($module['title']); ?>
                                            <span class="badge bg-secondary ms-2">
                                                <?= $module['lesson_count']; ?> lessons
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $module['id']; ?>"
                                        class="accordion-collapse collapse <?= $index === 0 ? 'show' : ''; ?>"
                                        data-bs-parent="#courseAccordion">
                                        <div class="accordion-body">
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($module['lessons'] as $lesson): ?>
                                                    <?php
                                                    $path = trim($lesson['video_path'] ?? '');
                                                    $hasVideo = $path !== '';
                                                    $isCurrent = $currentLesson && $lesson['id'] == $currentLesson['id'];

                                                    $info = $lessonProgressMap[$lesson['id']] ?? null;
                                                    $isCompleted = $info && (int) $info['completed'] === 1;
                                                    $percent = $info ? (int) $info['watched_percent'] : 0;

                                                    // sequential locking
                                                    $isLocked = false;
                                                    if ($isEnrolled && $hasVideo) {
                                                        $prevStmt = $pdo->prepare("
            SELECT l2.id
            FROM lessons l2
            JOIN course_modules cm2 ON l2.module_id = cm2.id
            WHERE cm2.course_id = :course_id
              AND (cm2.sort_order < :mod_order
                   OR (cm2.sort_order = :mod_order AND (l2.sort_order < :lesson_order OR (l2.sort_order = :lesson_order AND l2.id < :lesson_id))))
            ORDER BY cm2.sort_order DESC, l2.sort_order DESC, l2.id DESC
            LIMIT 1
        ");
                                                        $prevStmt->execute([
                                                            ':course_id' => $courseId,
                                                            ':mod_order' => $module['sort_order'],
                                                            ':lesson_order' => $lesson['sort_order'],
                                                            ':lesson_id' => $lesson['id']
                                                        ]);
                                                        $prevId = (int) $prevStmt->fetchColumn();
                                                        if ($prevId !== 0 && !in_array($prevId, $completedLessonIds, true)) {
                                                            $isLocked = true;
                                                        }
                                                    }
                                                    ?>
                                                    <li
                                                        class="list-group-item <?= $isEnrolled && $isCurrent ? 'active-lesson' : ''; ?>">
                                                        <div>

                                                            <?= htmlspecialchars($lesson['title']); ?>
                                                            <?php if ($lesson['duration'] > 0): ?>
                                                                <small class="text-muted">(<?= $lesson['duration']; ?> min)</small>
                                                            <?php endif; ?>

                                                            <?php if ($isEnrolled): ?>
                                                                <div class="lesson-progress mt-1">
                                                                    <div class="progress-bar" style="width: <?= $percent; ?>%;"></div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($isEnrolled && !empty($lesson['materials'])): ?>
                                                            <div class="lesson-materials mt-1 mb-2">
                                                                <?php foreach ($lesson['materials'] as $mat): ?>
                                                                    <a href="uploads/<?= htmlspecialchars($mat['file_path']); ?>"
                                                                        target="_blank"
                                                                        class="badge bg-info text-dark me-1 mb-1 text-decoration-none d-inline-flex align-items-center"
                                                                        title="<?= htmlspecialchars($mat['original_name']); ?>">
                                                                        <i class="fas fa-file-alt me-1"></i>
                                                                        <?= htmlspecialchars(substr($mat['original_name'], 0, 15)); ?>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>


                                                        <?php if ($isEnrolled && $hasVideo): ?>
                                                            <?php if ($isLocked): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                    <i class="fas fa-lock me-1"></i>Locked
                                                                </button>
                                                            <?php else: ?>
                                                                <button
                                                                    class="btn btn-sm <?= $isCompleted ? 'btn-success' : 'btn-outline-primary'; ?> load-lesson"
                                                                    data-lesson-id="<?= $lesson['id']; ?>"
                                                                    data-course-id="<?= $courseId; ?>"
                                                                    data-video-url="uploads/<?= htmlspecialchars($path); ?>"
                                                                    data-lesson-title="<?= htmlspecialchars($lesson['title']); ?>">
                                                                    <?= $isCompleted ? 'Completed ✅' : 'Play'; ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><i
                                                                    class="fas fa-lock me-1"></i>Locked</span>
                                                        <?php endif; ?>
                                                        <?php if ($isEnrolled && !$isLocked && !empty($lesson['materials'])): ?>
                                                            <button class="btn btn-sm btn-info mt-2 generate-diagram"
                                                                data-lesson-id="<?= $lesson['id']; ?>">
                                                                <i class="fas fa-project-diagram me-1"></i> Visualize Material
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($isEnrolled && !$isLocked): ?>
                                                            <?php
                                                            // ✅ Check if this lesson has any quiz questions
                                                            $quizCheck = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE lesson_id = ?");
                                                            $quizCheck->execute([$lesson['id']]);
                                                            $hasQuiz = $quizCheck->fetchColumn() > 0;
                                                            ?>

                                                            <?php if ($hasQuiz): ?>
                                                                <div class="quiz-subsection mt-2 ps-3 border-start border-primary-subtle"
                                                                    id="quiz-subsection-<?= $lesson['id']; ?>">
                                                                    <button class="btn btn-sm btn-outline-info take-quiz-btn"
                                                                        data-lesson-id="<?= $lesson['id']; ?>"
                                                                        data-course-id="<?= $courseId; ?>" <?= !$isCompleted ? 'disabled title="Complete the video to unlock this quiz"' : ''; ?>>
                                                                        <i class="fas fa-question-circle me-1"></i> Take Quiz
                                                                    </button>
                                                                    <div class="quiz-container mt-2"
                                                                        id="quiz-container-<?= $lesson['id']; ?>"></div>
                                                                </div>
                                                            <?php endif; ?>
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

            <!-- INSTRUCTOR -->
            <div class="card instructor-section">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chalkboard-teacher text-primary me-2"></i>About the Instructor
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <?php if (!empty($course['profile_picture'])): ?>
                            <img src="uploads/instructors/<?= htmlspecialchars($course['profile_picture']); ?>"
                                alt="Instructor" class="me-3">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-4x text-primary me-3"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">
                            <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                        </h5>
                        <p class="mb-0">
                            <?= htmlspecialchars($course['instructor_bio'] ?: 'No bio available.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR -->
        <div class="col-lg-4">
            <div class="sidebar">
                <div class="text-center mb-3">
                    <?php if ($course['price'] > 0): ?>
                        <div class="price-tag">
                            <i class="fas fa-crown"></i>
                            ₹<?= number_format($course['price'], 2); ?>
                        </div>
                    <?php else: ?>
                        <div class="price-tag">
                            <i class="fas fa-crown"></i>
                            Free
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid mb-3">
                    <?php if ($isEnrolled): ?>
                        <button class="btn btn-success mb-2" disabled>
                            <i class="fas fa-check-circle me-2"></i>Enrolled
                        </button>
                    <?php else: ?>
                        <?php if (isLoggedIn()): ?>
                            <form method="POST">
                                <button type="submit" name="enroll" class="btn btn-primary w-100 mb-2">
                                    <?= $course['price'] > 0 ? 'Enroll Now' : 'Enroll for Free'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary w-100 mb-2">Login to Enroll</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <hr class="border-secondary border-opacity-25">

                <h6 class="fw-bold mb-3">This course includes:</h6>
                <ul class="list-unstyled course-includes">
                    <li><i class="fas fa-list-ul"></i><?= count($modules); ?> modules</li>
                    <li><i class="fas fa-clock"></i><?= $course['duration']; ?> hours total</li>
                    <li><i class="fas fa-certificate"></i>Certificate of completion</li>
                    <li><i class="fas fa-infinity"></i>Lifetime access</li>
                </ul>

                <?php if (isLoggedIn() && $isEnrolled): ?>
                    <hr class="border-secondary border-opacity-25">
                    <h6 class="fw-bold mb-2">Your progress</h6>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $courseProgress; ?>%;"
                            aria-valuenow="<?= $courseProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $courseProgress; ?>%
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($isEnrolled && $courseCompleted): ?>
                    <div class="d-grid mt-3">
                        <a href="certificate.php?course_id=<?= $courseId; ?>" target="_blank" class="btn btn-success w-100">
                            <i class="fas fa-certificate me-1"></i>Download Certificate
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<!-- Diagram Modal -->
<div class="modal fade" id="diagramModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-project-diagram me-2"></i>Lesson Concept Diagram</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="diagramContainer" class="border rounded p-3 bg-light" style="height: 500px; overflow:auto;">
                    <p class="text-center text-muted">No diagram loaded yet.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Disable right-click on videos (prevent save)
    document.addEventListener('contextmenu', function (e) {
        if (e.target.tagName === 'VIDEO') e.preventDefault();
    });

    // === AUTO DIAGRAM GENERATION ===
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.generate-diagram');
        if (!btn) return;

        const lessonId = btn.dataset.lessonId;
        const diagramModal = new bootstrap.Modal(document.getElementById('diagramModal'));
        const diagramContainer = document.getElementById('diagramContainer');
        diagramContainer.innerHTML = '<p class="text-center text-muted">Generating diagram...</p>';

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        try {
            const res = await fetch('instructor/generate_diagram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lesson_id=${encodeURIComponent(lessonId)}`
            });

            const text = await res.text();
            console.log("Server response:", text);
            const data = JSON.parse(text);

            if (data.status === 'success') {
                setTimeout(async () => {
                    try {
                        const { svg } = await mermaid.render('diagram_' + Date.now(), data.diagram);
                        diagramContainer.innerHTML = svg;
                    } catch (err) {
                        diagramContainer.innerHTML = `<p class="text-danger">⚠️ Mermaid render error: ${err.message}</p>`;
                    }
                }, 100);

                diagramModal.show();
            } else {
                diagramContainer.innerHTML = `<p class="text-danger">⚠️ ${data.message}</p>`;
                diagramModal.show();
            }
        } catch (err) {
            diagramContainer.innerHTML = `<p class="text-danger">⚠️ Error: ${err.message}</p>`;
            diagramModal.show();
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    document.addEventListener('DOMContentLoaded', function () {

        // === VIDEO HANDLER ===
        document.querySelectorAll('.load-lesson').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.videoUrl;
                const title = this.dataset.lessonTitle;
                const lessonId = this.dataset.lessonId;
                const courseId = this.dataset.courseId;

                if (!url) return;

                const container = document.getElementById('videoContainer');
                if (!container) return;

                // Load video player dynamically
                container.innerHTML = `
                    <video class="video-player" controls preload="metadata" controlsList="nodownload" id="activeVideo">
                        <source src="${url}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                `;

                // Highlight current lesson
                document.querySelectorAll('.list-group-item').forEach(li => li.classList.remove('active-lesson'));
                this.closest('.list-group-item').classList.add('active-lesson');

                // Update title
                const header = document.querySelector('.video-card .card-header h5');
                if (header) header.innerHTML = '<i class="fas fa-play-circle text-primary me-2"></i>' + title;

                const video = document.getElementById('activeVideo');
                if (!video) return;

                const button = this;
                let watchedTime = 0, lastTime = 0, maxWatched = 0, lastSentPercent = -1;
                const MIN_DELTA = 5, MIN_WATCH_THRESHOLD = 0.9, SYNC_INTERVAL = 10000;
                let lastSyncTime = 0;

                video.addEventListener('seeking', () => {
                    if (video.currentTime > maxWatched + 5) video.currentTime = maxWatched;
                });

                function sendProgress(percent) {
                    percent = Math.max(0, Math.min(100, percent));
                    if (percent === lastSentPercent) return;
                    if (percent < 100 && lastSentPercent >= 0 && (percent - lastSentPercent) < MIN_DELTA) return;
                    lastSentPercent = percent;

                    fetch('update_progress.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `lesson_id=${lessonId}&course_id=${courseId}&percent=${percent}`
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const bar = document.querySelector('.sidebar .progress-bar');
                                if (bar && typeof data.course_progress !== 'undefined') {
                                    bar.style.width = data.course_progress + '%';
                                    bar.textContent = data.course_progress + '%';
                                    bar.style.boxShadow = '0 0 10px #22c55e';
                                    setTimeout(() => bar.style.boxShadow = 'none', 800);
                                }
                            }
                        });
                }

                video.addEventListener('timeupdate', () => {
                    if (!video.duration) return;
                    if (!video.seeking && video.currentTime > lastTime) {
                        const diff = video.currentTime - lastTime;
                        watchedTime += diff;
                        maxWatched = Math.max(maxWatched, video.currentTime);
                    }
                    lastTime = video.currentTime;

                    const percentWatched = Math.min((watchedTime / video.duration) * 100, 100);
                    const rounded = Math.round(percentWatched);
                    const mini = button.closest('.list-group-item').querySelector('.lesson-progress .progress-bar');
                    if (mini) mini.style.width = rounded + '%';

                    const now = Date.now();
                    if (Math.abs(rounded - lastSentPercent) >= MIN_DELTA || now - lastSyncTime > SYNC_INTERVAL) {
                        lastSyncTime = now;
                        sendProgress(rounded);
                    }

                    if (percentWatched >= MIN_WATCH_THRESHOLD * 100 && !button.classList.contains('btn-success')) {
                        button.innerHTML = 'Completed ✅';
                        button.classList.replace('btn-outline-primary', 'btn-success');

                        const quizBtn = document.querySelector(`#quiz-subsection-${lessonId} .take-quiz-btn`);
                        if (quizBtn) {
                            quizBtn.disabled = false;
                            quizBtn.title = "Now you can take this quiz!";
                            quizBtn.classList.replace('btn-outline-info', 'btn-info');
                        }

                        const nextLi = button.closest('li').nextElementSibling;
                        if (nextLi) {
                            const nextBtn = nextLi.querySelector('.load-lesson');
                            if (nextBtn) nextBtn.removeAttribute('disabled');
                        }
                    }
                });

                video.addEventListener('ended', () => sendProgress(100));
            });
        });

        // === QUIZ HANDLER ===
        document.querySelectorAll('.take-quiz-btn').forEach(function (quizBtn) {
            quizBtn.addEventListener('click', async function () {
                const lessonId = this.dataset.lessonId;
                const courseId = this.dataset.courseId;
                const container = document.getElementById(`quiz-container-${lessonId}`);
                if (!container) return;

                container.innerHTML = "<div class='text-center py-2 text-muted'>Loading quiz...</div>";

                fetch('fetch_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `lesson_id=${lessonId}`
                })
                    .then(res => res.text())
                    .then(async html => {
                        container.innerHTML = html;

                        // === QUIZ RESTRICTION LOIGC START ===
                        let quizActive = true;
                        let lockTimer = null;

                        const lockKey = `quiz_lock_${lessonId}`;
                        const lockData = JSON.parse(localStorage.getItem(lockKey) || 'null');

                        // Countdown display
                        function startCountdown(lockUntil) {
                            const countdownDiv = document.createElement('div');
                            countdownDiv.className = 'alert alert-danger text-center mt-3 fw-semibold';
                            container.innerHTML = '';
                            container.appendChild(countdownDiv);

                            function updateCountdown() {
                                const remainingMs = lockUntil - Date.now();
                                if (remainingMs <= 0) {
                                    localStorage.removeItem(lockKey);
                                    countdownDiv.innerHTML = `
                <div class='alert alert-success text-center'>
                    ✅ Lock expired.<br> Refresh the page to retry the quiz.
                </div>`;
                                    clearInterval(lockTimer);
                                    return;
                                }

                                const hours = Math.floor(remainingMs / 3600000);
                                const mins = Math.floor((remainingMs % 3600000) / 60000);
                                const secs = Math.floor((remainingMs % 60000) / 1000);
                                countdownDiv.innerHTML = `
            <strong>🚫 Quiz Locked!</strong><br>
            You can retry this quiz in <span class="text-warning">${hours}h ${mins}m ${secs}s</span>.
        `;
                            }

                            updateCountdown();
                            lockTimer = setInterval(updateCountdown, 1000);
                        }

                        // If already locked
                        if (lockData && Date.now() < lockData.until) {
                            startCountdown(lockData.until);
                            return;
                        }

                        // Request fullscreen
                        try {
                            await document.documentElement.requestFullscreen();
                        } catch (err) {
                            console.warn("Fullscreen not supported:", err);
                        }

                        function lockQuiz(reason) {
                            if (!quizActive) return;
                            quizActive = false;

                            const lockUntil = Date.now() + 2 * 60 * 60 * 1000; // 2 hours
                            localStorage.setItem(lockKey, JSON.stringify({ until: lockUntil }));

                            alert(`❌ ${reason}\nYour quiz is now locked for 2 hours.`);
                            startCountdown(lockUntil);

                            removeRestrictions();
                        }

                        function handleBlur() {
                            if (quizActive) lockQuiz("Tab switch or window unfocus detected");
                        }

                        function handleFullscreenChange() {
                            if (quizActive && !document.fullscreenElement) {
                                lockQuiz("Exited fullscreen mode");
                            }
                        }

                        function removeRestrictions() {
                            window.removeEventListener('blur', handleBlur);
                            document.removeEventListener('fullscreenchange', handleFullscreenChange);
                            if (document.fullscreenElement) document.exitFullscreen();
                        }

                        window.addEventListener('blur', handleBlur);
                        document.addEventListener('fullscreenchange', handleFullscreenChange);

                        // === QUIZ RESTRICTION LOIGC END ===

                        const submitBtn = container.querySelector('#submitQuiz');
                        if (submitBtn) {
                            submitBtn.addEventListener('click', function () {
                                const form = container.querySelector('#quizForm');
                                if (!form) return;
                                const formData = new FormData(form);
                                formData.append('lesson_id', lessonId);

                                fetch('submit_quiz.php', { method: 'POST', body: formData })
                                    .then(res => res.json())
                                    .then(data => {
                                        removeRestrictions(); // ✅ Disable all restrictions after quiz submission
                                        const resultDiv = container.querySelector('#quizResult');
                                        if (data.status === 'passed') {
                                            resultDiv.innerHTML = `
                                                <div class='alert alert-success text-center mb-0'>
                                                    ✅ You passed! Score: ${data.score}% (${data.correct}/${data.total})
                                                </div>`;

                                            const bar = document.querySelector('.sidebar .progress-bar');
                                            if (bar && typeof data.course_progress !== 'undefined') {
                                                bar.style.width = data.course_progress + '%';
                                                bar.textContent = data.course_progress + '%';
                                            }

                                        } else if (data.status === 'failed') {
                                            resultDiv.innerHTML = `
                                                <div class='alert alert-warning text-center mb-0'>
                                                    ❌ You scored ${data.score}% (${data.correct}/${data.total}). Try again.
                                                </div>`;
                                        } else {
                                            resultDiv.innerHTML = `<div class='alert alert-danger text-center'>${data.message || 'Error'}</div>`;
                                        }
                                    })
                                    .catch(() => container.querySelector('#quizResult').innerHTML = "<div class='text-danger'>Error submitting quiz.</div>");
                            });
                        }
                    })
                    .catch(() => container.innerHTML = "<div class='text-danger'>Failed to load quiz.</div>");
            });
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({
        startOnLoad: false,
        theme: 'forest',
        securityLevel: 'loose'
    });
</script>

<?php include 'includes/footer.php'; ?>