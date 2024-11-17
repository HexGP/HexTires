<?php
// admin_register.php

session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security

    // Check if email already exists
    $check_sql = "SELECT * FROM Admins WHERE email = '$email'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo "Email already exists.";
    } else {
        // Insert new admin into the database
        $sql = "INSERT INTO Admins (first_name, last_name, email, phone_number, password) 
                VALUES ('$first_name', '$last_name', '$email', '$phone_number', '$password')";

        if ($conn->query($sql) === TRUE) {
            echo "Admin registered successfully!";
            header("Location: admin_login.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Registration</title>
    <link rel="stylesheet" type="text/css" href="../login_styles.css">
</head>

<body class="login-page">
    <div class="form-container">
        <h2>Register as Admin</h2>
        <p>
            Welcome create your Admin account.
        </p>

        <?php if (!empty($error_message)) : ?>
        <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="column-form">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone_number" placeholder="Phone Number" required>
            <input type="password" name="password" placeholder="Password" required>

            <input type="submit" value="Register">
            <input type="button" value="Login" onclick="window.location.href='admin_login.php'">
        </form>
    </div>
</body>

</html>
