<?php
$admin_pageTitle = "View Instructor";
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

<style>
    /* Blue Glassmorphism - Single Card Instructor Profile */
    :root {
        --primary-blue: #3b82f6;
        --primary-blue-dark: #1e40af;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.3);
        --glass-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
    }

    /* Single Glass Card */
    .instructor-profile-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        padding: 3rem;
        max-width: 900px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }

    .instructor-profile-card:hover {
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.25);
        transform: translateY(-5px);
    }

    /* Back Button */
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
        margin: 0 auto 2rem;
        display: inline-block;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        color: white;
    }

    /* Profile Header */
    .profile-header {
        text-align: center;
        margin-bottom: 2.5rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .instructor-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid rgba(59, 130, 246, 0.3);
        box-shadow: 0 15px 35px rgba(59, 130, 246, 0.25);
        margin-bottom: 1.5rem;
    }

    .instructor-name {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .instructor-email {
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }

    .joined-date {
        color: #94a3b8;
        font-size: 0.95rem;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(59, 130, 246, 0.2);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-blue);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }

    .status-badge {
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .badge-inactive {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .badge-pending {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #1f2937;
    }

    /* Detail Sections */
    .detail-section {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .section-title {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .detail-item {
        background: rgba(255, 255, 255, 0.08);
        padding: 1.25rem;
        border-radius: 16px;
        border-left: 4px solid var(--primary-blue);
    }

    .detail-label {
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .detail-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 1rem;
    }

    /* Document Buttons */
    .doc-btn {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-blue);
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid rgba(59, 130, 246, 0.2);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .doc-btn:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    }

    /* Bio Section */
    .bio-content {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.5rem;
        border-left: 4px solid var(--primary-blue);
        line-height: 1.7;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .instructor-profile-card {
            margin: 1rem;
            padding: 2rem;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="instructor-profile-card">

        <a href="instructors.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Instructors
        </a>

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="<?= !empty($instructor['profile_picture']) && file_exists("../uploads/instructors/" . $instructor['profile_picture'])
                ? "../uploads/instructors/" . htmlspecialchars($instructor['profile_picture'])
                : '../uploads/instructors/default-avatar.png' ?>" class="instructor-avatar"
                onerror="this.src='../uploads/instructors/default-avatar.png'"
                alt="<?= htmlspecialchars($instructor['first_name']) ?>">

            <div class="instructor-name">
                <?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?>
            </div>
            <div class="instructor-email">
                <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($instructor['email']) ?>
            </div>
            <div class="joined-date">
                <i class="fas fa-calendar me-1"></i> Joined:
                <?= date('M j, Y', strtotime($instructor['created_at'])) ?>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $instructor['course_count'] ?></div>
                <div class="stat-label">Courses Created</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $instructor['total_enrollments'] ?></div>
                <div class="stat-label">Total Enrollments</div>
            </div>
            <div class="stat-card">
                <div class="status-badge <?= $instructor['profile_status'] == 'active' ? 'badge-active' :
                    ($instructor['profile_status'] == 'inactive' ? 'badge-inactive' : 'badge-pending') ?>">
                    <?= ucfirst($instructor['profile_status'] ?? 'pending') ?>
                </div>
                <div class="stat-label">Profile Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-number <?= $instructor['verified'] ? 'text-success' : 'text-danger' ?>">
                    <?= $instructor['verified'] ? '✅ Yes' : '❌ No' ?>
                </div>
                <div class="stat-label">Verified</div>
            </div>
        </div>

        <!-- Basic Info -->
        <div class="detail-section">
            <h3 class="section-title">
                <i class="fas fa-info-circle"></i> Basic Information
            </h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Experience</div>
                    <div class="detail-value">
                        <?= !empty($instructor['experience']) ? htmlspecialchars($instructor['experience']) : 'N/A' ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expertise Area</div>
                    <div class="detail-value">
                        <?= !empty($instructor['expertise_area']) ? htmlspecialchars($instructor['expertise_area']) : 'N/A' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confidential Documents -->
        <div class="detail-section">
            <h3 class="section-title">
                <i class="fas fa-file-shield-alt"></i> Confidential Documents
            </h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">ID Proof</div>
                    <div class="detail-value">
                        <?php if (!empty($instructor['id_proof']) && file_exists("../uploads/instructors/" . $instructor['id_proof'])): ?>
                            <a href="../uploads/instructors/<?= urlencode($instructor['id_proof']) ?>" target="_blank"
                                class="doc-btn">
                                <i class="fas fa-file-pdf"></i> View ID Proof
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not Uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Qualification Proof</div>
                    <div class="detail-value">
                        <?php if (!empty($instructor['qualification']) && file_exists("../uploads/instructors/" . $instructor['qualification'])): ?>
                            <a href="../uploads/instructors/<?= urlencode($instructor['qualification']) ?>" target="_blank"
                                class="doc-btn">
                                <i class="fas fa-certificate"></i> View Qualification
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not Uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biography -->
        <?php if (!empty($instructor['bio'])): ?>
            <div class="detail-section">
                <h3 class="section-title">
                    <i class="fas fa-user-pen"></i> Biography
                </h3>
                <div class="bio-content">
                    <?= nl2br(htmlspecialchars($instructor['bio'])) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>