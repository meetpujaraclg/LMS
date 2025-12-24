<?php

ob_start();

$admin_pageTitle = "Manage Courses";
require_once 'admin_header.php';

// Handle course actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $courseId = (int)$_GET['id'];
    
    if ($_GET['action'] == 'delete') {
        $stmt = $admin_pdo->prepare("DELETE FROM courses WHERE id = ?");
        if ($stmt->execute([$courseId])) {
            $_SESSION['success'] = "Course deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete course!";
        }
        header("Location: courses.php");
        exit();
    } elseif ($_GET['action'] == 'toggle_publish') {
        $stmt = $admin_pdo->prepare("UPDATE courses SET is_published = NOT is_published WHERE id = ?");
        if ($stmt->execute([$courseId])) {
            $_SESSION['success'] = "Course status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update course status!";
        }
        header("Location: courses.php");
        exit();
    }
}

// Handle add/edit course form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = admin_sanitize($_POST['title']);
    $description = admin_sanitize($_POST['description']);
    $category = admin_sanitize($_POST['category']);
    $level = admin_sanitize($_POST['level']);
    $duration = (int)$_POST['duration'];
    $price = floatval($_POST['price']);
    $instructorId = (int)$_POST['instructor_id'];
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    // Handle thumbnail upload
    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/courses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $filePath)) {
                $thumbnail = 'courses/' . $fileName;
            }
        }
    }
    
    if (isset($_POST['course_id'])) {
        // Update existing course
        $courseId = (int)$_POST['course_id'];
        
        if ($thumbnail) {
            // Delete old thumbnail if exists
            $oldStmt = $admin_pdo->prepare("SELECT thumbnail FROM courses WHERE id = ?");
            $oldStmt->execute([$courseId]);
            $oldCourse = $oldStmt->fetch();
            
            if ($oldCourse['thumbnail'] && file_exists('../uploads/' . $oldCourse['thumbnail'])) {
                unlink('../uploads/' . $oldCourse['thumbnail']);
            }
            
            $stmt = $admin_pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, level = ?, duration = ?, price = ?, instructor_id = ?, is_published = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$title, $description, $category, $level, $duration, $price, $instructorId, $isPublished, $thumbnail, $courseId]);
        } else {
            $stmt = $admin_pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, level = ?, duration = ?, price = ?, instructor_id = ?, is_published = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$title, $description, $category, $level, $duration, $price, $instructorId, $isPublished, $courseId]);
        }
        
        if ($result) {
            $_SESSION['success'] = "Course updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update course!";
        }
    }
    
    header("Location: courses.php");
    exit();
}

// Display messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Show add/edit form if requested
if (isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')) {
    $course = null;
    $formTitle = "Add New Course";
    
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $courseId = (int)$_GET['id'];
        $stmt = $admin_pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();
        
        if (!$course) {
            $_SESSION['error'] = "Course not found!";
            header("Location: courses.php");
            exit();
        }
        $formTitle = "Edit Course: " . $course['title'];
    }
    
    // Get instructors for dropdown
    $instructors = $admin_pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'instructor' ORDER BY first_name, last_name")->fetchAll();
    ?>
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo $formTitle; ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="courses.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($course): ?>
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Course Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $course ? $course['title'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $course ? $course['description'] : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo $course ? $course['category'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Level *</label>
                                    <select class="form-select" id="level" name="level" required>
                                        <option value="beginner" <?php echo ($course && $course['level'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="intermediate" <?php echo ($course && $course['level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo ($course && $course['level'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (hours) *</label>
                                    <input type="number" class="form-control" id="duration" name="duration" 
                                           value="<?php echo $course ? $course['duration'] : ''; ?>" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price ($) *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo $course ? $course['price'] : '0'; ?>" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="instructor_id" class="form-label">Instructor *</label>
                            <select class="form-select" id="instructor_id" name="instructor_id" required>
                                <option value="">Select Instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['id']; ?>" 
                                        <?php echo ($course && $course['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                        <?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="thumbnail" class="form-label">Course Thumbnail</label>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Recommended size: 400x300px. Max 2MB.</small>
                            
                            <?php if ($course && $course['thumbnail']): ?>
                                <div class="mt-2">
                                    <img src="../uploads/<?php echo $course['thumbnail']; ?>" class="img-thumbnail" style="max-height: 150px;">
                                    <br>
                                    <small>Current thumbnail</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" 
                                   <?php echo ($course && $course['is_published']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Publish Course</label>
                        </div>
                        
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Quick Stats</h6>
                                <?php if ($course): ?>
                                    <?php
                                    $enrollmentStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                                    $enrollmentStmt->execute([$course['id']]);
                                    $enrollmentCount = $enrollmentStmt->fetchColumn();
                                    
                                    $progressStmt = $admin_pdo->prepare("SELECT AVG(progress) FROM enrollments WHERE course_id = ?");
                                    $progressStmt->execute([$course['id']]);
                                    $avgProgress = $progressStmt->fetchColumn();
                                    ?>
                                    <small>
                                        <strong>Enrollments:</strong> <?php echo $enrollmentCount; ?><br>
                                        <strong>Avg Progress:</strong> <?php echo $avgProgress ? round($avgProgress, 1) . '%' : 'N/A'; ?><br>
                                        <strong>Created:</strong> <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Stats will appear after course creation</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">* Required fields</small>
                </div>
                
                <button type="submit" name="save_course" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $course ? 'Update Course' : 'Add Course'; ?>
                </button>
                <a href="courses.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    
    <?php
    require_once 'admin_footer.php';
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? admin_sanitize($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? admin_sanitize($_GET['status']) : '';
$instructor_filter = isset($_GET['instructor']) ? (int)$_GET['instructor'] : 0;

$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($category_filter)) {
    $where .= " AND c.category = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    if ($status_filter == 'published') {
        $where .= " AND c.is_published = 1";
    } elseif ($status_filter == 'draft') {
        $where .= " AND c.is_published = 0";
    }
}

if (!empty($instructor_filter)) {
    $where .= " AND c.instructor_id = ?";
    $params[] = $instructor_filter;
}

// Get total count
$countStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $where");
$countStmt->execute($params);
$totalCourses = $countStmt->fetchColumn();
$totalPages = ceil($totalCourses / $limit);

// Get courses with additional info
$stmt = $admin_pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.email as instructor_email,
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as enrollment_count,
           (SELECT AVG(progress) FROM enrollments e WHERE e.course_id = c.id) as avg_progress
    FROM courses c 
    LEFT JOIN users u ON c.instructor_id = u.id 
    WHERE $where 
    ORDER BY c.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get unique categories
$categories = $admin_pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll();

// Get instructors for filter
$instructors = $admin_pdo->query("SELECT id, first_name, last_name FROM instructors")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Courses</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status_filter == 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="instructor" class="form-select">
                    <option value="0">All Instructors</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo $instructor['id']; ?>" <?php echo $instructor_filter == $instructor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="courses.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Courses (<?php echo $totalCourses; ?>)</h5>
        <span class="badge bg-primary">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Details</th>
                        <th>Enrollments</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-book fa-2x mb-2 d-block"></i>
                                No courses found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="../uploads/<?php echo $course['thumbnail']; ?>" class="rounded me-2" width="50" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded bg-secondary d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 40px;">
                                                <i class="fas fa-book text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                            <?php if ($course['category']): ?>
                                                <br><span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($course['level']): ?>
                                                <span class="badge bg-info"><?php echo ucfirst($course['level']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($course['first_name']): ?>
                                        <strong><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($course['instructor_email']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">No instructor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-clock text-muted"></i> <?php echo $course['duration']; ?>h
                                        <br>
                                        <i class="fas fa-dollar-sign text-muted"></i> 
                                        <?php echo $course['price'] > 0 ? '$' . $course['price'] : 'Free'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $course['enrollment_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($course['avg_progress']): ?>
                                        <div class="progress" style="height: 20px; width: 100px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $course['avg_progress']; ?>%;">
                                                <?php echo round($course['avg_progress']); ?>%
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $course['is_published'] ? 'success' : 'warning'; ?>">
                                        <?php echo $course['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../course-detail.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary" target="_blank" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="courses.php?action=toggle_publish&id=<?php echo $course['id']; ?>" class="btn btn-outline-<?php echo $course['is_published'] ? 'warning' : 'success'; ?>" title="<?php echo $course['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                            <i class="fas fa-<?php echo $course['is_published'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="courses.php?action=delete&id=<?php echo $course['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this course? This will also delete all enrollments and progress data.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&instructor=<?php echo $instructor_filter; ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&instructor=<?php echo $instructor_filter; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&instructor=<?php echo $instructor_filter; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'admin_footer.php'; ob_end_flush(); ?>