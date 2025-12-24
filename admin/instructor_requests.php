<?php
require_once 'check_admin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer_config.php';

$pageTitle = "Admin - Manage Instructor Requests";

// Handle Approve / Reject
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];
    $reason = $_GET['reason'] ?? '';

    try {
        // Fetch instructor details
        $stmt = $admin_pdo->prepare("SELECT first_name, last_name, email FROM instructors WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$instructor) {
            $_SESSION['error'] = "Instructor not found.";
        } else {
            $email = $instructor['email'];
            $name = $instructor['first_name'] . ' ' . $instructor['last_name'];

            if ($action === 'approve') {
                // ✅ Mark active & delete from requests
                $update = $admin_pdo->prepare("UPDATE instructors SET profile_status = 'active', verified = 1 WHERE id = :id");
                $update->execute([':id' => $id]);

                // Delete approved instructor from requests table
                $delete = $admin_pdo->prepare("DELETE FROM instructors WHERE id = :id");
                $delete->execute([':id' => $id]);

                $_SESSION['success'] = "Instructor approved and removed from request list!";

                // ✅ send approval email
                $subject = "Your Instructor Account Has Been Approved!";
                $message = "
                    <h3 style='color:#28a745;'>Congratulations, $name!</h3>
                    <p>Your instructor account has been <b>approved</b> by the admin.</p>
                    <p>You can now log in and start creating and managing your courses.</p>
                    <br>
                    <p>Regards,<br><b>EduTech Team</b></p>
                ";
                sendEmail($email, $subject, $message);
            } elseif ($action === 'reject') {
                if (empty($reason)) {
                    $_SESSION['error'] = "Rejection reason is required!";
                } else {
                    // ❌ Mark inactive & delete request
                    $update = $admin_pdo->prepare("UPDATE instructors SET profile_status = 'inactive', verified = 0 WHERE id = :id");
                    $update->execute([':id' => $id]);

                    // Delete rejected instructor from requests table
                    $delete = $admin_pdo->prepare("DELETE FROM instructors WHERE id = :id");
                    $delete->execute([':id' => $id]);

                    $_SESSION['error'] = "Instructor rejected and removed from request list!";

                    // ❌ send rejection email with reason
                    $subject = "Your Instructor Account Has Been Rejected";
                    $message = "
                        <h3 style='color:#dc3545;'>Hello $name,</h3>
                        <p>We regret to inform you that your instructor account request has been <b>rejected</b>.</p>
                        <p><b>Reason:</b> " . nl2br(htmlspecialchars($reason)) . "</p>
                        <p>Please review the above issue, fix it, and reapply.</p>
                        <br>
                        <p>Regards,<br><b>EduTech Team</b></p>
                    ";
                    sendEmail($email, $subject, $message);
                }
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: instructor_requests.php");
    exit;
}

// Fetch only pending instructors
try {
    $stmt = $admin_pdo->query("SELECT * FROM instructors WHERE profile_status IS NULL OR profile_status = 'pending' ORDER BY id DESC");
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        thead tr {
            background-color: #000 !important;
            color: #fff !important;
        }
    </style>
</head>

<?php include 'admin_header.php'; ?>

<body>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span12" id="content">
                <div class="row-fluid">
                    <h2>Manage Instructor Requests</h2>
                    <hr>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'];
                        unset($_SESSION['success']); ?></div>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'];
                        unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Experience</th>
                                <th>Expertise</th>
                                <th>ID Proof</th>
                                <th>Qualification</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($instructors)): ?>
                                <?php foreach ($instructors as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']); ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= htmlspecialchars($row['experience']); ?></td>
                                        <td><?= htmlspecialchars($row['expertise_area']); ?></td>

                                        <td>
                                            <?php if (!empty($row['id_proof']) && file_exists("../uploads/instructors/" . $row['id_proof'])): ?>
                                                <a href="../uploads/instructors/<?= urlencode($row['id_proof']); ?>" target="_blank"
                                                    style="text-decoration:none;">View</a>
                                            <?php else: ?>N/A<?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($row['qualification']) && file_exists("../uploads/instructors/" . $row['qualification'])): ?>
                                                <a href="../uploads/instructors/<?= urlencode($row['qualification']); ?>"
                                                    target="_blank" style="text-decoration:none;">View</a>
                                            <?php else: ?>N/A<?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        </td>

                                        <td>
                                            <a href="?action=approve&id=<?= $row['id']; ?>" class="btn btn-success btn-sm"
                                                style="margin-bottom:5px;"
                                                onclick="return confirm('Approve this instructor?')">Approve</a>
                                            <button class="btn btn-danger btn-sm"
                                                onclick="rejectWithReason(<?= $row['id']; ?>)">Reject</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No pending instructor requests found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function rejectWithReason(id) {
            const reason = prompt("Please enter the reason for rejection:");
            if (reason && reason.trim() !== "") {
                const encoded = encodeURIComponent(reason.trim());
                window.location.href = `?action=reject&id=${id}&reason=${encoded}`;
            } else {
                alert("Rejection reason is required!");
            }
        }
    </script>
</body>

</html>

<?php require_once 'admin_footer.php'; ?>