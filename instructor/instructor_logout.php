<?php
// instructor_logout.php

error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Unset only instructor-specific session variables
unset($_SESSION['instructor_id']);
unset($_SESSION['instructor_email']);
unset($_SESSION['instructor_role']);
unset($_SESSION['instructor_name']);

// Optionally, you can add a success message or log here

// Redirect to instructor login page
header("Location: ../instructor_login.php");
exit();