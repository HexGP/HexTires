<?php
// admin_login.php

session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check if the email exists
    $sql = "SELECT * FROM Admins WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];

            // Redirect to admin dashboard
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "Admin not found.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Login</title>
    <link rel="stylesheet" type="text/css" href="../login_styles.css">
</head>
    <body class="login-page">
        <div class="form-container">
            <div class="back-button-container">
                <button onclick="window.location.href='../index.php'" class="back-button">&laquo; Go Back</button>
            </div>

            <h2>Admin Login</h2>
            <p>
            Welcome back Admin!
            </p>
            <?php if (!empty($error_message)) : ?>
            <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>

                <input type="submit" value="Login">
                <input type="button" value="Sign Up" onclick="window.location.href='admin_register.php'">
            </form>
        </div>
    </body>
</html>