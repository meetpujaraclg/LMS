<?php
require_once 'check_admin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer_config.php';

$admin_pageTitle = "Manage Instructor Requests";

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
                $update = $admin_pdo->prepare("UPDATE instructors SET profile_status = 'active', verified = 1 WHERE id = :id");
                $update->execute([':id' => $id]);
                $_SESSION['success'] = "Instructor approved and removed from request list!";
                $subject = "Your Instructor Account Has Been Approved!";
                $message = "
                    <h3 style='color:#28a745;'>Congratulations, $name!</h3>
                    <p>Your instructor account has been <b>approved</b> by the admin.</p>
                    <p>You can now log in and start creating and managing your courses.</p>
                    <br><p>Regards,<br><b>EduTech Team</b></p>
                ";
                sendEmail($email, $subject, $message);
            } elseif ($action === 'reject') {
                if (empty($reason)) {
                    $_SESSION['error'] = "Rejection reason is required!";
                } else {
                    $delete = $admin_pdo->prepare("DELETE FROM instructors WHERE id = :id");
                    $delete->execute([':id' => $id]);
                    $_SESSION['error'] = "Instructor rejected and removed from request list!";
                    $subject = "Your Instructor Account Has Been Rejected";
                    $message = "
                        <h3 style='color:#dc3545;'>Hello $name,</h3>
                        <p>We regret to inform you that your instructor account request has been <b>rejected</b>.</p>
                        <p><b>Reason:</b> " . nl2br(htmlspecialchars($reason)) . "</p>
                        <p>Please review the above issue, fix it, and reapply.</p>
                        <br><p>Regards,<br><b>EduTech Team</b></p>
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

<?php include 'admin_header.php'; ?>

<style>
    /* Blue Glassmorphism - Single Card Design */
    :root {
        --primary-blue: #3b82f6;
        --primary-blue-dark: #1e40af;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.3);
        --glass-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
    }

    /* Single Glass Card */
    .instructor-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        padding: 2.5rem;
        max-width: 900px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }

    .instructor-card:hover {
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.25);
        transform: translateY(-5px);
    }

    /* Header */
    h2 {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    /* Alerts */
    .alert {
        border: none;
        border-radius: 16px;
        backdrop-filter: blur(12px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    /* Instructor Item */
    .instructor-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .instructor-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .instructor-name {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }

    .instructor-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .detail-item {
        background: rgba(59, 130, 246, 0.1);
        padding: 1rem;
        border-radius: 12px;
        border-left: 4px solid var(--primary-blue);
    }

    .detail-label {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .detail-value {
        font-weight: 600;
        color: #1e293b;
        word-break: break-all;
        overflow-wrap: anywhere;
        display: block;
        max-width: 100%;
        white-space: normal;
    }


    /* Document Buttons */
    .doc-btn {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-blue);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        border: 1px solid rgba(59, 130, 246, 0.2);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .doc-btn:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-1px);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn {
        border-radius: 12px;
        border: none;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(12px);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
    }

    .empty-icon {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #475569;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .instructor-card {
            margin: 1rem;
            padding: 2rem;
        }

        .instructor-details {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="container-fluid">
    <div class="instructor-card">

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($instructors)): ?>
            <?php foreach ($instructors as $instructor): ?>
                <div class="instructor-item">
                    <div class="instructor-name">
                        <?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        <span class="badge bg-warning ms-2">⏳ Pending</span>
                    </div>

                    <div class="instructor-details">
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?= htmlspecialchars($instructor['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Experience</div>
                            <div class="detail-value"><?= htmlspecialchars($instructor['experience']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Expertise</div>
                            <div class="detail-value"><?= htmlspecialchars($instructor['expertise_area']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">ID</div>
                            <div class="detail-value">
                                <?php if (!empty($instructor['id_proof']) && file_exists("../uploads/instructors/" . $instructor['id_proof'])): ?>
                                    <a href="../uploads/instructors/<?= urlencode($instructor['id_proof']); ?>" target="_blank"
                                        class="doc-btn">
                                        📄 View ID Proof
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Qualification</div>
                            <div class="detail-value">
                                <?php if (!empty($instructor['qualification']) && file_exists("../uploads/instructors/" . $instructor['qualification'])): ?>
                                    <a href="../uploads/instructors/<?= urlencode($instructor['qualification']); ?>" target="_blank"
                                        class="doc-btn">
                                        📜 View Document
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Demo Teaching Video</div>
                            <div class="detail-value">
                                <?php if (!empty($instructor['demo_video']) && file_exists("../uploads/instructors/" . $instructor['demo_video'])): ?>
                                    <video width="100%" height="240" controls controlsList="no" style="border-radius: 12px; outline: none;">
                                        <source src="../uploads/instructors/<?= urlencode($instructor['demo_video']); ?>"
                                            type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <div class="action-buttons">
                        <a href="?action=approve&id=<?= $instructor['id']; ?>" class="btn btn-success btn-sm"
                            onclick="return confirm('✅ Approve this instructor?')">
                            ✅ Approve
                        </a>
                        <button class="btn btn-danger btn-sm" onclick="rejectWithReason(<?= $instructor['id']; ?>)">
                            ❌ Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👨‍🏫</div>
                <div class="empty-title">No pending instructor requests found</div>
                <p>All instructor requests have been processed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>

    // Disable right-click on videos (prevent save)
    document.addEventListener('contextmenu', function (e) {
        if (e.target.tagName === 'VIDEO') {
            e.preventDefault();
        }
    });

    function rejectWithReason(id) {
        const reason = prompt("Please enter the reason for rejection:");
        if (reason && reason.trim() !== "") {
            const encoded = encodeURIComponent(reason.trim());
            window.location.href = `?action=reject&id=${id}&reason=${encoded}`;
        } else {
            alert("❌ Rejection reason is required!");
        }
    }
</script>

<?php require_once 'admin_footer.php'; ?>