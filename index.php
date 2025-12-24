<?php
$pageTitle = "Home";
require_once 'includes/config.php';
require_once 'includes/auth.php';

include 'includes/header.php';

global $pdo;

// Get featured courses
$featuredStmt = $pdo->query("
    SELECT c.*, u.first_name, u.last_name 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    WHERE c.is_published = 1 
    ORDER BY c.created_at DESC 
    LIMIT 6
");
$featuredCourses = $featuredStmt->fetchAll();

// Get statistics
$totalCoursesStmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published = 1");
$totalCourses = $totalCoursesStmt->fetchColumn();

$totalStudentsStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalStudents = $totalStudentsStmt->fetchColumn();

$totalInstructorsStmt = $pdo->query("SELECT COUNT(*) FROM instructors");
$totalInstructors = $totalInstructorsStmt->fetchColumn();

$totalEnrollmentsStmt = $pdo->query("SELECT COUNT(*) FROM enrollments");
$totalEnrollments = $totalEnrollmentsStmt->fetchColumn();
?>

<!-- Enhanced Hero Section -->
<section class="hero-section bg-gradient-primary text-white py-5 position-relative overflow-hidden">
    <div class="container position-relative z-1">
        <div class="row align-items-center min-vh-80">
            <div class="col-lg-6">
                <div class="hero-content">
                    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill">
                        <i class="fas fa-rocket me-2"></i>Transform Your Career Today
                    </span>
                    <h1 class="display-4 fw-bold mb-4 animate-slide-in">Learn Without <span class="text-warning">Limits</span></h1>
                    <p class="lead mb-4 fs-5">Start, switch, or advance your career with thousands of courses, professional certificates, and degrees from world-class universities and companies.</p>
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
                    <div class="trust-indicators">
                        <div class="d-flex align-items-center flex-wrap gap-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2 text-warning"></i>
                                <small>Trusted by 50,000+ students</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-globe me-2 text-warning"></i>
                                <small>190+ countries</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock me-2 text-warning"></i>
                                <small>24/7 access</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual text-center position-relative">
                    <div class="floating-elements">
                        <div class="floating-card card-1">
                            <i class="fas fa-graduation-cap text-primary"></i>
                        </div>
                        <div class="floating-card card-2">
                            <i class="fas fa-certificate text-success"></i>
                        </div>
                        <div class="floating-card card-3">
                            <i class="fas fa-trophy text-warning"></i>
                        </div>
                    </div>
                    <img src="https://cdn.pixabay.com/photo/2016/11/19/14/00/code-1839406_1280.jpg" 
                         alt="Online Learning" 
                         class="img-fluid rounded-4 shadow-lg hero-main-img">
                    <div class="achievement-badge">
                        <div class="badge-content">
                            <i class="fas fa-star text-warning"></i>
                            <span>4.8/5 Rating</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
        </svg>
    </div>
</section>

<!-- Enhanced Statistics Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-primary text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h2 class="display-4 fw-bold text-primary counter" data-count="<?php echo $totalCourses; ?>">0</h2>
                    <p class="text-muted fw-semibold">Online Courses</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-success text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="display-4 fw-bold text-success counter" data-count="<?php echo number_format($totalStudents); ?>">0</h2>
                    <p class="text-muted fw-semibold">Active Students</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-info text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h2 class="display-4 fw-bold text-info counter" data-count="<?php echo $totalInstructors; ?>">0</h2>
                    <p class="text-muted fw-semibold">Expert Instructors</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item hover-lift">
                    <div class="stat-icon bg-warning text-white rounded-circle mx-auto mb-3">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h2 class="display-4 fw-bold text-warning counter" data-count="<?php echo number_format($totalEnrollments); ?>">0</h2>
                    <p class="text-muted fw-semibold">Course Enrollments</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-md-12">
                <span class="badge bg-primary mb-3 px-3 py-2 rounded-pill">Why Choose Us</span>
                <h2 class="fw-bold display-5">Why Choose EdTech LMS?</h2>
                <p class="text-muted lead fs-5">Experience the future of learning with our innovative platform designed for success</p>
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
                        <p class="text-muted">Access courses on any device, at any time. Learn at your own pace with lifetime access to course materials.</p>
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
                        <p class="text-muted">Learn from industry experts and experienced professionals who are passionate about teaching and mentoring.</p>
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
                        <p class="text-muted">Earn industry-recognized certificates upon course completion to showcase your skills and advance your career.</p>
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
                        <h5 class="fw-bold">Hands-on Learning</h5>
                        <p class="text-muted">Apply your knowledge with real-world projects and build a portfolio that demonstrates your expertise to employers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New: Become an Instructor Section -->
<section class="py-5 bg-gradient-instructor text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill">Share Your Knowledge</span>
                <h2 class="fw-bold display-5 mb-4">Become an Instructor</h2>
                <p class="lead mb-4">Join our community of expert instructors and share your knowledge with thousands of eager learners worldwide. Create impactful courses and build your personal brand.</p>
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
                <div class="instructor-visual mt-5 mt-lg-0">
                    <div class="position-relative">
                        <!-- <img src="https://cdn.pixabay.com/photo/2016/02/19/11/19/office-1209640_1280.jpg" 
                             alt="Become an Instructor" 
                             class="img-fluid rounded-4 shadow-lg"> -->
                        <div class="instructor-stats-card">
                            <div class="stats-content">
                                <h4 class="text-primary mb-1">$5,000+</h4>
                                <small>Average Monthly Earnings</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Featured Courses Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-md-8">
                <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill">Popular Courses</span>
                <h2 class="fw-bold display-5">Featured Courses</h2>
                <p class="text-muted fs-5">Discover our most popular and highly-rated courses handpicked for your success</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="courses.php" class="btn btn-primary btn-lg px-4 py-3 fw-semibold hover-lift">
                    <i class="fas fa-arrow-right me-2"></i>View All Courses
                </a>
            </div>
        </div>
        <div class="row g-4">
            <?php if (empty($featuredCourses)): ?>
                <div class="col-md-12">
                    <div class="card border-0 text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-book fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No courses available yet</h4>
                            <p class="text-muted">Check back soon for new course offerings!</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($featuredCourses as $course): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card course-card border-0 h-100 hover-lift">
                            <div class="course-image-wrapper position-relative">
                                <?php if ($course['thumbnail']): ?>
                                    <img src="uploads/<?php echo $course['thumbnail']; ?>" 
                                         class="card-img-top course-image" 
                                         alt="<?php echo $course['title']; ?>">
                                <?php else: ?>
                                    <div class="card-img-top course-image-placeholder bg-gradient-primary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-book fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="course-badges position-absolute top-0 start-0 p-3">
                                    <span class="badge bg-primary"><?php echo ucfirst($course['level']); ?></span>
                                    <?php if ($course['category']): ?>
                                        <span class="badge bg-secondary ms-1"><?php echo $course['category']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="course-hover-actions position-absolute top-0 end-0 p-3">
                                    <button class="btn btn-light btn-sm rounded-circle">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column p-4">
                                <div class="course-instructor mb-2">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <small class="text-muted"><?php echo $course['first_name'] . ' ' . $course['last_name']; ?></small>
                                </div>
                                <h5 class="card-title fw-bold line-clamp-2"><?php echo $course['title']; ?></h5>
                                <p class="card-text text-muted line-clamp-2 flex-grow-1"><?php echo substr($course['description'], 0, 120); ?>...</p>
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
                                            <span class="h5 mb-0 text-success fw-bold">$<?php echo $course['price']; ?></span>
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

<!-- Enhanced Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill">Explore Categories</span>
                <h2 class="fw-bold display-5">Popular Categories</h2>
                <p class="text-muted lead fs-5">Browse courses by category and find your perfect learning path</p>
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

<!-- Enhanced Testimonials Section -->
<section class="py-5 bg-gradient-testimonials text-white position-relative">
    <div class="container position-relative z-1">
        <div class="row mb-5">
            <div class="col-md-12 text-center">
                <span class="badge bg-light text-primary mb-2 px-3 py-2 rounded-pill">Success Stories</span>
                <h2 class="fw-bold display-5">What Our Students Say</h2>
                <p class="lead fs-5">Hear from our successful students around the world who transformed their careers</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card testimonial-card border-0 bg-white text-dark h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="testimonial-header d-flex align-items-center mb-3">
                            <div class="student-avatar me-3">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Sarah Johnson</h6>
                                <small class="text-muted">Web Developer</small>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"EdTech LMS transformed my career. The courses are well-structured and the instructors are knowledgeable. I landed a job as a web developer just 3 months after completing the full-stack course!"</p>
                        <div class="testimonial-meta mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-graduation-cap me-1"></i>
                                Completed 8 courses
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card testimonial-card border-0 bg-white text-dark h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="testimonial-header d-flex align-items-center mb-3">
                            <div class="student-avatar me-3">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Michael Chen</h6>
                                <small class="text-muted">UX Designer</small>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"As a working professional, I needed flexible learning options. EdTech LMS allowed me to upskill at my own pace. The hands-on projects were invaluable for building my portfolio and landing promotions."</p>
                        <div class="testimonial-meta mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-graduation-cap me-1"></i>
                                Completed 12 courses
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card testimonial-card border-0 bg-white text-dark h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="testimonial-header d-flex align-items-center mb-3">
                            <div class="student-avatar me-3">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Emily Rodriguez</h6>
                                <small class="text-muted">Data Analyst</small>
                            </div>
                        </div>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"The quality of instruction on EdTech LMS is exceptional. The courses are comprehensive and the community support helped me overcome challenges. Highly recommended for anyone serious about learning!"</p>
                        <div class="testimonial-meta mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-graduation-cap me-1"></i>
                                Completed 6 courses
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced CTA Section -->
<section class="py-5 bg-dark text-white position-relative overflow-hidden">
    <div class="container position-relative z-1">
        <div class="row align-items-center text-center text-lg-start">
            <div class="col-lg-8">
                <h3 class="fw-bold display-6 mb-3">Ready to Start Your Learning Journey?</h3>
                <p class="lead mb-0">Join thousands of students who have transformed their careers with our expert-led courses and hands-on projects.</p>
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
/* Enhanced CSS Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --instructor-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --testimonial-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.bg-gradient-primary {
    background: var(--primary-gradient);
}

.bg-gradient-instructor {
    background: var(--instructor-gradient);
}

.bg-gradient-testimonials {
    background: var(--testimonial-gradient);
}


.min-vh-80 {
    min-height: 80vh;
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Hero Section Enhancements */
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
    width: calc(100% + 1.3px);
    height: 70px;
}

.hero-wave .shape-fill {
    fill: #FFFFFF;
}

.floating-elements {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.floating-card {
    position: absolute;
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    animation: float 6s ease-in-out infinite;
}

.floating-card.card-1 {
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.floating-card.card-2 {
    top: 60%;
    right: 15%;
    animation-delay: 2s;
}

.floating-card.card-3 {
    bottom: 30%;
    left: 20%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.achievement-badge {
    position: absolute;
    bottom: -20px;
    right: 20px;
    background: white;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.hero-main-img {
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

/* Statistics */
.stat-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Feature Cards */
.feature-icon-wrapper {
    width: 100px;
    height: 100px;
}

.feature-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

/* Course Cards */
.course-image {
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.course-card:hover .course-image {
    transform: scale(1.05);
}

.course-image-placeholder {
    height: 200px;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Category Cards */
.category-icon-wrapper {
    width: 80px;
    height: 80px;
}

.category-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.gradient-success { background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); }
.gradient-info { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
.gradient-warning { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
.gradient-danger { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
.gradient-secondary { background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); }

/* Instructor Section */
.instructor-stats-card {
    position: absolute;
    bottom: -20px;
    right: -20px;
    background: white;
    color: #333;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2.5rem;
    }
    
    .display-5 {
        font-size: 2rem;
    }
    
    .hero-visual {
        margin-top: 3rem;
    }
    
    .floating-card {
        width: 40px;
        height: 40px;
        font-size: 0.8rem;
    }
}
</style>

<script>
// Counter animation for statistics
document.addEventListener('DOMContentLoaded', function() {
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

        // Start counter when element is in viewport
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