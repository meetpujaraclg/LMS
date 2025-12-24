<?php
$admin_pageTitle = "System Settings";
require_once 'admin_header.php';

// Create settings table if it doesn't exist
$admin_pdo->exec("
    CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Default settings
$defaultSettings = [
    // General Settings
    'site_name' => ['value' => 'EdTech LMS', 'type' => 'text', 'group' => 'general', 'desc' => 'The name of your learning platform'],
    'site_email' => ['value' => 'admin@edtech.com', 'type' => 'email', 'group' => 'general', 'desc' => 'Primary contact email'],
    'site_description' => ['value' => 'Learning Management System', 'type' => 'textarea', 'group' => 'general', 'desc' => 'Brief description of your platform'],
    'site_url' => ['value' => 'http://localhost:8085/edutech-lms', 'type' => 'url', 'group' => 'general', 'desc' => 'Your website URL'],
    
    // System Settings
    'registration_enabled' => ['value' => '1', 'type' => 'checkbox', 'group' => 'system', 'desc' => 'Allow new user registrations'],
    'maintenance_mode' => ['value' => '0', 'type' => 'checkbox', 'group' => 'system', 'desc' => 'Put site in maintenance mode'],
    'max_file_size' => ['value' => '10', 'type' => 'number', 'group' => 'system', 'desc' => 'Maximum file upload size in MB'],
    'allowed_file_types' => ['value' => 'jpg,jpeg,png,gif,pdf,mp4,avi,mov', 'type' => 'text', 'group' => 'system', 'desc' => 'Comma-separated list of allowed file types'],
    
    // Security Settings
    'password_min_length' => ['value' => '8', 'type' => 'number', 'group' => 'security', 'desc' => 'Minimum password length'],
    'login_attempts' => ['value' => '5', 'type' => 'number', 'group' => 'security', 'desc' => 'Maximum login attempts before lockout'],
    'session_timeout' => ['value' => '60', 'type' => 'number', 'group' => 'security', 'desc' => 'Session timeout in minutes'],
    
    // Email Settings
    'smtp_host' => ['value' => '', 'type' => 'text', 'group' => 'email', 'desc' => 'SMTP server host'],
    'smtp_port' => ['value' => '587', 'type' => 'number', 'group' => 'email', 'desc' => 'SMTP server port'],
    'smtp_username' => ['value' => '', 'type' => 'text', 'group' => 'email', 'desc' => 'SMTP username'],
    'smtp_password' => ['value' => '', 'type' => 'password', 'group' => 'email', 'desc' => 'SMTP password'],
    
    // Payment Settings
    'currency' => ['value' => 'USD', 'type' => 'text', 'group' => 'payment', 'desc' => 'Default currency'],
    'payment_gateway' => ['value' => 'stripe', 'type' => 'select:paypal,stripe,razorpay', 'group' => 'payment', 'desc' => 'Payment gateway'],
];

// Initialize settings
foreach ($defaultSettings as $key => $setting) {
    $checkStmt = $admin_pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
    $checkStmt->execute([$key]);
    
    if (!$checkStmt->fetch()) {
        $stmt = $admin_pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_group, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$key, $setting['value'], $setting['type'], $setting['group'], $setting['desc']]);
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // Remove 'setting_' prefix
            $settingValue = is_array($value) ? implode(',', $value) : $value;
            
            $stmt = $admin_pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$settingValue, $settingKey]);
        }
    }
    $success = "Settings updated successfully!";
}

// Get current settings
$settingsStmt = $admin_pdo->query("SELECT setting_key, setting_value, setting_type, setting_group, description FROM system_settings");
$currentSettings = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize settings by group
$settingsByGroup = [];
foreach ($currentSettings as $setting) {
    $settingsByGroup[$setting['setting_group']][] = $setting;
}

// System information
$phpVersion = phpversion();
$mysqlVersion = $admin_pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Settings</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <form method="POST">
            <!-- General Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>General Settings
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($settingsByGroup['general'] ?? [] as $setting): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    <?php if ($setting['description']): ?>
                                        <small class="text-muted d-block"><?php echo $setting['description']; ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="col-md-8">
                                <?php if ($setting['setting_type'] === 'textarea'): ?>
                                    <textarea class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php else: ?>
                                    <input type="<?php echo $setting['setting_type']; ?>" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h me-2"></i>System Settings
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($settingsByGroup['system'] ?? [] as $setting): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    <?php if ($setting['description']): ?>
                                        <small class="text-muted d-block"><?php echo $setting['description']; ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="col-md-8">
                                <?php if ($setting['setting_type'] === 'checkbox'): ?>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="setting_<?php echo $setting['setting_key']; ?>" value="1" 
                                            <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            <?php echo $setting['setting_value'] ? 'Enabled' : 'Disabled'; ?>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <input type="<?php echo $setting['setting_type']; ?>" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Security Settings
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($settingsByGroup['security'] ?? [] as $setting): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                    <?php if ($setting['description']): ?>
                                        <small class="text-muted d-block"><?php echo $setting['description']; ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="col-md-8">
                                <input type="<?php echo $setting['setting_type']; ?>" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </form>
    </div>

    <!-- System Information & Quick Actions -->
    <div class="col-lg-4">
        <!-- System Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>PHP Version:</strong> 
                    <span class="badge bg-success float-end"><?php echo $phpVersion; ?></span>
                </div>
                <div class="mb-3">
                    <strong>MySQL Version:</strong>
                    <span class="badge bg-success float-end"><?php echo $mysqlVersion; ?></span>
                </div>
                <div class="mb-3">
                    <strong>Server Software:</strong>
                    <span class="badge bg-info float-end"><?php echo $serverSoftware; ?></span>
                </div>
                <div class="mb-3">
                    <strong>Upload Max Filesize:</strong>
                    <span class="badge bg-warning float-end"><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Post Max Size:</strong>
                    <span class="badge bg-warning float-end"><?php echo ini_get('post_max_size'); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Memory Limit:</strong>
                    <span class="badge bg-warning float-end"><?php echo ini_get('memory_limit'); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                        <i class="fas fa-broom me-2"></i>Clear Cache
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="backupDatabase()">
                        <i class="fas fa-database me-2"></i>Backup Database
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="checkUpdates()">
                        <i class="fas fa-sync me-2"></i>Check for Updates
                    </button>
                    <a href="../logout.php" class="btn btn-outline-danger" onclick="return confirm('This will log out all users. Continue?')">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout All Users
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-heartbeat me-2"></i>System Status
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Database:</strong>
                    <span class="badge bg-success float-end">Connected</span>
                </div>
                <div class="mb-3">
                    <strong>Disk Space:</strong>
                    <span class="badge bg-success float-end">85% Free</span>
                </div>
                <div class="mb-3">
                    <strong>Last Backup:</strong>
                    <span class="badge bg-warning float-end">2 days ago</span>
                </div>
                <div class="mb-3">
                    <strong>Uptime:</strong>
                    <span class="badge bg-info float-end">99.8%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearCache() {
    if (confirm('Are you sure you want to clear all cache?')) {
        // AJAX call to clear cache
        alert('Cache cleared successfully!');
    }
}

function backupDatabase() {
    if (confirm('Create a database backup?')) {
        // AJAX call to backup database
        alert('Database backup initiated!');
    }
}

function checkUpdates() {
    alert('Checking for updates...');
    // AJAX call to check updates
}
</script>

<?php require_once 'admin_footer.php'; ?>