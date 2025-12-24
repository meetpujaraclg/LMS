<?php
// instructor/add_material_simple.php
error_reporting(0);
session_start();
require_once '../includes/config.php';

// Check if user is instructor
if (!isset($_SESSION['instructor_id']) || $_SESSION['user_role'] != 'instructor') {
    header("Location: ../login.php");
    exit();
}

$instructorId = $_SESSION['instructor_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify course belongs to instructor
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$courseId, $instructorId]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found or access denied.");
}

// Handle form submission
if ($_POST['submit']) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $materialType = $_POST['material_type'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, instructor_id, title, description, material_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $instructorId, $title, $description, $materialType]);
        
        $success = "Material added successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get existing materials
$materialsStmt = $pdo->prepare("SELECT * FROM course_materials WHERE course_id = ? ORDER BY created_at DESC");
$materialsStmt->execute([$courseId]);
$materials = $materialsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Add Material to: <?php echo htmlspecialchars($course['title']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Material Type</label>
                                <select name="material_type" class="form-control" required>
                                    <option value="document">Document</option>
                                    <option value="video">Video</option>
                                    <option value="link">Link</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="assignment">Assignment</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="submit" value="1" class="btn btn-primary">Add Material</button>
                            <a href="instructor_courses.php" class="btn btn-secondary">Back to Courses</a>
                        </form>
                        
                        <hr>
                        
                        <h5>Existing Materials</h5>
                        <?php if (empty($materials)): ?>
                            <p class="text-muted">No materials added yet.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($materials as $material): ?>
                                    <div class="list-group-item">
                                        <h6><?php echo htmlspecialchars($material['title']); ?></h6>
                                        <small class="text-muted">
                                            Type: <?php echo $material['material_type']; ?> | 
                                            Created: <?php echo date('M j, Y', strtotime($material['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>