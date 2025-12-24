<?php
$pageTitle = "Courses";
require_once 'includes/config.php';
require_once 'includes/auth.php';
include 'includes/header.php';

// Get all published courses
global $pdo;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$level = isset($_GET['level']) ? sanitize($_GET['level']) : '';

$query = "SELECT c.*, u.first_name, u.last_name 
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          WHERE c.is_published = 1";

$params = [];

if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND c.category = ?";
    $params[] = $category;
}

if (!empty($level)) {
    $query .= " AND c.level = ?";
    $params[] = $level;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get unique categories for filter
$categoriesStmt = $pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL");
$categories = $categoriesStmt->fetchAll();
?>

<style>
    /* ===== Modern Udemy-Style Course Page ===== */
    .page-title {
        font-weight: 700;
        color: #1c1d1f;
    }

    .text-muted {
        color: #6a6f73 !important;
    }

    .filter-card {
        border: none;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }

    .course-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .course-card img {
        height: 200px;
        object-fit: cover;
    }

    .course-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1c1d1f;
    }

    .course-instructor {
        font-size: 0.9rem;
        color: #6a6f73;
    }

    .course-price {
        font-weight: 700;
        color: #2d864e;
    }

    .badge {
        font-size: 0.75rem;
        border-radius: 6px;
    }

    .btn-primary {
        background-color: #5624d0;
        border-color: #5624d0;
    }

    .btn-primary:hover {
        background-color: #401b9c;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #ccc;
    }
</style>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <h2 class="page-title">Browse Our Courses</h2>
            <p class="text-muted">Learn from top instructors and upgrade your skills anytime, anywhere.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card filter-card p-3">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search for courses..."
                            value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['category']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="level" class="form-select">
                            <option value="">All Levels</option>
                            <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>Beginner
                            </option>
                            <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>
                                Intermediate</option>
                            <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Advanced
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i
                                class="fas fa-filter me-2"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Course Grid -->
    <div class="row">
        <?php if (empty($courses)): ?>
            <div class="col-md-12 text-center">
                <div class="alert alert-light border">
                    <h5 class="fw-bold">No courses found</h5>
                    <p class="text-muted">Try changing filters or browse all courses.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="col-md-4 mb-4">
                    <div class="card course-card">
                        <?php if ($course['thumbnail']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-book fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="course-title mb-2"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="course-instructor mb-2"><i
                                    class="fas fa-user me-2"></i><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                            </p>
                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 90)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary text-white"><?php echo ucfirst($course['level']); ?></span>
                                <?php if ($course['price'] > 0): ?>
                                    <span class="course-price">$<?php echo number_format($course['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="course-price">Free</span>
                                <?php endif; ?>
                            </div>
                            <a href="course-detail.php?id=<?php echo $course['id']; ?>" class="btn btn-primary w-100 mt-3">View
                                Course</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>