<?php
// instructor/instructor_courses.php
error_reporting(0);
// Start output buffering to prevent headers already sent error
ob_start();

$pageTitle = "Manage Courses";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];

// Handle course actions
if (isset($_GET['action']) && isset($_GET['id'])) 
    // Handle course actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $courseId = (int)$_GET['id'];
    
    // Enhanced verification with better error handling
    try {
        $verifyStmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
        $verifyStmt->execute([$courseId, $instructorId]);
        $course = $verifyStmt->fetch();
        
        if ($course) {
            if ($_GET['action'] == 'delete') {
                // Check if there are enrollments before deleting
                $enrollmentCheck = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                $enrollmentCheck->execute([$courseId]);
                $enrollmentCount = $enrollmentCheck->fetchColumn();
                
                if ($enrollmentCount > 0) {
                    $_SESSION['error'] = "Cannot delete course with active enrollments!";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                    if ($stmt->execute([$courseId])) {
                        $_SESSION['success'] = "Course '{$course['title']}' deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete course!";
                    }
                }
            } elseif ($_GET['action'] == 'toggle_publish') {
                $stmt = $pdo->prepare("UPDATE courses SET is_published = NOT is_published WHERE id = ?");
                if ($stmt->execute([$courseId])) {
                    $newStatus = $pdo->query("SELECT is_published FROM courses WHERE id = $courseId")->fetchColumn();
                    $statusText = $newStatus ? 'published' : 'unpublished';
                    $_SESSION['success'] = "Course '{$course['title']}' $statusText successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update course status!";
                }
            }
        } else {
            $_SESSION['error'] = "Course not found or you don't have permission to access it!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    // Clear output buffer and redirect
    ob_end_clean();
    header("Location: instructor_courses.php");
    exit();
}
// Handle add/edit course form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = admin_sanitize($_POST['title']);
    $description = admin_sanitize($_POST['description']);
    $category = admin_sanitize($_POST['category']);
    $level = admin_sanitize($_POST['level']);
    $duration = (int)$_POST['duration'];
    $price = floatval($_POST['price']);
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
        
        // Verify course belongs to instructor
        $verifyStmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $verifyStmt->execute([$courseId, $instructorId]);
        
        if ($verifyStmt->fetch()) {
            if ($thumbnail) {
                // Delete old thumbnail if exists
                $oldStmt = $pdo->prepare("SELECT thumbnail FROM courses WHERE id = ?");
                $oldStmt->execute([$courseId]);
                $oldCourse = $oldStmt->fetch();
                
                if ($oldCourse && $oldCourse['thumbnail'] && file_exists('../uploads/' . $oldCourse['thumbnail'])) {
                    unlink('../uploads/' . $oldCourse['thumbnail']);
                }
                
                $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, level = ?, duration = ?, price = ?, is_published = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$title, $description, $category, $level, $duration, $price, $isPublished, $thumbnail, $courseId]);
            } else {
                $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, level = ?, duration = ?, price = ?, is_published = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$title, $description, $category, $level, $duration, $price, $isPublished, $courseId]);
            }
            
            if ($result) {
                $_SESSION['success'] = "Course updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update course!";
            }
        } else {
            $_SESSION['error'] = "Course not found or access denied!";
        }
    } else {
        // Add new course
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, category, level, duration, price, instructor_id, is_published, thumbnail, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if ($stmt->execute([$title, $description, $category, $level, $duration, $price, $instructorId, $isPublished, $thumbnail])) {
            $_SESSION['success'] = "Course added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add course!";
        }
    }
    
    // Clear output buffer and redirect
    ob_end_clean();
    header("Location: instructor_courses.php");
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
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$courseId, $instructorId]);
        $course = $stmt->fetch();
        
        if (!$course) {
            $_SESSION['error'] = "Course not found or access denied!";
            
            // Clear output buffer and redirect
            ob_end_clean();
            header("Location: instructor_courses.php");
            exit();
        }
        $formTitle = "Edit Course: " . $course['title'];
    }
    ?>
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo $formTitle; ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="instructor_courses.php" class="btn btn-secondary">
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
                                   value="<?php echo $course ? htmlspecialchars($course['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $course ? htmlspecialchars($course['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo $course ? htmlspecialchars($course['category']) : ''; ?>" required>
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
                        
                        <?php if ($course): ?>
                            <?php
                            // Check if enrollments table exists
                            $enrollmentsTableExists = $pdo->query("SHOW TABLES LIKE 'enrollments'")->fetch();
                            
                            if ($enrollmentsTableExists) {
                                $enrollmentStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                                $enrollmentStmt->execute([$course['id']]);
                                $enrollmentCount = $enrollmentStmt->fetchColumn();
                                
                                $progressStmt = $pdo->prepare("SELECT AVG(progress) FROM enrollments WHERE course_id = ?");
                                $progressStmt->execute([$course['id']]);
                                $avgProgress = $progressStmt->fetchColumn();
                            } else {
                                $enrollmentCount = 0;
                                $avgProgress = 0;
                            }
                            ?>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Course Stats</h6>
                                    <small>
                                        <strong>Enrollments:</strong> <?php echo $enrollmentCount; ?><br>
                                        <strong>Avg Progress:</strong> <?php echo $avgProgress ? round($avgProgress, 1) . '%' : 'N/A'; ?><br>
                                        <strong>Created:</strong> <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">* Required fields</small>
                </div>
                
                <button type="submit" name="save_course" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $course ? 'Update Course' : 'Add Course'; ?>
                </button>
                <a href="instructor_courses.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    
    <?php
    // End output buffering and flush for form pages
    ob_end_flush();
    require_once 'instructor_footer.php';
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? admin_sanitize($_GET['status']) : '';

$where = "instructor_id = ?";
$params = [$instructorId];

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($status_filter)) {
    if ($status_filter == 'published') {
        $where .= " AND is_published = 1";
    } elseif ($status_filter == 'draft') {
        $where .= " AND is_published = 0";
    }
}

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE $where");
$countStmt->execute($params);
$totalCourses = $countStmt->fetchColumn();
$totalPages = ceil($totalCourses / $limit);

// Check if enrollments table exists for additional stats
$enrollmentsTableExists = $pdo->query("SHOW TABLES LIKE 'enrollments'")->fetch();

if ($enrollmentsTableExists) {
    // Get courses with additional info
    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as enrollment_count,
               (SELECT AVG(progress) FROM enrollments e WHERE e.course_id = c.id) as avg_progress
        FROM courses c 
        WHERE $where 
        ORDER BY c.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
} else {
    // Get courses without enrollment stats
    $stmt = $pdo->prepare("
        SELECT c.*,
               0 as enrollment_count,
               0 as avg_progress
        FROM courses c 
        WHERE $where 
        ORDER BY c.created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
}

$stmt->execute($params);
$courses = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Courses</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="instructor_courses.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Course
        </a>
    </div>
</div>
<a href="add_material_simple.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm">
    <i class="fas fa-plus"></i> Add Materials
</a>
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
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status_filter == 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="instructor_courses.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">My Courses (<?php echo $totalCourses; ?>)</h5>
        <span class="badge bg-primary">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Course</th>
                        <th>Details</th>
                        <th>Enrollments</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Created</th>
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
                                    <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../course-detail.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary" target="_blank" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="instructor_courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="instructor_courses.php?action=toggle_publish&id=<?php echo $course['id']; ?>" class="btn btn-outline-<?php echo $course['is_published'] ? 'warning' : 'success'; ?>" title="<?php echo $course['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                            <i class="fas fa-<?php echo $course['is_published'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="instructor_courses.php?action=delete&id=<?php echo $course['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this course? This will also delete all enrollments and progress data.')">
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php 
// End output buffering and flush the content
ob_end_flush();
require_once 'instructor_footer.php'; 
?>