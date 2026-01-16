<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

$pageTitle = "Manage Courses";
require_once 'instructor_header.php';

if (!function_exists('admin_sanitize')) {
    function admin_sanitize($data)
    {
        return htmlspecialchars(trim($data));
    }
}

$instructorId = $_SESSION['instructor_id'];

// ---------------------------------------------------------
// LOGIC: Handle Actions (Delete Only)
// ---------------------------------------------------------

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $courseId = (int) $_GET['id'];

    if ($action == 'delete') {
        try {
            $verifyStmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
            $verifyStmt->execute([$courseId, $instructorId]);
            $course = $verifyStmt->fetch();

            if ($course) {
                $enrollmentCheck = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                $enrollmentCheck->execute([$courseId]);

                if ($enrollmentCheck->fetchColumn() > 0) {
                    $_SESSION['error'] = "Cannot delete course with active enrollments!";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                    if ($stmt->execute([$courseId])) {
                        $_SESSION['success'] = "Course deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete course!";
                    }
                }
            } else {
                $_SESSION['error'] = "Course not found or access denied!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        ob_end_clean();
        header("Location: instructor_courses.php");
        exit();
    }
}

// ---------------------------------------------------------
// LOGIC: Save Course (Create / Edit)
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = admin_sanitize($_POST['title']);
    $description = admin_sanitize($_POST['description']);
    $category = admin_sanitize($_POST['category']);
    $level = admin_sanitize($_POST['level']);
    $price = floatval($_POST['price']);

    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/courses/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $fileExtension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array(strtolower($fileExtension), $allowed)) {
            $fileName = uniqid() . '.' . $fileExtension;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $fileName)) {
                $thumbnail = 'courses/' . $fileName;
            }
        }
    }

    try {
        if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
            $courseId = (int) $_POST['course_id'];
            $check = $pdo->prepare("SELECT id, thumbnail FROM courses WHERE id=? AND instructor_id=?");
            $check->execute([$courseId, $instructorId]);
            $existing = $check->fetch();

            if ($existing) {
                if ($thumbnail) {
                    if ($existing['thumbnail'] && file_exists('../uploads/' . $existing['thumbnail'])) {
                        unlink('../uploads/' . $existing['thumbnail']);
                    }
                    $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, category=?, level=?, price=?, thumbnail=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$title, $description, $category, $level, $price, $thumbnail, $courseId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, category=?, level=?, price=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$title, $description, $category, $level, $price, $courseId]);
                }
                $_SESSION['success'] = "Course updated successfully!";
                ob_end_clean();
                header("Location: instructor_modules.php?course_id=$courseId");
                exit();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, category, level, duration, price, instructor_id, thumbnail, created_at, updated_at) VALUES (?,?,?,?,0,?,?,?,NOW(),NOW())");
            $stmt->execute([$title, $description, $category, $level, $price, $instructorId, $thumbnail]);
            $_SESSION['success'] = "New course created successfully! Now add modules and videos.";
            $newCourseId = $pdo->lastInsertId();
            ob_end_clean();
            header("Location: instructor_modules.php?course_id=$newCourseId");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
}

// ---------------------------------------------------------
// VIEW: Flash Messages
// ---------------------------------------------------------

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show m-4" role="alert">' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show m-4" role="alert">' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error']);
}

$currentAction = $_GET['action'] ?? 'list';

// ---------------------------------------------------------
// VIEW: Add / Edit Form
// ---------------------------------------------------------

if ($currentAction == 'add' || $currentAction == 'edit') {
    $course = null;
    $formTitle = "Add New Course";

    if ($currentAction == 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND instructor_id=?");
        $stmt->execute([$_GET['id'], $instructorId]);
        $course = $stmt->fetch();

        if (!$course) {
            ob_end_clean();
            header("Location: instructor_courses.php");
            exit();
        }
        $formTitle = "Edit Course: " . htmlspecialchars($course['title']);
    }
    ?>
    <div class="container-fluid px-4">
        <div class="p-4 mb-4 rounded-3 text-white" style="background:linear-gradient(90deg,#0062E6,#33AEFF);">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="fw-bold mb-0"><i class="fas fa-pen-nib me-2"></i><?php echo $formTitle; ?></h2>
                    <p class="text-white-50 mb-0">Step 1 of 3 — Add basic course info</p>
                </div>
                <a href="instructor_courses.php" class="btn btn-light fw-semibold shadow-sm"><i
                        class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($course): ?><input type="hidden" name="course_id"
                            value="<?php echo $course['id']; ?>"><?php endif; ?>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Course Title *</label>
                                <input type="text" class="form-control form-control-lg" name="title"
                                    value="<?php echo $course ? htmlspecialchars($course['title']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description *</label>
                                <textarea class="form-control" name="description" rows="5"
                                    required><?php echo $course ? htmlspecialchars($course['description']) : ''; ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Category *</label>
                                    <select class="form-select" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        <?php
                                        // Fetch all admin-created categories
                                        $catStmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
                                        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($categories as $cat):
                                            $selected = ($course && $course['category'] == $cat['name']) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Level *</label>
                                    <select class="form-select" name="level" required>
                                        <option value="beginner" <?php echo ($course && $course['level'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="intermediate" <?php echo ($course && $course['level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo ($course && $course['level'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Total Duration (auto)</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo $course ? $course['duration'] . ' hours' : '0 hours'; ?>"
                                        readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Price (₹) *</label>
                                    <input type="number" class="form-control" name="price"
                                        value="<?php echo $course ? $course['price'] : '0'; ?>" min="0" step="0.01"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Course Thumbnail *</label>
                                <input type="file" required class="form-control" id="thumbnail" name="thumbnail"
                                    accept="image/*">
                                <small class="text-muted d-block mt-1">Recommended: 400x300px, Max 2MB</small>
                                <div class="mt-3 text-center">
                                    <img src="<?php echo ($course && $course['thumbnail']) ? '../uploads/' . $course['thumbnail'] : ''; ?>"
                                        id="preview" class="img-thumbnail rounded"
                                        style="max-height:180px; <?php echo ($course && $course['thumbnail']) ? '' : 'display:none;'; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" name="save_course" class="btn btn-primary btn-lg px-5"><i
                                class="fas fa-save me-1"></i> <?php echo $course ? 'Update' : 'Create'; ?> Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('thumbnail').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('preview');
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        });
    </script>

    <?php
    // ---------------------------------------------------------
// VIEW: Course List
// ---------------------------------------------------------
} else {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = 9;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? admin_sanitize($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? admin_sanitize($_GET['status']) : '';

    $where = "instructor_id=?";
    $params = [$instructorId];

    if (!empty($search)) {
        $where .= " AND (title LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE $where");
    $countStmt->execute($params);
    $totalCourses = $countStmt->fetchColumn();
    $totalPages = ceil($totalCourses / $limit);

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) as enrollment_count, 
            (SELECT AVG(progress) FROM enrollments e WHERE e.course_id=c.id) as avg_progress 
            FROM courses c WHERE $where ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    ?>

    <div class="container-fluid px-4">
        <div class="p-4 mb-4 rounded-3 text-white" style="background:linear-gradient(90deg,#0062E6,#33AEFF);">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h2 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Manage Your Courses</h2>
                <a href="instructor_courses.php?action=add" class="btn btn-light fw-semibold shadow-sm"><i
                        class="fas fa-plus"></i> New Course</a>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($courses)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-book-open fa-3x mb-3 opacity-25"></i>
                    <p class="lead">No courses found matching your criteria.</p>
                </div>
            <?php else:
                foreach ($courses as $course): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm h-100 border-0 course-card">
                            <div class="position-relative">
                                <?php if ($course['thumbnail']): ?>
                                    <img src="../uploads/<?php echo $course['thumbnail']; ?>" class="card-img-top"
                                        style="height:180px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex justify-content-center align-items-center" style="height:180px;">
                                        <i class="fas fa-image text-secondary fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($course['title']); ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </h5>
                                <div class="d-flex gap-2 mb-2 text-muted small">
                                    <span><i class="fas fa-layer-group me-1"></i><?php echo ucfirst($course['level']); ?></span>
                                    <span><i class="fas fa-clock me-1"></i><?php echo $course['duration']; ?>h</span>
                                </div>
                                <div class="mb-3">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($course['category']); ?></span>
                                    <span
                                        class="float-end fw-bold text-primary"><?php echo $course['price'] > 0 ? '₹' . $course['price'] : 'Free'; ?></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Students: <strong><?php echo $course['enrollment_count']; ?></strong></span>
                                    <?php if ($course['avg_progress'] > 0): ?>
                                        <span>Avg Prog: <?php echo round($course['avg_progress']); ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3">
                                <a href="../course-detail.php?id=<?php echo $course['id']; ?>"
                                    class="btn btn-sm btn-outline-primary" target="_blank" title="View Public Page"><i
                                        class="fas fa-eye"></i></a>
                                <a href="instructor_courses.php?action=edit&id=<?php echo $course['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="instructor_modules.php?course_id=<?php echo $course['id']; ?>"
                                    class="btn btn-sm btn-outline-info" title="Manage Modules & Videos"><i
                                        class="fas fa-folder-open"></i></a>
                                <button class="btn btn-sm btn-outline-danger delete-course" data-id="<?php echo $course['id']; ?>"
                                    title="Delete"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-course').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const id = btn.dataset.id;
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this! Enrollments will prevent deletion.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `instructor_courses.php?action=delete&id=${id}`;
                    }
                })
            });
        });
    </script>

    <style>
        .course-card {
            transition: transform 0.2s;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }
    </style>

    <?php
}

require_once 'instructor_footer.php';
ob_end_flush();
?>