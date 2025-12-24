<?php
// my_courses.php
error_reporting(0);
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get enrolled courses
$stmt = $pdo->prepare("
    SELECT c.*, e.progress 
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    WHERE e.user_id = ? 
    ORDER BY e.created_at DESC
");
$stmt->execute([$userId]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>My Courses</h2>
        
        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                You are not enrolled in any courses yet.
                <a href="courses.php" class="alert-link">Browse courses</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                <p class="card-text"><?php echo substr($course['description'], 0, 100); ?>...</p>
                                
                                <!-- Progress -->
                                <div class="progress mb-2">
                                    <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%">
                                        <?php echo $course['progress']; ?>%
                                    </div>
                                </div>
                                
                                <a href="course_materials_simple.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                    Continue Learning
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>