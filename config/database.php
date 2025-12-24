<?php
// config/database.php
$host = 'localhost';
$dbname = 'edtech_lms'; // Change to your actual database name
$username = 'root';      // Change to your database username
$password = '';          // Change to your database password

try {
    $admin_pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $admin_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $admin_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>