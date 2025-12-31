<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.* FROM courses c
    INNER JOIN favorites f ON c.id = f.course_id
    WHERE f.user_id = ?
");
$stmt->execute([$userId]);
$favCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4 text-primary"><i class="fas fa-heart me-2"></i>My Favorite Courses</h2>

    <?php if (empty($favCourses)): ?>
        <div class="alert alert-info">You haven’t added any favorite courses yet ❤️</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favCourses as $course): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <?php if ($course['thumbnail']): ?>
                            <img src="uploads/<?= htmlspecialchars($course['thumbnail']); ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(mb_strimwidth($course['description'], 0, 100, '...')); ?></p>
                            <a href="course-detail.php?id=<?= $course['id']; ?>" class="btn btn-primary btn-sm">View Course</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
