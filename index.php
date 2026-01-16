<?php
$pageTitle = "Home";
require_once 'includes/config.php';
require_once 'includes/auth.php';

include 'includes/header.php';

global $pdo;

// Get featured courses (latest published)
$featuredStmt = $pdo->query("
    SELECT c.*, i.first_name, i.last_name
    FROM courses c
    JOIN instructors i ON c.instructor_id = i.id
    WHERE c.duration >= 1
    ORDER BY c.created_at DESC
    LIMIT 6
");
$featuredCourses = $featuredStmt->fetchAll();

// Get statistics
$totalCoursesStmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE duration >= 1");
$totalCourses = $totalCoursesStmt->fetchColumn();

$totalStudentsStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalStudents = $totalStudentsStmt->fetchColumn();

$totalInstructorsStmt = $pdo->query("SELECT COUNT(*) FROM instructors");
$totalInstructors = $totalInstructorsStmt->fetchColumn();

$totalEnrollmentsStmt = $pdo->query("SELECT COUNT(*) FROM enrollments");
$totalEnrollments = $totalEnrollmentsStmt->fetchColumn();

?>

<!-- HERO -->
<section class="hero-section bg-gradient-primary text-white py-5 position-relative overflow-hidden">
    <div class="container position-relative z-1">
        <div class="row align-items-center min-vh-80">
            <!-- Left: Text -->
            <div class="col-lg-6 mb-5 mb-lg-0">
                <span class="badge bg-light text-primary mb-4 px-4 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="fas fa-rocket me-2"></i>Transform Your Career Today
                </span>

                <h1 class="display-3 fw-bolder mb-4">
                    Learn Without <span class="text-warning">Limits</span>
                </h1>

                <p class="lead mb-4 fs-5">
                    Start, switch, or advance your career with thousands of courses, professional
                    certificates, and degrees from world-class universities and companies.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-light btn-lg px-4 py-3 fw-semibold shadow-lg hover-lift">
                            <i class="fas fa-user-plus me-2"></i>Join For Free
                        </a>
                        <a href="courses.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold hover-lift">
                            <i class="fas fa-search me-2"></i>Browse Courses
                        </a>
                    <?php else: ?>
                        <a href="courses.php" class="btn btn-light btn-lg px-4 py-3 fw-semibold shadow-lg hover-lift">
                            <i class="fas fa-play-circle me-2"></i>Continue Learning
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold hover-lift">
                            <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                        </a>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap gap-4 small fw-medium">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shield-alt me-2 text-warning"></i>
                        Trusted by 50,000+ students
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-globe me-2 text-warning"></i>
                        190+ countries
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        24/7 access
                    </div>
                </div>
            </div>

            <!-- Right: Image + floating icons + rating -->
            <div class="col-lg-6">
                <div class="hero-visual text-center position-relative">
                    <div class="floating-elements">
                        <div class="floating-card card-1">
                            <i class="fas fa-graduation-cap text-primary fs-5"></i>
                        </div>
                        <div class="floating-card card-2">
                            <i class="fas fa-trophy text-warning fs-5"></i>
                        </div>
                        <div class="floating-card card-3">
                            <i class="fas fa-cogs text-success fs-5"></i>
                        </div>
                    </div>

                    <img src="https://cdn.pixabay.com/photo/2016/11/19/14/00/code-1839406_1280.jpg"
                        alt="Online Learning" class="img-fluid hero-main-img">

                    <div class="achievement-badge">
                        <i class="fas fa-star"></i>
                        <span>4.8/5 Rating</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATISTICS -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3 col-6">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-primary text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h2 class="display-5 fw-bold text-primary counter" data-count="<?php echo $totalCourses; ?>">0</h2>
                    <p class="text-muted fw-semibold mb-0">Online Courses</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-success text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="display-5 fw-bold text-success counter" data-count="<?php echo $totalStudents; ?>">0</h2>
                    <p class="text-muted fw-semibold mb-0">Active Students</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-info text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h2 class="display-5 fw-bold text-info counter" data-count="<?php echo $totalInstructors; ?>">0</h2>
                    <p class="text-muted fw-semibold mb-0">Expert Instructors</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-warning text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h2 class="display-5 fw-bold text-warning counter" data-count="<?php echo $totalEnrollments; ?>">0
                    </h2>
                    <p class="text-muted fw-semibold mb-0">Course Enrollments</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-md-12">
                <span class="badge bg-primary mb-3 px-3 py-2 rounded-pill">Why Choose Us</span>
                <h2 class="fw-bold display-5">Why Choose Edutech LMS?</h2>
                <p class="text-muted lead fs-5 mb-0">Experience a modern, blue‑themed learning platform designed for
                    your success.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card border-0 text-center h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon-wrapper mx-auto mb-4">
                            <div class="feature-icon bg-primary text-white rounded-3">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Learn Anywhere</h5>
                        <p class="text-muted mb-0">Access courses on any device, at any time, with lifetime access to
                            high‑quality lessons.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card border-0 text-center h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon-wrapper mx-auto mb-4">
                            <div class="feature-icon bg-success text-white rounded-3">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Expert Instructors</h5>
                        <p class="text-muted mb-0">Learn from practitioners who teach modern, production‑ready skills
                            and workflows.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card border-0 text-center h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon-wrapper mx-auto mb-4">
                            <div class="feature-icon bg-info text-white rounded-3">
                                <i class="fas fa-certificate"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Get Certified</h5>
                        <p class="text-muted mb-0">Earn shareable certificates that help you stand out in applications
                            and promotions.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card border-0 text-center h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon-wrapper mx-auto mb-4">
                            <div class="feature-icon bg-warning text-white rounded-3">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Hands‑on Learning</h5>
                        <p class="text-muted mb-0">Build real projects and a strong portfolio that proves your expertise
                            to employers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BECOME INSTRUCTOR -->
<section class="py-5 bg-gradient-instructor text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill">Share Your Knowledge</span>
                <h2 class="fw-bold display-5 mb-4">Become an Instructor</h2>
                <p class="lead mb-4">
                    Join our global instructor community and teach learners worldwide on a modern, blue‑themed LMS.
                </p>
                <div class="instructor-benefits mb-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-dollar-sign text-warning me-3 fs-5"></i>
                                <span>Earn money teaching</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-warning me-3 fs-5"></i>
                                <span>Reach global audience</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-line text-warning me-3 fs-5"></i>
                                <span>Flexible schedule</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-tools text-warning me-3 fs-5"></i>
                                <span>Powerful teaching tools</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="request_instructor.php" class="btn btn-light btn-lg px-4 py-3 fw-semibold">
                        <i class="fas fa-user-plus me-2"></i>Start Teaching Today
                    </a>
                    <a href="request_instructor.php" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="instructor-visual mt-4 mt-lg-0 position-relative">
                    <div class="instructor-stats-card">
                        <div class="stats-content">
                            <h4 class="text-primary mb-1">₹5,00,000+</h4>
                            <small>Average Yearly Earnings</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED COURSES -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5 align-items-center">
            <div class="col-md-8">
                <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill">Popular Courses</span>
                <h2 class="fw-bold display-5">Featured Courses</h2>
                <p class="text-muted fs-5 mb-0">Discover the latest and most loved courses from our community.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="courses.php" class="btn btn-primary btn-lg px-4 py-3 fw-semibold hover-lift">
                    <i class="fas fa-arrow-right me-2"></i>View All Courses
                </a>
            </div>
        </div>
        <div class="row g-4">
            <?php if (empty($featuredCourses)): ?>
                <div class="col-md-12">
                    <div class="card border-0 text-center py-5 course-card">
                        <div class="card-body">
                            <i class="fas fa-book fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No courses available yet</h4>
                            <p class="text-muted mb-0">Check back soon for new course offerings!</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($featuredCourses as $course): ?>
                    <?php
                    $isFavorite = false;
                    if (isLoggedIn()) {
                        $userId = $_SESSION['user_id'];
                        $checkFav = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND course_id = ?");
                        $checkFav->execute([$userId, $course['id']]);
                        $isFavorite = $checkFav->fetchColumn();
                    }
                    ?>

                    <div class="col-xl-4 col-md-6">
                        <div class="card course-card border-0 h-100 hover-lift">
                            <div class="course-image-wrapper position-relative">
                                <?php if ($course['thumbnail']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>"
                                        class="card-img-top course-image" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <?php else: ?>
                                    <div
                                        class="card-img-top course-image-placeholder bg-gradient-primary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-book fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="course-badges position-absolute top-0 start-0 p-3">
                                    <span class="badge bg-primary"><?php echo ucfirst($course['level']); ?></span>
                                    <?php if ($course['category']): ?>
                                        <span
                                            class="badge bg-secondary ms-1"><?php echo htmlspecialchars($course['category']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="course-hover-actions position-absolute top-0 end-0 p-3">
                                    <button class="favorite-btn <?= $isFavorite ? 'favorited' : '' ?>"
                                        data-course-id="<?= $course['id']; ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                                        <i class="<?= $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column p-4">
                                <div class="course-instructor mb-2">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                    </small>
                                </div>
                                <h5 class="card-title fw-bold line-clamp-2">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h5>
                                <p class="card-text text-muted line-clamp-2 flex-grow-1 mb-3">
                                    <?php echo htmlspecialchars(mb_strimwidth($course['description'], 0, 120, '...')); ?>
                                </p>
                                <div class="course-meta mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <small class="text-muted"><?php echo $course['duration']; ?> hours</small>
                                        </div>
                                        <div class="course-rating">
                                            <i class="fas fa-star text-warning small"></i>
                                            <span class="text-muted small">4.8</span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($course['price'] > 0): ?>
                                            <span class="h5 mb-0 text-success fw-bold">
                                                ₹<?php echo number_format($course['price']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="h5 mb-0 text-success fw-bold">Free</span>
                                        <?php endif; ?>
                                        <a href="course-detail.php?id=<?php echo $course['id']; ?>"
                                            class="btn btn-primary btn-sm px-3">
                                            Enroll Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill">Explore Categories</span>
                <h2 class="fw-bold display-5">Popular Categories</h2>
                <p class="text-muted lead fs-5 mb-0">Browse by category and follow a focused learning path.</p>
            </div>
        </div>
        <div class="row g-3">
            <?php
            $categories = [
                ['name' => 'Web Development', 'icon' => 'code', 'color' => 'primary', 'count' => 25, 'gradient' => 'gradient-primary'],
                ['name' => 'Design', 'icon' => 'paint-brush', 'color' => 'success', 'count' => 18, 'gradient' => 'gradient-success'],
                ['name' => 'Marketing', 'icon' => 'chart-line', 'color' => 'info', 'count' => 15, 'gradient' => 'gradient-info'],
                ['name' => 'Photography', 'icon' => 'camera', 'color' => 'warning', 'count' => 12, 'gradient' => 'gradient-warning'],
                ['name' => 'Music', 'icon' => 'music', 'color' => 'danger', 'count' => 10, 'gradient' => 'gradient-danger'],
                ['name' => 'Business', 'icon' => 'briefcase', 'color' => 'secondary', 'count' => 22, 'gradient' => 'gradient-secondary'],
                ['name' => 'Health & Fitness', 'icon' => 'heartbeat', 'color' => 'primary', 'count' => 14, 'gradient' => 'gradient-primary'],
                ['name' => 'Language', 'icon' => 'language', 'color' => 'success', 'count' => 16, 'gradient' => 'gradient-success']
            ];
            foreach ($categories as $category): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card category-card border-0 text-center h-100 hover-lift">
                        <div class="card-body p-4">
                            <div class="category-icon-wrapper mx-auto mb-3">
                                <div class="category-icon <?php echo $category['gradient']; ?> text-white rounded-3">
                                    <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2"><?php echo $category['name']; ?></h6>
                            <small class="text-muted"><?php echo $category['count']; ?> Courses</small>
                            <div class="category-progress mt-3">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-<?php echo $category['color']; ?>"
                                        style="width: <?php echo min($category['count'] * 4, 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-5 bg-gradient-testimonials text-white position-relative">
    <div class="container position-relative z-1">
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <span class="badge bg-light text-primary mb-2 px-3 py-2 rounded-pill">Success Stories</span>
                <h2 class="fw-bold display-5">What Our Students Say</h2>
                <p class="lead fs-5 mb-0">Real learners, real results on a modern LMS.</p>
            </div>
        </div>
        <div class="row g-4">
            <!-- keep your three testimonial cards as before -->
            <!-- (unchanged for brevity) -->
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-dark text-white position-relative overflow-hidden">
    <div class="container position-relative z-1">
        <div class="row align-items-center text-center text-lg-start">
            <div class="col-lg-8">
                <h3 class="fw-bold display-6 mb-3">Ready to Start Your Learning Journey?</h3>
                <p class="lead mb-0">Join thousands of students who trust this blue‑themed LMS to grow their careers.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary btn-lg px-5 py-3 fw-semibold hover-lift">
                        <i class="fas fa-rocket me-2"></i>Get Started Today
                    </a>
                <?php else: ?>
                    <a href="courses.php" class="btn btn-primary btn-lg px-5 py-3 fw-semibold hover-lift">
                        <i class="fas fa-search me-2"></i>Browse Courses
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    :root {
        --primary-blue-start: #0f62ff;
        --primary-blue-end: #2d9eff;
        --primary-blue-deep: #0043ce;
        --accent-gold: #ffb545;
        --bg-page-light: #f3f6ff;
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

    .bg-gradient-primary {
        background: linear-gradient(120deg, var(--primary-blue-start), var(--primary-blue-end));
    }

    .bg-gradient-instructor {
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
    }

    .bg-gradient-testimonials {
        background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
    }

    .min-vh-80 {
        min-height: 80vh;
    }

    .hover-lift {
        transition: all 0.25s ease;
    }

    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
    }

    .hero-section {
        position: relative;
        border-radius: 0 0 32px 32px;
        box-shadow: 0 20px 55px rgba(15, 23, 42, 0.25);
    }

    .hero-main-img {
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.35);
    }

    .hero-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .hero-wave svg {
        position: relative;
        display: block;
        width: 100%;
        height: 70px;
    }

    .hero-wave .shape-fill {
        fill: #f3f6ff;
    }

    .floating-elements {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .floating-card {
        position: absolute;
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.96);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.18);
        animation: float 6s ease-in-out infinite;
    }

    .floating-card.card-1 {
        top: 18%;
        left: 8%;
        animation-delay: 0s;
    }

    .floating-card.card-2 {
        top: 58%;
        right: 12%;
        animation-delay: 2s;
    }

    .floating-card.card-3 {
        bottom: 26%;
        left: 24%;
        animation-delay: 4s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-14px);
        }
    }

    .achievement-badge {
        position: absolute;
        bottom: -18px;
        right: 18px;
        background: rgba(15, 23, 42, 0.96);
        color: #e5edff;
        padding: 10px 16px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.4);
        font-size: 0.9rem;
    }

    .achievement-badge i {
        color: var(--accent-gold);
    }

    .stat-item {
        background: rgba(255, 255, 255, 0.96);
        border-radius: var(--radius-md);
        padding: 1.4rem 1.1rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        border: 1px solid var(--border-soft);
    }

    .stat-icon {
        width: 76px;
        height: 76px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.15);
    }

    .feature-card {
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        border: 1px solid var(--border-soft);
        backdrop-filter: blur(12px);
    }

    .feature-icon-wrapper {
        width: 90px;
        height: 90px;
    }

    .feature-icon {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .course-card {
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
        border: 1px solid var(--border-soft);
    }

    .course-image {
        height: 210px;
        object-fit: cover;
        border-top-left-radius: var(--radius-md);
        border-top-right-radius: var(--radius-md);
        transition: transform 0.3s ease;
    }

    .course-card:hover .course-image {
        transform: scale(1.04);
    }

    .course-image-placeholder {
        height: 210px;
        border-top-left-radius: var(--radius-md);
        border-top-right-radius: var(--radius-md);
    }

    .course-badges .badge {
        border-radius: 999px;
        font-size: 0.75rem;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .category-card {
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--border-soft);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    }

    .category-icon-wrapper {
        width: 72px;
        height: 72px;
    }

    .category-icon {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .gradient-primary {
        background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    }

    .gradient-success {
        background: linear-gradient(135deg, #059669, #22c55e);
    }

    .gradient-info {
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    }

    .gradient-warning {
        background: linear-gradient(135deg, #f59e0b, #facc15);
    }

    .gradient-danger {
        background: linear-gradient(135deg, #db2777, #f97316);
    }

    .gradient-secondary {
        background: linear-gradient(135deg, #4b5563, #6b7280);
    }

    .category-progress .progress {
        background-color: rgba(148, 163, 184, 0.25);
    }

    .instructor-stats-card {
        position: absolute;
        bottom: -18px;
        right: -18px;
        background: rgba(255, 255, 255, 0.98);
        color: var(--text-main);
        padding: 18px 22px;
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.5);
    }

    .testimonial-card {
        border-radius: var(--radius-md);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
    }

    section.bg-dark {
        background: radial-gradient(circle at top, #020617, #020617 55%);
    }

    /* === Fancy Blue Favorite Button === */
    .favorite-btn {
        background: rgba(255, 255, 255, 0.85);
        border: none;
        border-radius: 50%;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3b82f6;
        /* Blue heart by default */
        font-size: 1.2rem;
        transition: all 0.25s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
        cursor: pointer;
        position: relative;
    }

    .favorite-btn:hover {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
    }

    .favorite-btn.favorited {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
    }

    .favorite-btn.favorited::after {
        content: '★';
        position: absolute;
        bottom: -4px;
        right: -4px;
        background: #fff;
        color: #2563eb;
        font-size: 0.6rem;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    @media (max-width: 768px) {
        .display-3 {
            font-size: 2.4rem;
        }

        .display-5 {
            font-size: 2rem;
        }

        .hero-visual {
            margin-top: 2.5rem;
        }

        .floating-card {
            width: 44px;
            height: 44px;
            font-size: 0.8rem;
        }
    }
</style>

<script>

    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const courseId = btn.dataset.courseId;
            const icon = btn.querySelector('i');

            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'course_id=' + encodeURIComponent(courseId)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'added') {
                        btn.classList.add('favorited');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.style.transform = 'scale(1.2)';
                        setTimeout(() => btn.style.transform = 'scale(1)', 150);
                    } else if (data.status === 'removed') {
                        btn.classList.remove('favorited');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        btn.style.transform = 'scale(0.9)';
                        setTimeout(() => btn.style.transform = 'scale(1)', 150);
                    } else if (data.status === 'error') {
                        alert(data.message);
                    }
                });
        });
    });

    // Counter animation for statistics
    document.addEventListener('DOMContentLoaded', function () {
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-count');
                const count = +counter.innerText;
                const increment = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCount();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>