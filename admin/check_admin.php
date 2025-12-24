<?php
session_start();

/*
 * Your admin table is separate (no 'role' column),
 * so we just check if the session contains admin_id.
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

// Redirect to admin login if not logged in
if (!isAdminLoggedIn()) {
    header('Location: admin_login.php');
    exit;
}
?>