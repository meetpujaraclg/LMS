<?php
// Standalone admin authentication - no dependencies on user side
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Database configuration for admin
define('ADMIN_DB_HOST', 'localhost');
define('ADMIN_DB_USER', 'root');
define('ADMIN_DB_PASS', '');
define('ADMIN_DB_NAME', 'edtech_lms');

try {
    $admin_pdo = new PDO("mysql:host=" . ADMIN_DB_HOST . ";dbname=" . ADMIN_DB_NAME, ADMIN_DB_USER, ADMIN_DB_PASS);
    $admin_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Admin Database Connection failed: " . $e->getMessage());
}

// Helper functions
function admin_isLoggedIn()
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function admin_redirect($url)
{
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit;
    }
}

function admin_sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Admin login function
function admin_login($email, $password)
{
    global $admin_pdo;

    // ✅ Fetch from the new 'admins' table
    $stmt = $admin_pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_first_name'] = $admin['first_name'];
        $_SESSION['admin_last_name'] = $admin['last_name'];
        $_SESSION['admin_profile_picture'] = $admin['profile_picture'];
        return true;
    }

    return false;
}

// ✅ Redirect to login page only if not logged in
if (!admin_isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'admin_login.php') {
    admin_redirect('admin_login.php');
    exit;
}
?>