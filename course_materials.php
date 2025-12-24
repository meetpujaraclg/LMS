<?php
// course_materials.php

// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify course exists and user is enrolled
$enrollmentStmt = $pdo->prepare("
    SELECT c.*, e.progress as course_progress 
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    WHERE c.id = ? AND e.user_id = ? AND c.is_published = 1
");
$enrollmentStmt->execute([$course_id, $user_id]);
$course = $enrollmentStmt->fetch();

if (!$course) {
    header("Location: courses.php");
    exit();
}

$pageTitle = $course['title'] . " - Materials";
include 'includes/header.php';

// Handle progress updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $material_id = (int)$_POST['material_id'];
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;
    $progress_percentage = (int)$_POST['progress_percentage'];
    
    try {
        // Check if progress record exists
        $checkStmt = $pdo->prepare("SELECT id FROM student_material_progress WHERE student_id = ? AND material_id = ?");
        $checkStmt->execute([$user_id, $material_id]);
        $existingProgress = $checkStmt->fetch();
        
        if ($existingProgress) {
            // Update existing progress
            $updateStmt = $pdo->prepare("
                UPDATE student_material_progress 
                SET is_completed = ?, progress_percentage = ?, updated_at = NOW(),
                    completed_at = CASE WHEN ? = 1 THEN NOW() ELSE completed_at END
                WHERE student_id = ? AND material_id = ?
            ");
            $updateStmt->execute([$is_completed, $progress_percentage, $is_completed, $user_id, $material_id]);
        } else {
            // Insert new progress record
            $insertStmt = $pdo->prepare("
                INSERT INTO student_material_progress (student_id, material_id, course_id, is_completed, progress_percentage, completed_at)
                VALUES (?, ?, ?, ?, ?, CASE WHEN ? = 1 THEN NOW() ELSE NULL END)
            ");
            $insertStmt->execute([$user_id, $material_id, $course_id, $is_completed, $progress_percentage, $is_completed]);
        }
        
        // Update overall course progress
        updateCourseProgress($user_id, $course_id, $pdo);
        
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Function to update overall course progress
function updateCourseProgress($user_id, $course_id, $pdo) {
    // Get total materials count
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM course_materials WHERE course_id = ? AND is_published = 1");
    $totalStmt->execute([$course_id]);
    $totalMaterials = $totalStmt->fetchColumn();
    
    if ($totalMaterials > 0) {
        // Get completed materials count
        $completedStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM student_material_progress smp
            JOIN course_materials cm ON smp.material_id = cm.id
            WHERE smp.student_id = ? AND cm.course_id = ? AND smp.is_completed = 1
        ");
        $completedStmt->execute([$user_id, $course_id]);
        $completedMaterials = $completedStmt->fetchColumn();
        
        // Calculate progress percentage
        $progress = round(($completedMaterials / $totalMaterials) * 100);
        
        // Update enrollment progress
        $updateStmt = $pdo->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
        $updateStmt->execute([$progress, $user_id, $course_id]);
    }
}

// Get course materials
$materialsStmt = $pdo->prepare("
    SELECT cm.*, 
           smp.is_completed, 
           smp.progress_percentage,
           smp.completed_at,
           smp.time_spent
    FROM course_materials cm
    LEFT JOIN student_material_progress smp ON cm.id = smp.material_id AND smp.student_id = ?
    WHERE cm.course_id = ? AND cm.is_published = 1
    ORDER BY cm.display_order, cm.created_at
");
$materialsStmt->execute([$user_id, $course_id]);
$materials = $materialsStmt->fetchAll();

// Calculate overall progress
$totalMaterials = count($materials);
$completedMaterials = 0;
foreach ($materials as $material) {
    if ($material['is_completed']) {
        $completedMaterials++;
    }
}
$overallProgress = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100) : 0;
?>

<div class="container-fluid py-4">
    <!-- Course Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="courses.php">My Courses</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['title']); ?></li>
                                </ol>
                            </nav>
                            <h1 class="h3 mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($course['description']); ?></p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="progress-wrapper">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Course Progress</small>
                                    <strong><?php echo $overallProgress; ?>%</strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $overallProgress; ?>%;"></div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $completedMaterials; ?> of <?php echo $totalMaterials; ?> materials completed
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Materials List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Course Materials</h6>
                    <span class="badge bg-primary"><?php echo $totalMaterials; ?> materials</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($materials)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No materials available yet</h4>
                            <p class="text-muted">The instructor hasn't added any learning materials for this course.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($materials as $material): ?>
                                <div class="list-group-item material-item" data-material-id="<?php echo $material['id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-1 text-center">
                                            <div class="material-icon">
                                                <?php
                                                $materialIcons = [
                                                    'document' => 'file-pdf text-danger',
                                                    'video' => 'video text-primary',
                                                    'link' => 'link text-info',
                                                    'quiz' => 'question-circle text-warning',
                                                    'assignment' => 'tasks text-success'
                                                ];
                                                $iconClass = $materialIcons[$material['material_type']] ?? 'file text-muted';
                                                ?>
                                                <i class="fas fa-<?php echo $iconClass; ?> fa-2x"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($material['title']); ?></h6>
                                            <p class="text-muted mb-1 small"><?php echo htmlspecialchars($material['description']); ?></p>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-3">
                                                    <i class="fas fa-<?php echo $material['material_type'] == 'video' ? 'play-circle' : 'file'; ?> me-1"></i>
                                                    <?php echo ucfirst($material['material_type']); ?>
                                                </small>
                                                <?php if ($material['is_completed']): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Completed on <?php echo date('M j, Y', strtotime($material['completed_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="progress-wrapper">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <small class="text-muted">Progress</small>
                                                    <small class="fw-bold"><?php echo $material['progress_percentage']; ?>%</small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar <?php echo $material['is_completed'] ? 'bg-success' : 'bg-primary'; ?>" 
                                                         style="width: <?php echo $material['progress_percentage']; ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-primary btn-sm view-material" 
                                                    data-material="<?php echo htmlspecialchars(json_encode($material)); ?>">
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                        </div>
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

<!-- Material View Modal -->
<div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materialModalTitle">Material Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="materialModalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="markComplete">
                        <label class="form-check-label" for="markComplete">
                            Mark as complete
                        </label>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProgress">Save Progress</button>
            </div>
        </div>
    </div>
</div>

<style>
.material-item {
    transition: background-color 0.2s;
}

.material-item:hover {
    background-color: #f8f9fa;
}

.material-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.progress-wrapper {
    min-width: 100px;
}

.video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>

<script>
let currentMaterialId = null;

// View material
document.querySelectorAll('.view-material').forEach(button => {
    button.addEventListener('click', function() {
        const material = JSON.parse(this.getAttribute('data-material'));
        currentMaterialId = material.id;
        
        // Update modal title
        document.getElementById('materialModalTitle').textContent = material.title;
        
        // Load content based on material type
        let content = '';
        switch (material.material_type) {
            case 'document':
                if (material.file_path) {
                    content = `
                        <div class="text-center py-4">
                            <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                            <h5>${material.title}</h5>
                            <p class="text-muted">${material.description || ''}</p>
                            <a href="../uploads/${material.file_path}" class="btn btn-primary" target="_blank" download>
                                <i class="fas fa-download me-2"></i>Download Document
                            </a>
                        </div>
                        <div class="mt-4">
                            <iframe src="../uploads/${material.file_path}" width="100%" height="500px" style="border: 1px solid #ddd;"></iframe>
                        </div>
                    `;
                }
                break;
                
            case 'video':
                if (material.external_url) {
                    content = `
                        <div class="video-container mb-3">
                            <iframe src="${material.external_url}" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <p>${material.description || ''}</p>
                    `;
                }
                break;
                
            case 'link':
                if (material.external_url) {
                    content = `
                        <div class="alert alert-info">
                            <i class="fas fa-external-link-alt me-2"></i>
                            You are about to visit an external resource.
                        </div>
                        <p>${material.description || ''}</p>
                        <a href="${material.external_url}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>Visit Resource
                        </a>
                    `;
                }
                break;
                
            case 'quiz':
                content = `
                    <div class="alert alert-warning">
                        <i class="fas fa-question-circle me-2"></i>
                        This is a quiz activity.
                    </div>
                    <div class="quiz-content">
                        ${material.content || '<p>No quiz content available.</p>'}
                    </div>
                `;
                break;
                
            case 'assignment':
                content = `
                    <div class="alert alert-success">
                        <i class="fas fa-tasks me-2"></i>
                        This is an assignment activity.
                    </div>
                    <div class="assignment-content">
                        ${material.content || '<p>No assignment instructions available.</p>'}
                    </div>
                `;
                break;
                
            default:
                content = '<p>Material content not available.</p>';
        }
        
        document.getElementById('materialModalContent').innerHTML = content;
        
        // Set completion status
        document.getElementById('markComplete').checked = material.is_completed || false;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('materialModal')).show();
    });
});

// Save progress
document.getElementById('saveProgress').addEventListener('click', function() {
    const isCompleted = document.getElementById('markComplete').checked ? 1 : 0;
    
    // For now, we'll set progress to 100% if completed, otherwise maintain current
    const progressPercentage = isCompleted ? 100 : 50; // In real app, track actual progress
    
    const formData = new FormData();
    formData.append('update_progress', '1');
    formData.append('material_id', currentMaterialId);
    formData.append('is_completed', isCompleted);
    formData.append('progress_percentage', progressPercentage);
    
    fetch('course_materials.php?course_id=<?php echo $course_id; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const materialItem = document.querySelector(`.material-item[data-material-id="${currentMaterialId}"]`);
            if (materialItem) {
                const progressBar = materialItem.querySelector('.progress-bar');
                const progressText = materialItem.querySelector('.fw-bold');
                const completionBadge = materialItem.querySelector('.text-success');
                
                progressBar.style.width = progressPercentage + '%';
                progressBar.className = isCompleted ? 'progress-bar bg-success' : 'progress-bar bg-primary';
                progressText.textContent = progressPercentage + '%';
                
                if (isCompleted && !completionBadge) {
                    const completionElement = document.createElement('small');
                    completionElement.className = 'text-success';
                    completionElement.innerHTML = '<i class="fas fa-check-circle me-1"></i>Completed just now';
                    materialItem.querySelector('.d-flex.align-items-center').appendChild(completionElement);
                }
            }
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();
            
            // Show success message
            showAlert('Progress saved successfully!', 'success');
        } else {
            showAlert('Error saving progress: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error saving progress: ' + error, 'danger');
    });
});

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.container-fluid').firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Auto-save progress for videos (if implemented)
function setupVideoProgressTracking(videoElement) {
    let saveTimeout;
    
    videoElement.addEventListener('timeupdate', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const progress = (videoElement.currentTime / videoElement.duration) * 100;
            updateProgress(progress, progress === 100);
        }, 2000);
    });
    
    function updateProgress(percentage, completed) {
        // Similar to saveProgress function above
        console.log('Video progress:', percentage, '%');
    }
}
</script>

<?php include 'includes/footer.php'; ?>