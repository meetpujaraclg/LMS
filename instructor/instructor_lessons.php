<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
$pageTitle = "Manage Lessons";
require_once 'instructor_header.php';

if (!function_exists('admin_sanitize')) {
    function admin_sanitize($data)
    {
        return htmlspecialchars(trim($data));
    }
}

function getVideoDuration($filePath)
{
    $fullPath = realpath($filePath);
    if (!$fullPath) {
        return 0;
    }
    $cmd = "ffprobe -v error -show_entries format=duration "
        . "-of default=noprint_wrappers=1:nokey=1 \"$fullPath\"";
    $output = shell_exec($cmd);
    $duration = floatval($output);
    return (int) round($duration / 60);
}

if (!isset($_GET['module_id'])) {
    header("Location: instructor_modules.php");
    exit();
}

$moduleId = (int) $_GET['module_id'];

$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE id = ?");
$stmt->execute([$moduleId]);
$module = $stmt->fetch();

if (!$module) {
    header("Location: instructor_modules.php");
    exit();
}

/* ---------- Add / Update Lesson ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lesson'])) {

    $title = admin_sanitize($_POST['title']);
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    $video_url = isset($_POST['video_url']) ? trim($_POST['video_url']) : null;
    $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
    $video_path = null;
    $duration = 0;
    $lessonId = isset($_POST['lesson_id']) ? (int) $_POST['lesson_id'] : 0;

    // Handle video upload
    $uploadDir = '../uploads/videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $allowed = ['mp4', 'mov', 'avi', 'mkv'];
        if (in_array(strtolower($fileExtension), $allowed)) {
            $fileName = uniqid('lesson_', true) . '.' . $fileExtension;
            if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadDir . $fileName)) {
                $video_path = 'videos/' . $fileName;
                $duration = getVideoDuration($uploadDir . $fileName);
            }
        }
    }

    try {
        $pdo->beginTransaction();

        if ($lessonId > 0) {
            // UPDATE existing lesson
            $check = $pdo->prepare("SELECT video_path, duration FROM lessons WHERE id = ? AND module_id = ?");
            $check->execute([$lessonId, $moduleId]);
            $existing = $check->fetch();

            $finalVideoPath = $existing ? $existing['video_path'] : null;
            $finalDuration = $existing ? $existing['duration'] : 0;

            if ($video_path) {
                if ($existing && $existing['video_path'] && file_exists('../uploads/' . $existing['video_path'])) {
                    unlink('../uploads/' . $existing['video_path']);
                }
                $finalVideoPath = $video_path;
                $finalDuration = $duration;
            }

            $stmt = $pdo->prepare("
                UPDATE lessons SET title = ?, content = ?, video_url = ?, duration = ?, 
                video_path = ?, sort_order = ? WHERE id = ? AND module_id = ?
            ");
            $stmt->execute([$title, $content, $video_url, $finalDuration, $finalVideoPath, $sort_order, $lessonId, $moduleId]);

        } else {
            // CREATE new lesson - CAPTURE lessonId IMMEDIATELY
            $stmt = $pdo->prepare("
                INSERT INTO lessons (module_id, title, content, video_url, duration, video_path, sort_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$moduleId, $title, $content, $video_url, $duration, $video_path, $sort_order]);
            $lessonId = $pdo->lastInsertId(); // ✅ FIXED: Now $lessonId is SET
        }

        // Handle MULTIPLE materials upload - NOW lessonId is guaranteed
        $materials_dir = '../uploads/materials/';
        if (!is_dir($materials_dir)) {
            mkdir($materials_dir, 0755, true);
        }

        if (isset($_FILES['materials']) && is_array($_FILES['materials']['name'])) {
            foreach ($_FILES['materials']['name'] as $key => $originalName) {
                if ($_FILES['materials']['error'][$key] === UPLOAD_ERR_OK && !empty(trim($originalName))) {
                    $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $allowed_materials = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'xlsx', 'xls'];

                    if (in_array(strtolower($fileExtension), $allowed_materials)) {
                        $fileName = uniqid('material_', true) . '.' . $fileExtension;
                        $fullPath = $materials_dir . $fileName;

                        if (move_uploaded_file($_FILES['materials']['tmp_name'][$key], $fullPath)) {
                            $fileSize = filesize($fullPath);

                            $stmt = $pdo->prepare("
                                INSERT INTO lesson_materials (lesson_id, original_name, file_path, file_size) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->execute([$lessonId, $originalName, 'materials/' . $fileName, $fileSize]);
                        }
                    }
                }
            }
        }

        // Update course duration
        $totalDurationStmt = $pdo->prepare("
            SELECT SUM(l.duration) AS total_minutes FROM lessons l 
            JOIN course_modules m ON l.module_id = m.id WHERE m.course_id = ?
        ");
        $totalDurationStmt->execute([$module['course_id']]);
        $totalHours = round((int) $totalDurationStmt->fetchColumn() / 60, 1);

        $updateCourseStmt = $pdo->prepare("UPDATE courses SET duration = ? WHERE id = ?");
        $updateCourseStmt->execute([$totalHours, $module['course_id']]);

        $pdo->commit();
        $_SESSION['success'] = "Lesson " . ($lessonId > 0 ? 'updated' : 'added') . " successfully with materials!";

        ob_end_clean();
        header("Location: instructor_lessons.php?module_id={$moduleId}");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
}

/* ---------- Delete Lesson ---------- */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $lessonId = (int) $_GET['id'];

    try {
        $pdo->beginTransaction();

        // Get lesson video path
        $stmt = $pdo->prepare("SELECT video_path FROM lessons WHERE id = ? AND module_id = ?");
        $stmt->execute([$lessonId, $moduleId]);
        $lesson = $stmt->fetch();

        if ($lesson) {
            // Delete video file
            if ($lesson['video_path'] && file_exists('../uploads/' . $lesson['video_path'])) {
                unlink('../uploads/' . $lesson['video_path']);
            }

            // Delete ALL material files for this lesson
            $matStmt = $pdo->prepare("SELECT file_path FROM lesson_materials WHERE lesson_id = ?");
            $matStmt->execute([$lessonId]);
            while ($mat = $matStmt->fetch()) {
                if ($mat['file_path'] && file_exists('../uploads/' . $mat['file_path'])) {
                    unlink('../uploads/' . $mat['file_path']);
                }
            }

            // Delete DB records
            $pdo->prepare("DELETE FROM lesson_materials WHERE lesson_id = ?")->execute([$lessonId]);
            $pdo->prepare("DELETE FROM lessons WHERE id = ?")->execute([$lessonId]);

            // Update course duration
            $totalDurationStmt = $pdo->prepare("
                SELECT SUM(l.duration) AS total_minutes FROM lessons l 
                JOIN course_modules m ON l.module_id = m.id WHERE m.course_id = ?
            ");
            $totalDurationStmt->execute([$module['course_id']]);
            $totalHours = round((int) $totalDurationStmt->fetchColumn() / 60, 1);

            $pdo->prepare("UPDATE courses SET duration = ? WHERE id = ?")->execute([$totalHours, $module['course_id']]);

            $pdo->commit();
            $_SESSION['success'] = "Lesson and all materials deleted!";
        }

        ob_end_clean();
        header("Location: instructor_lessons.php?module_id={$moduleId}");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Delete error: " . $e->getMessage();
    }
}

/* ---------- Delete Material ---------- */
if (isset($_GET['action']) && $_GET['action'] === 'delete_material' && isset($_GET['id'])) {
    $matId = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT file_path FROM lesson_materials WHERE id = ?");
    $stmt->execute([$matId]);
    $mat = $stmt->fetch();

    if ($mat && file_exists('../uploads/' . $mat['file_path'])) {
        unlink('../uploads/' . $mat['file_path']);
    }

    $pdo->prepare("DELETE FROM lesson_materials WHERE id = ?")->execute([$matId]);
    $_SESSION['success'] = "Material deleted successfully!";

    ob_end_clean();
    header("Location: instructor_lessons.php?module_id={$moduleId}");
    exit();
}

/* ---------- Alerts ---------- */
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show m-4" role="alert">'
        . $_SESSION['success']
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show m-4" role="alert">'
        . $_SESSION['error']
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error']);
}

/* ---------- Edit Lesson ---------- */
$editLesson = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? AND module_id = ?");
    $stmt->execute([(int) $_GET['id'], $moduleId]);
    $editLesson = $stmt->fetch();
}

/* ---------- Fetch lessons with materials count ---------- */
$stmt = $pdo->prepare("
    SELECT l.*, 
           COALESCE((SELECT COUNT(*) FROM lesson_materials m WHERE m.lesson_id = l.id), 0) as materials_count,
           COALESCE((SELECT COUNT(*) FROM quizzes q WHERE q.lesson_id = l.id), 0) as quiz_count
    FROM lessons l WHERE l.module_id = ? ORDER BY l.sort_order ASC, l.created_at DESC
");
$stmt->execute([$moduleId]);
$lessons = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="p-4 mb-4 rounded-3 text-white" style="background:linear-gradient(90deg,#0062E6,#33AEFF);">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h2 class="fw-bold mb-0">
                <i class="fas fa-video me-2"></i>Manage Lessons - <?php echo htmlspecialchars($module['title']); ?>
            </h2>
            <a href="instructor_modules.php?course_id=<?php echo $module['course_id']; ?>"
                class="btn btn-light fw-semibold shadow-sm">
                <i class="fas fa-arrow-left"></i> Back to Modules
            </a>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editLesson): ?>
                    <input type="hidden" name="lesson_id" value="<?php echo (int) $editLesson['id']; ?>">
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lesson Title *</label>
                        <input type="text" class="form-control" name="title"
                            value="<?php echo $editLesson ? htmlspecialchars($editLesson['title']) : ''; ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" min="0"
                            value="<?php echo $editLesson ? (int) $editLesson['sort_order'] : 0; ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Upload Video</label>
                        <input type="file" class="form-control" name="video" accept="video/*">
                        <?php if ($editLesson && $editLesson['video_path']): ?>
                            <small class="text-muted d-block mt-1">Current:
                                <?php echo basename($editLesson['video_path']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Upload Materials</label>
                        <input type="file" class="form-control" name="materials[]" multiple
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.xlsx,.xls">

                        <?php if ($editLesson): ?>
                            <?php
                            $matStmt = $pdo->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY id DESC");
                            $matStmt->execute([$editLesson['id']]);
                            $currentMaterials = $matStmt->fetchAll();
                            ?>
                            <?php if (!empty($currentMaterials)): ?>
                                <div class="mb-2">
                                    <?php foreach ($currentMaterials as $mat): ?>
                                        <span class="badge bg-success me-1 mb-1 d-inline-flex align-items-center position-relative">
                                            <a href="../uploads/<?= htmlspecialchars($mat['file_path']); ?>" target="_blank"
                                                class="text-white text-decoration-none pe-1">
                                                <?= htmlspecialchars(substr($mat['original_name'], 0, 12)); ?>
                                            </a>
                                            <a href="?action=delete_material&id=<?= $mat['id']; ?>&module_id=<?= $moduleId; ?>"
                                                class="btn btn-sm p-0 ms-1 text-danger border-0 bg-transparent text-decoration-none"
                                                onclick="return confirm('Delete this material?')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>


                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Video URL</label>
                        <input type="text" class="form-control" name="video_url"
                            value="<?php echo $editLesson ? htmlspecialchars($editLesson['video_url']) : ''; ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Content</label>
                        <textarea class="form-control" name="content" rows="4"><?php
                        echo $editLesson ? htmlspecialchars($editLesson['content']) : '';
                        ?></textarea>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" name="save_lesson" class="btn btn-primary px-5">
                        <i class="fas fa-save me-1"></i>
                        <?php echo $editLesson ? 'Update Lesson' : 'Add Lesson'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lessons List -->
    <div class="row g-4">
        <?php if (empty($lessons)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-film fa-3x mb-3 opacity-25"></i>
                <p class="lead">No lessons found in this module.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lessons as $lesson): ?>
                <?php
                $matStmt = $pdo->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ? ORDER BY id DESC");
                $matStmt->execute([$lesson['id']]);
                $materials = $matStmt->fetchAll();
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="ratio ratio-16x9">
                            <?php if ($lesson['video_path']): ?>
                                <video src="../uploads/<?php echo $lesson['video_path']; ?>" controls preload="metadata"></video>
                            <?php elseif ($lesson['video_url']): ?>
                                <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" title="Lesson video"
                                    allowfullscreen></iframe>
                            <?php else: ?>
                                <div class="bg-light d-flex justify-content-center align-items-center">
                                    <i class="fas fa-video text-secondary fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold text-truncate"><?php echo htmlspecialchars($lesson['title']); ?></h6>
                            <p class="text-muted small mb-2">
                                ⏱️ <?php echo (int) $lesson['duration']; ?> mins
                                <?php if ($lesson['materials_count'] > 0): ?>
                                    | 📎 <strong><?php echo $lesson['materials_count']; ?></strong> files
                                <?php endif; ?>
                                <?php if (isset($lesson['quiz_count']) && $lesson['quiz_count'] > 0): ?>
                                    | <i class="fas fa-question-circle text-warning"></i>
                                    <strong><?php echo $lesson['quiz_count']; ?></strong> quizzes
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($lesson['content'])): ?>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($lesson['content'], 0, 80)); ?>...
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($materials)): ?>
                                <div class="mb-2">
                                    <?php foreach ($materials as $mat): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($mat['file_path']); ?>" target="_blank"
                                            class="badge bg-success me-1 mb-1 text-white fw-semibold text-decoration-none"
                                            title="<?php echo htmlspecialchars($mat['original_name']); ?> (<?php echo number_format($mat['file_size'] / 1024, 1); ?> KB)">
                                            📎 <?php echo htmlspecialchars(substr($mat['original_name'], 0, 12)); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if ($lesson['materials_count'] > 4): ?>
                                        <span
                                            class="badge bg-secondary text-white">+<?php echo $lesson['materials_count'] - 4; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                            <div class="d-flex gap-2">
                                <a href="?action=edit&id=<?php echo $lesson['id']; ?>&module_id=<?php echo $moduleId; ?>"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $lesson['id']; ?>&module_id=<?php echo $moduleId; ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete lesson + <?php echo $lesson['materials_count']; ?> materials?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <button type="button"
                                class="btn btn-sm btn-warning auto-generate<?php echo isset($lesson['quiz_count']) && $lesson['quiz_count'] > 0 ? ' btn-outline-warning' : ''; ?>"
                                data-lesson-id="<?php echo $lesson['id']; ?>">
                                <i class="fas fa-magic me-1"></i>
                                <?php echo isset($lesson['quiz_count']) && $lesson['quiz_count'] > 0 ? 'Regenerate' : 'Auto Generate'; ?>
                                <?php if (isset($lesson['quiz_count']) && $lesson['quiz_count'] > 0): ?>
                                    (<small><?php echo $lesson['quiz_count']; ?></small>)
                                <?php endif; ?>
                            </button>


                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.auto-generate');
        if (!btn) return;

        const lessonId = btn.dataset.lessonId;
        const modal = new bootstrap.Modal(document.getElementById('aiProgressModal'));
        const progressBar = document.querySelector('#aiProgressModal .progress-bar');
        const statusText = document.getElementById('ai-status-text');
        const statusIcon = document.getElementById('ai-status-icon');
        const closeBtn = document.getElementById('aiCloseBtn');

        // Reset modal
        progressBar.style.width = '0%';
        closeBtn.classList.add('d-none');
        statusText.textContent = "Preparing...";
        statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i>';
        modal.show();

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        try {
            // Step 1 — Generate quiz via PHP
            statusText.textContent = "Generating quiz...";
            progressBar.style.width = '50%';

            const res = await fetch('generate_auto_quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lesson_id=${encodeURIComponent(lessonId)}`
            });

            const data = await res.json();

            // Step 2 — Finish progress
            progressBar.style.width = '100%';

            if (data.status === 'success') {
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-success');
                statusIcon.innerHTML = '<i class="fas fa-check-circle fa-2x text-success"></i>';
                statusText.textContent = data.message;
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            progressBar.classList.remove('bg-primary');
            progressBar.classList.add('bg-danger');
            statusIcon.innerHTML = '<i class="fas fa-times-circle fa-2x text-danger"></i>';
            statusText.textContent = '⚠️ ' + err.message;
        } finally {
            closeBtn.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
</script>


<!-- Quiz Progress Modal -->
<div class="modal fade" id="aiProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-body text-center">
                <div id="ai-status-icon"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
                <p id="ai-status-text" class="mt-3">Starting...</p>
                <div class="progress mt-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width:0%"></div>
                </div>
                <button id="aiCloseBtn" type="button" class="btn btn-success mt-3 d-none"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'instructor_footer.php';
ob_end_flush(); ?>