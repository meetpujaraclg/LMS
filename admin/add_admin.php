<?php
// add_admin.php

// Database configuration
define('ADMIN_DB_HOST', 'localhost');
define('ADMIN_DB_USER', 'root');
define('ADMIN_DB_PASS', '');
define('ADMIN_DB_NAME', 'edtech_lms');

// Create database connection
$conn = mysqli_connect(ADMIN_DB_HOST, ADMIN_DB_USER, ADMIN_DB_PASS, ADMIN_DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['submit'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle profile picture upload
    $profile_picture = "";
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "uploads/admins/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $profile_picture = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $profile_picture;

        // Move uploaded file
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            echo "<script>alert('Error uploading file!');</script>";
            $profile_picture = "";
        }
    }

    // Insert into database
    $query = "INSERT INTO admins (first_name, last_name, email, password, profile_picture) 
              VALUES ('$first_name', '$last_name', '$email', '$hashed_password', '$profile_picture')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Admin added successfully!');</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Admin</title>
    <style>
        body {
            font-family: Arial;
            background: #f7f9fc;
            padding: 40px;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            max-width: 450px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="file"] {
            margin-top: 10px;
        }

        input[type="submit"] {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #0056b3;
        }

        h2 {
            text-align: center;
            color: #333;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Add New Admin</h2>
        <form method="post" enctype="multipart/form-data">
            <label>First Name:</label>
            <input type="text" name="first_name" required>

            <label>Last Name:</label>
            <input type="text" name="last_name" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Profile Picture:</label>
            <input type="file" name="profile_picture" accept="image/*">

            <input type="submit" name="submit" value="Add Admin">
        </form>
    </div>

</body>

</html>