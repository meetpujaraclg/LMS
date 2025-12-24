<?php
// course_materials_simple.php
error_reporting(0);
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Check if user is enrolled
$enrollmentStmt = $pdo->prepare("
    SELECT c.* 
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    WHERE c.id = ? AND e.user_id = ?
");
$enrollmentStmt->execute([$courseId, $userId]);
$course = $enrollmentStmt->fetch();

if (!$course) {
    die("Course not found or you are not enrolled.");
}

// Handle progress update
if ($_POST['mark_complete']) {
    $materialId = (int)$_POST['material_id'];
    
    try {
        // Check if progress exists
        $checkStmt = $pdo->prepare("SELECT id FROM student_material_progress WHERE student_id = ? AND material_id = ?");
        $checkStmt->execute([$userId, $materialId]);
        
        if ($checkStmt->fetch()) {
            // Update
            $updateStmt = $pdo->prepare("UPDATE student_material_progress SET is_completed = 1, completed_at = NOW() WHERE student_id = ? AND material_id = ?");
            $updateStmt->execute([$userId, $materialId]);
        } else {
            // Insert
            $insertStmt = $pdo->prepare("INSERT INTO student_material_progress (student_id, material_id, course_id, is_completed, completed_at) VALUES (?, ?, ?, 1, NOW())");
            $insertStmt->execute([$userId, $materialId, $courseId]);
        }
        
        // Update course progress
        updateCourseProgress($userId, $courseId, $pdo);
        
        $success = "Progress updated!";
    } catch (PDOException $e) {
        $error = "Error updating progress: " . $e->getMessage();
    }
}

// Function to update course progress
function updateCourseProgress($userId, $courseId, $pdo) {
    // Count total materials
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM course_materials WHERE course_id = ? AND is_published = 1");
    $totalStmt->execute([$courseId]);
    $total = $totalStmt->fetchColumn();
    
    // Count completed materials
    $completedStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM student_material_progress smp
        JOIN course_materials cm ON smp.material_id = cm.id
        WHERE smp.student_id = ? AND cm.course_id = ? AND smp.is_completed = 1
    ");
    $completedStmt->execute([$userId, $courseId]);
    $completed = $completedStmt->fetchColumn();
    
    // Calculate percentage
    $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
    
    // Update enrollment
    $updateStmt = $pdo->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
    $updateStmt->execute([$progress, $userId, $courseId]);
}

// Get materials with progress
$materialsStmt = $pdo->prepare("
    SELECT cm.*, 
           COALESCE(smp.is_completed, 0) as is_completed,
           smp.completed_at
    FROM course_materials cm
    LEFT JOIN student_material_progress smp ON cm.id = smp.material_id AND smp.student_id = ?
    WHERE cm.course_id = ? AND cm.is_published = 1
    ORDER BY cm.created_at
");
$materialsStmt->execute([$userId, $courseId]);
$materials = $materialsStmt->fetchAll();

// Calculate progress
$totalMaterials = count($materials);
$completedMaterials = 0;
foreach ($materials as $material) {
    if ($material['is_completed']) {
        $completedMaterials++;
    }
}
$progressPercent = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Materials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Header -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="text-muted"><?php echo htmlspecialchars($course['description']); ?></p>
                        
                        <!-- Progress Bar -->
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $progressPercent; ?>%">
                                <?php echo $progressPercent; ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $completedMaterials; ?> of <?php echo $totalMaterials; ?> materials completed
                        </small>
                    </div>
                </div>
                
                <!-- Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Materials List -->
                <div class="card">
                    <div class="card-header">
                        <h4>Course Materials</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                            <p class="text-muted">No materials available for this course.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($materials as $material): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6><?php echo htmlspecialchars($material['title']); ?></h6>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($material['description']); ?></p>
                                                <small class="text-muted">
                                                    Type: <?php echo $material['material_type']; ?>
                                                    <?php if ($material['is_completed']): ?>
                                                        | <span class="text-success">Completed on <?php echo date('M j, Y', strtotime($material['completed_at'])); ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($material['is_completed']): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                                        <button type="submit" name="mark_complete" value="1" class="btn btn-success btn-sm">
                                                            Mark Complete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="my_courses.php" class="btn btn-secondary">Back to My Courses</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>