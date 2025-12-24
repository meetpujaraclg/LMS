<?php
require_once 'admin_header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid instructor ID!";
    header("Location: instructors.php");
    exit();
}

$instructorId = (int) $_GET['id'];

// 🧠 Fetch instructor details
$stmt = $admin_pdo->prepare("
    SELECT i.*, 
           (SELECT COUNT(*) FROM courses c WHERE c.instructor_id = i.id) AS course_count,
           (SELECT COUNT(*) FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            WHERE c.instructor_id = i.id) AS total_enrollments
    FROM instructors i
    WHERE i.id = ?
");
$stmt->execute([$instructorId]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instructor) {
    $_SESSION['error'] = "Instructor not found!";
    header("Location: instructors.php");
    exit();
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Instructor Details</h1>
    <a href="instructors.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <!-- 🧑 Profile Info -->
            <div class="col-md-3 text-center border-end">
                <img src="<?php echo !empty($instructor['profile_picture']) && file_exists("../uploads/instructors/" . $instructor['profile_picture'])
                    ? "../uploads/instructors/" . htmlspecialchars($instructor['profile_picture'])
                    : 'https://via.placeholder.com/150'; ?>" class="rounded-circle shadow-sm mb-3" width="120"
                    height="120" style="object-fit: cover;">
                <h5><?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></h5>
                <p class="text-muted mb-1"><i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($instructor['email']); ?></p>
                <p class="text-muted"><i class="fas fa-calendar"></i> Joined:
                    <?php echo date('M j, Y', strtotime($instructor['created_at'])); ?></p>
            </div>

            <!-- 📋 Detailed Info -->
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Experience:</label>
                        <p><?php echo !empty($instructor['experience']) ? htmlspecialchars($instructor['experience']) : 'N/A'; ?>
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Expertise Area:</label>
                        <p><?php echo !empty($instructor['expertise_area']) ? htmlspecialchars($instructor['expertise_area']) : 'N/A'; ?>
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Courses Created:</label>
                        <p><span class="badge bg-primary"><?php echo $instructor['course_count']; ?></span></p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Total Enrollments:</label>
                        <p><span class="badge bg-info"><?php echo $instructor['total_enrollments']; ?></span></p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Profile Status:</label>
                        <?php if ($instructor['profile_status'] == 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php elseif ($instructor['profile_status'] == 'inactive'): ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">Verified:</label>
                        <p><?php echo $instructor['verified'] ? '<span class="text-success fw-bold">Yes</span>' : '<span class="text-danger fw-bold">No</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- 🪪 Confidential Docs -->
                <hr>
                <h5 class="fw-bold mt-3"><i class="fas fa-file-shield"></i> Confidential Documents</h5>
                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <label>ID Proof:</label><br>
                        <?php if (!empty($instructor['id_proof']) && file_exists("../uploads/instructors/" . $instructor['id_proof'])): ?>
                            <a href="../uploads/instructors/<?php echo urlencode($instructor['id_proof']); ?>"
                                target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> View ID Proof
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not Uploaded</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Qualification Proof:</label><br>
                        <?php if (!empty($instructor['qualification']) && file_exists("../uploads/instructors/" . $instructor['qualification'])): ?>
                            <a href="../uploads/instructors/<?php echo urlencode($instructor['qualification']); ?>"
                                target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-pdf"></i> View Qualification
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not Uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 🧾 Optional Biography -->
                <?php if (!empty($instructor['bio'])): ?>
                    <hr>
                    <h5 class="fw-bold"><i class="fas fa-user-pen"></i> Bio</h5>
                    <p><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>