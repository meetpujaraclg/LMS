<?php
// instructor/add_material.php

// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

$pageTitle = "Add Course Material";
require_once 'instructor_header.php';

$instructorId = $_SESSION['instructor_id'];
$success = '';
$error = '';

// Get course ID from URL
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify the course belongs to the instructor
if ($courseId) {
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$courseId, $instructorId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        $_SESSION['error'] = "Course not found or access denied!";
        header("Location: instructor_courses.php");
        exit();
    }
} else {
    $_SESSION['error'] = "No course specified!";
    header("Location: instructor_courses.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $materialType = $_POST['material_type'];
    $displayOrder = (int)$_POST['display_order'];
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    $filePath = null;
    $externalUrl = null;
    $content = null;
    
    // Validate required fields
    if (empty($title) || empty($materialType)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Handle different material types
            switch ($materialType) {
                case 'document':
                    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/courses/materials/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $fileName = 'course_' . $courseId . '_' . uniqid() . '.' . $fileExtension;
                            $filePath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
                                $filePath = 'courses/materials/' . $fileName;
                            } else {
                                $error = "Failed to upload document file.";
                            }
                        } else {
                            $error = "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
                        }
                    } else {
                        $error = "Please select a document file.";
                    }
                    break;
                    
                case 'video':
                    $externalUrl = trim($_POST['video_url']);
                    if (empty($externalUrl)) {
                        $error = "Please provide a video URL.";
                    }
                    break;
                    
                case 'link':
                    $externalUrl = trim($_POST['link_url']);
                    if (empty($externalUrl)) {
                        $error = "Please provide a link URL.";
                    }
                    break;
                    
                case 'quiz':
                    $content = trim($_POST['quiz_content']);
                    if (empty($content)) {
                        $error = "Please provide quiz content or instructions.";
                    }
                    break;
                    
                case 'assignment':
                    $content = trim($_POST['assignment_content']);
                    if (empty($content)) {
                        $error = "Please provide assignment instructions.";
                    }
                    break;
            }
            
            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, instructor_id, title, description, material_type, file_path, external_url, content, display_order, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$courseId, $instructorId, $title, $description, $materialType, $filePath, $externalUrl, $content, $displayOrder, $isPublished]);
                
                $success = "Course material added successfully!";
                
                // Clear form if success
                if ($success) {
                    $_POST = array();
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get existing materials for this course
$materialsStmt = $pdo->prepare("
    SELECT * FROM course_materials 
    WHERE course_id = ? 
    ORDER BY display_order, created_at
");
$materialsStmt->execute([$courseId]);
$materials = $materialsStmt->fetchAll();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Add Course Material</h1>
            <p class="mb-0">Add learning materials for: <strong><?php echo htmlspecialchars($course['title']); ?></strong></p>
        </div>
        <div>
            <a href="instructor_courses.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add Material Form -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Material</h6>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Material Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Brief description of this material..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="material_type" class="form-label">Material Type *</label>
                                <select class="form-select" id="material_type" name="material_type" required>
                                    <option value="">Select Type</option>
                                    <option value="document" <?php echo (isset($_POST['material_type']) && $_POST['material_type'] == 'document') ? 'selected' : ''; ?>>Document</option>
                                    <option value="video" <?php echo (isset($_POST['material_type']) && $_POST['material_type'] == 'video') ? 'selected' : ''; ?>>Video</option>
                                    <option value="link" <?php echo (isset($_POST['material_type']) && $_POST['material_type'] == 'link') ? 'selected' : ''; ?>>External Link</option>
                                    <option value="quiz" <?php echo (isset($_POST['material_type']) && $_POST['material_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                    <option value="assignment" <?php echo (isset($_POST['material_type']) && $_POST['material_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" 
                                       value="<?php echo isset($_POST['display_order']) ? $_POST['display_order'] : '0'; ?>" 
                                       min="0">
                                <div class="form-text">Lower numbers appear first</div>
                            </div>
                        </div>
                        
                        <!-- Dynamic fields based on material type -->
                        <div id="material-type-fields">
                            <!-- Document Upload -->
                            <div class="material-field document-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="document_file" class="form-label">Document File *</label>
                                    <input type="file" class="form-control" id="document_file" name="document_file" 
                                           accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip">
                                    <div class="form-text">Supported: PDF, DOC, DOCX, PPT, PPTX, TXT, ZIP</div>
                                </div>
                            </div>
                            
                            <!-- Video URL -->
                            <div class="material-field video-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="video_url" class="form-label">Video URL *</label>
                                    <input type="url" class="form-control" id="video_url" name="video_url" 
                                           placeholder="https://youtube.com/embed/..." 
                                           value="<?php echo isset($_POST['video_url']) ? htmlspecialchars($_POST['video_url']) : ''; ?>">
                                    <div class="form-text">Enter YouTube embed URL or direct video link</div>
                                </div>
                            </div>
                            
                            <!-- External Link -->
                            <div class="material-field link-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="link_url" class="form-label">Link URL *</label>
                                    <input type="url" class="form-control" id="link_url" name="link_url" 
                                           placeholder="https://example.com" 
                                           value="<?php echo isset($_POST['link_url']) ? htmlspecialchars($_POST['link_url']) : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Quiz Content -->
                            <div class="material-field quiz-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="quiz_content" class="form-label">Quiz Content/Instructions *</label>
                                    <textarea class="form-control" id="quiz_content" name="quiz_content" rows="4"
                                              placeholder="Enter quiz questions, instructions, or embed code..."><?php echo isset($_POST['quiz_content']) ? htmlspecialchars($_POST['quiz_content']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Assignment Content -->
                            <div class="material-field assignment-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="assignment_content" class="form-label">Assignment Instructions *</label>
                                    <textarea class="form-control" id="assignment_content" name="assignment_content" rows="4"
                                              placeholder="Enter assignment instructions, requirements, and submission guidelines..."><?php echo isset($_POST['assignment_content']) ? htmlspecialchars($_POST['assignment_content']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" 
                                   <?php echo (!isset($_POST['is_published']) || $_POST['is_published']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Publish material (visible to students)</label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" name="add_material" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Material
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Existing Materials -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Existing Materials</h6>
                    <span class="badge bg-primary"><?php echo count($materials); ?> materials</span>
                </div>
                <div class="card-body">
                    <?php if (empty($materials)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No materials added yet</h5>
                            <p class="text-muted">Add your first course material using the form</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($materials as $material): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <?php
                                            $materialIcons = [
                                                'document' => 'file-pdf',
                                                'video' => 'video',
                                                'link' => 'link',
                                                'quiz' => 'question-circle',
                                                'assignment' => 'tasks'
                                            ];
                                            $icon = $materialIcons[$material['material_type']] ?? 'file';
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                                            <?php if (!$material['is_published']): ?>
                                                <span class="badge bg-warning ms-2">Draft</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            Type: <?php echo ucfirst($material['material_type']); ?> • 
                                            Order: <?php echo $material['display_order']; ?> • 
                                            Added: <?php echo date('M j, Y', strtotime($material['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_material.php?id=<?php echo $material['id']; ?>&course_id=<?php echo $courseId; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_material.php?id=<?php echo $material['id']; ?>&course_id=<?php echo $courseId; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this material?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide material type specific fields
document.getElementById('material_type').addEventListener('change', function() {
    const type = this.value;
    
    // Hide all material fields
    document.querySelectorAll('.material-field').forEach(field => {
        field.style.display = 'none';
        // Clear required attributes
        field.querySelectorAll('input, textarea, select').forEach(input => {
            input.removeAttribute('required');
        });
    });
    
    // Show relevant field and set required
    if (type) {
        const field = document.querySelector('.' + type + '-field');
        if (field) {
            field.style.display = 'block';
            field.querySelectorAll('input, textarea, select').forEach(input => {
                input.setAttribute('required', 'required');
            });
        }
    }
});

// Trigger change on page load to show correct fields
document.addEventListener('DOMContentLoaded', function() {
    const materialType = document.getElementById('material_type');
    if (materialType.value) {
        materialType.dispatchEvent(new Event('change'));
    }
});
</script>

<?php 
// End output buffering and flush
ob_end_flush();
require_once 'instructor_footer.php'; 
?>