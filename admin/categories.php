<?php
$admin_pageTitle = "Manage Categories";
require_once 'admin_header.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = admin_sanitize($_POST['name']);
        $description = admin_sanitize($_POST['description']);
        $color = admin_sanitize($_POST['color']);
        $icon = admin_sanitize($_POST['icon']);

        // Check if category already exists
        $checkStmt = $admin_pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $checkStmt->execute([$name]);

        if ($checkStmt->fetch()) {
            $error = "Category '$name' already exists!";
        } else {
            $stmt = $admin_pdo->prepare("INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $color, $icon])) {
                $success = "Category added successfully!";
            } else {
                $error = "Failed to add category!";
            }
        }
    } elseif (isset($_POST['update_category'])) {
        $id = (int) $_POST['id'];
        $name = admin_sanitize($_POST['name']);
        $description = admin_sanitize($_POST['description']);
        $color = admin_sanitize($_POST['color']);
        $icon = admin_sanitize($_POST['icon']);

        // Check if category name already exists (excluding current category)
        $checkStmt = $admin_pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $checkStmt->execute([$name, $id]);

        if ($checkStmt->fetch()) {
            $error = "Category '$name' already exists!";
        } else {
            $stmt = $admin_pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ?, icon = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $color, $icon, $id])) {
                $success = "Category updated successfully!";
            } else {
                $error = "Failed to update category!";
            }
        }
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $categoryId = (int) $_GET['id'];

    if ($_GET['action'] == 'delete') {
        // Check if category is being used
        $checkStmt = $admin_pdo->prepare("SELECT COUNT(*) FROM courses WHERE category = (SELECT name FROM categories WHERE id = ?)");
        $checkStmt->execute([$categoryId]);
        $courseCount = $checkStmt->fetchColumn();

        if ($courseCount > 0) {
            $error = "Cannot delete category! It is being used by $courseCount course(s).";
        } else {
            $stmt = $admin_pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$categoryId])) {
                $success = "Category deleted successfully!";
            } else {
                $error = "Failed to delete category!";
            }
        }
    }
}

// Create categories table if it doesn't exist
$admin_pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#6c757d',
        icon VARCHAR(50) DEFAULT 'folder',
        course_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Get all categories
$categories = $admin_pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get category usage statistics
$categoryUsageStmt = $admin_pdo->query("
    SELECT category, COUNT(*) as course_count 
    FROM courses 
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category
");
$categoryUsage = $categoryUsageStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Available icons and colors
$availableIcons = ['book', 'code', 'paint-brush', 'chart-line', 'camera', 'music', 'briefcase', 'heartbeat', 'language', 'calculator', 'flask', 'globe', 'mobile', 'database', 'shopping-cart'];
$availableColors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#27ae60', '#8e44ad'];

$editingCategory = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $admin_pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $editingCategory = $stmt->fetch();
}
?>

<style>
    /* Blue Glassmorphism - Single Card Categories */
    :root {
        --primary-blue: #3b82f6;
        --primary-blue-dark: #1e40af;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.3);
        --glass-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
    }

    /* Single Glass Card */
    .categories-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        padding: 2.5rem;
        max-width: 1000px;
        margin: 0 auto;
        transition: all 0.3s ease;
    }

    .categories-card:hover {
        box-shadow: 0 30px 60px rgba(59, 130, 246, 0.25);
        transform: translateY(-5px);
    }

    /* Alerts */
    .alert {
        border: none;
        border-radius: 16px;
        backdrop-filter: blur(12px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    /* Form Section */
    .category-form {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2.5rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--primary-blue);
        margin-bottom: 0.75rem;
    }

    /* Category Item */
    .category-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 1.75rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .category-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(59, 130, 246, 0.15);
    }

    .category-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .category-info {
        flex: 1;
    }

    .category-name {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 0.25rem;
    }

    .category-description {
        color: #64748b;
        margin-bottom: 1rem;
    }

    .category-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .usage-badge {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    /* Color Palette */
    .color-palette {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2rem;
    }

    .color-swatch {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: all 0.2s ease;
    }

    .color-swatch:hover {
        transform: scale(1.1);
        border-color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Course Categories */
    .course-category-item {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        text-align: center;
    }

    .course-category-item:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    /* Buttons */
    .btn {
        border-radius: 12px;
        border: none;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(12px);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    }

    .btn-outline-primary {
        background: rgba(59, 130, 246, 0.2);
        color: var(--primary-blue);
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .btn-outline-primary:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-2px);
    }

    .btn-outline-danger {
        background: rgba(239, 68, 68, 0.2);
        color: white;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-outline-danger:hover {
        background: rgba(239, 68, 68, 0.4);
        color: white;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
    }

    .empty-icon {
        font-size: 5rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .categories-card {
            margin: 1rem;
            padding: 2rem;
        }

        .category-item {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="categories-card">

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="category-form">
                <form method="POST">
                    <?php if ($editingCategory): ?>
                        <input type="hidden" name="id" value="<?= $editingCategory['id'] ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= $editingCategory ? htmlspecialchars($editingCategory['name']) : '' ?>"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="icon" class="form-label">Icon</label>
                            <select class="form-select" id="icon" name="icon">
                                <?php foreach ($availableIcons as $icon): ?>
                                    <option value="<?= $icon ?>" <?= ($editingCategory && $editingCategory['icon'] == $icon) ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('-', ' ', $icon)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color w-100" id="color" name="color"
                                value="<?= $editingCategory ? $editingCategory['color'] : '#6c757d' ?>"
                                title="Choose color">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quick Colors</label>
                            <div class="color-palette">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($availableColors as $color): ?>
                                        <div class="color-swatch" style="background: <?= $color ?>;"
                                            onclick="document.getElementById('color').value = '<?= $color ?>';"></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="3"><?= $editingCategory ? htmlspecialchars($editingCategory['description']) : '' ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="<?= $editingCategory ? 'update_category' : 'add_category' ?>"
                            class="btn btn-primary flex-fill">
                            <i class="fas fa-save me-2"></i>
                            <?= $editingCategory ? 'Update Category' : 'Add Category' ?>
                        </button>
                        <?php if ($editingCategory): ?>
                            <a href="manage_categories.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Managed Categories -->
            <?php if (!empty($categories)): ?>
                <div class="mb-4">
                    <h3 style="color: var(--primary-blue);">📂 Managed Categories (<?= count($categories) ?>)</h3>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item">
                            <div class="category-icon" style="background: <?= $category['color'] ?>">
                                <i class="fas fa-<?= $category['icon'] ?>"></i>
                            </div>
                            <div class="category-info">
                                <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                                <div class="category-description">
                                    <?= htmlspecialchars($category['description'] ?: 'No description') ?>
                                </div>
                                <div class="category-stats">
                                    <span class="usage-badge">
                                        <?= $categoryUsage[$category['name']] ?? 0 ?> courses
                                    </span>
                                    <small style="color: #64748b;">
                                        Created: <?= date('M j, Y', strtotime($category['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="?action=edit&id=<?= $category['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?= $category['id'] ?>" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($category['name']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏷️</div>
                        <h4>No categories created yet</h4>
                        <p>Use the form above to add your first category.</p>
                    </div>
                <?php endif; ?>

                <!-- Course Categories from Existing Data -->
                <?php if (!empty($categoryUsage)): ?>
                    <div class="mt-5">
                        <h3 style="color: var(--primary-blue);">📊 Course Categories (Auto-detected)</h3>
                        <div class="row">
                            <?php
                            $colorIndex = 0;
                            foreach ($categoryUsage as $category => $count):
                                $color = $availableColors[$colorIndex % count($availableColors)];
                                $colorIndex++;
                                ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="course-category-item">
                                        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                                            style="width: 60px; height: 60px; background: <?= $color ?>;">
                                            <i class="fas fa-book text-white fa-lg"></i>
                                        </div>
                                        <h6 class="mb-2"><?= htmlspecialchars($category) ?></h6>
                                        <span class="badge bg-primary"><?= $count ?> course<?= $count != 1 ? 's' : '' ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>

<?php require_once 'admin_footer.php'; ?>