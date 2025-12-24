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
        $id = (int)$_POST['id'];
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
    $categoryId = (int)$_GET['id'];
    
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
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Categories</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Add/Edit Category Form -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php 
                    $editingCategory = null;
                    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
                        $stmt = $admin_pdo->prepare("SELECT * FROM categories WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        $editingCategory = $stmt->fetch();
                        echo 'Edit Category';
                    } else {
                        echo 'Add New Category';
                    }
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editingCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editingCategory['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo $editingCategory ? $editingCategory['name'] : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editingCategory ? $editingCategory['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color" id="color" name="color" 
                                       value="<?php echo $editingCategory ? $editingCategory['color'] : '#6c757d'; ?>" title="Choose color">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="icon" class="form-label">Icon</label>
                                <select class="form-select" id="icon" name="icon">
                                    <?php foreach ($availableIcons as $icon): ?>
                                        <option value="<?php echo $icon; ?>" 
                                            <?php echo ($editingCategory && $editingCategory['icon'] == $icon) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(str_replace('-', ' ', $icon)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="<?php echo $editingCategory ? 'update_category' : 'add_category'; ?>" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> 
                        <?php echo $editingCategory ? 'Update Category' : 'Add Category'; ?>
                    </button>
                    
                    <?php if ($editingCategory): ?>
                        <a href="categories.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Quick Color Palette -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">Quick Colors</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($availableColors as $color): ?>
                        <div class="color-swatch" style="background: <?php echo $color; ?>; width: 30px; height: 30px; border-radius: 4px; cursor: pointer;" 
                             onclick="document.getElementById('color').value = '<?php echo $color; ?>'"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <!-- <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Existing Categories</h5>
                <span class="badge bg-primary"><?php echo count($categories); ?> categories</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Courses</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                        No categories found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 40px; height: 40px; background: <?php echo $category['color']; ?>;">
                                                    <i class="fas fa-<?php echo $category['icon']; ?> text-white"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo $category['name']; ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $category['description'] ?: '<span class="text-muted">No description</span>'; ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo $categoryUsage[$category['name']] ?? 0; ?> courses
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> -->

        <!-- Course Categories from Existing Data -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Course Categories (Auto-detected)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($categoryUsage)): ?>
                    <p class="text-muted text-center">No course categories found in the database.</p>
                <?php else: ?>
                    <div class="row">
                        <?php 
                        $colorIndex = 0;
                        foreach ($categoryUsage as $category => $count): 
                            $color = $availableColors[$colorIndex % count($availableColors)];
                            $colorIndex++;
                        ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                             style="width: 60px; height: 60px; background: <?php echo $color; ?>;">
                                            <i class="fas fa-book text-white fa-lg"></i>
                                        </div>
                                        <h6><?php echo $category; ?></h6>
                                        <span class="badge bg-primary"><?php echo $count; ?> course<?php echo $count != 1 ? 's' : ''; ?></span>
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

<?php require_once 'admin_footer.php'; ?>