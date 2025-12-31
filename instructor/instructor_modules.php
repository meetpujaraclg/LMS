<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
$pageTitle = "Manage Modules";
require_once 'instructor_header.php';

if (!function_exists('admin_sanitize')) {
    function admin_sanitize($data)
    {
        return htmlspecialchars(trim($data));
    }
}

if (!isset($_GET['course_id'])) {
    header("Location: instructor_courses.php");
    exit();
}

$courseId = (int) $_GET['course_id'];

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: instructor_courses.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_module'])) {
    $title = admin_sanitize($_POST['title']);
    $description = admin_sanitize($_POST['description']);
    $sort_order = (int) $_POST['sort_order'];

    try {
        if (isset($_POST['module_id']) && !empty($_POST['module_id'])) {
            $moduleId = (int) $_POST['module_id'];
            $stmt = $pdo->prepare("UPDATE course_modules SET title=?, description=?, sort_order=? WHERE id=?");
            $stmt->execute([$title, $description, $sort_order, $moduleId]);
            $_SESSION['success'] = "Module updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO course_modules (course_id, title, description, sort_order, created_at) VALUES (?,?,?,?,NOW())");
            $stmt->execute([$courseId, $title, $description, $sort_order]);
            $_SESSION['success'] = "New module added successfully!";
        }
        ob_end_clean();
        header("Location: instructor_modules.php?course_id=$courseId");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $moduleId = (int) $_GET['id'];

    $lessonCheck = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE module_id=?");
    $lessonCheck->execute([$moduleId]);

    if ($lessonCheck->fetchColumn() > 0) {
        $_SESSION['error'] = "Cannot delete a module containing lessons!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM course_modules WHERE id=?");
        $stmt->execute([$moduleId]);
        $_SESSION['success'] = "Module deleted successfully!";
    }

    ob_end_clean();
    header("Location: instructor_modules.php?course_id=$courseId");
    exit();
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show m-4" role="alert">' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show m-4" role="alert">' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error']);
}

$editModule = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE id=? AND course_id=?");
    $stmt->execute([$_GET['id'], $courseId]);
    $editModule = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id=? ORDER BY sort_order ASC");
$stmt->execute([$courseId]);
$modules = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="p-4 mb-4 rounded-3 text-white" style="background:linear-gradient(90deg,#0062E6,#33AEFF);">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Manage Modules -
                    <?php echo htmlspecialchars($course['title']); ?></h2>
                <p class="text-white-50 mb-0">Step 2 of 3 — Organize course modules</p>
            </div>
            <a href="instructor_courses.php" class="btn btn-light fw-semibold shadow-sm"><i
                    class="fas fa-arrow-left"></i> Back to Courses</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="POST">
                <?php if ($editModule): ?><input type="hidden" name="module_id"
                        value="<?php echo $editModule['id']; ?>"><?php endif; ?>
                <div class="row g-4">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Module Title *</label>
                        <input type="text" class="form-control" name="title"
                            value="<?php echo $editModule ? htmlspecialchars($editModule['title']) : ''; ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" class="form-control" name="description"
                            value="<?php echo $editModule ? htmlspecialchars($editModule['description']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Sort Order *</label>
                        <input type="number" class="form-control" name="sort_order"
                            value="<?php echo $editModule ? $editModule['sort_order'] : count($modules) + 1; ?>" min="1"
                            required>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" name="save_module" class="btn btn-primary px-5"><i
                            class="fas fa-save me-1"></i><?php echo $editModule ? 'Update Module' : 'Add Module'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($modules)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-layer-group fa-3x mb-3 opacity-25"></i>
                <p class="lead">No modules found for this course.</p>
            </div>
        <?php else:
            foreach ($modules as $module): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-body">
                            <h5 class="fw-bold text-truncate"><?php echo htmlspecialchars($module['title']); ?></h5>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($module['description']); ?></p>
                            <span class="badge bg-secondary">Order: <?php echo $module['sort_order']; ?></span>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                            <a href="instructor_lessons.php?module_id=<?php echo $module['id']; ?>"
                                class="btn btn-sm btn-outline-info"><i class="fas fa-video"></i> Lessons</a>
                            <div class="d-flex gap-2">
                                <a href="instructor_modules.php?action=edit&id=<?php echo $module['id']; ?>&course_id=<?php echo $courseId; ?>"
                                    class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <a href="instructor_modules.php?action=delete&id=<?php echo $module['id']; ?>&course_id=<?php echo $courseId; ?>"
                                    class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
    </div>
</div>

<?php
require_once 'instructor_footer.php';
ob_end_flush();
?>