<?php
// config/database.php
$host = 'localhost';
$dbname = 'edutech_lms'; // Your database name
$username = 'root';      // Your database username
$password = '';          // Your database password

try {
    $admin_pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $admin_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $admin_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>