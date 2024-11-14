<?php
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = ""; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Get the user details from the Clients table
    $sql = "SELECT * FROM Clients WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify the password
        if (password_verify($password, $row['password'])) {
            // Store user data in session
            $_SESSION['client_id'] = $row['client_id'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];

            // Redirect to the client dashboard
            header("Location: client_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "No user found with this email.";
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Client Login</title>
    <link rel="stylesheet" type="text/css" href="client_styles.css">
</head>
    <body class="login-page">
        <div class="form-container">
            <div class="back-button-container">
                <button onclick="window.location.href='../index.php'" class="back-button">&laquo; Go Back</button>
            </div>

            <h2>Client Login</h2>
            <p>
            Welcome back! Please log in to access your account.
            </p>
            <?php if (!empty($error_message)) : ?>
            <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>

                <input type="submit" value="Login">
                <input type="button" value="Sign Up" onclick="window.location.href='client_register.php'">
            </form>
        </div>
    </body>
</html>