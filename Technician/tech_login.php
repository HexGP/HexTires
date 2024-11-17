<?php
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to fetch technician details based on email
    $tech_sql = "SELECT technician_id, first_name, last_name, email, phone_number, clearance_id, password FROM Technicians WHERE email = '$email'";
    $tech_result = $conn->query($tech_sql);

    if ($tech_result->num_rows == 1) {
        $tech_data = $tech_result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $tech_data['password'])) {
            // Store technician details in session
            $_SESSION['tech_id'] = $tech_data['technician_id'];
            $_SESSION['tech_name'] = $tech_data['first_name'] . ' ' . $tech_data['last_name'];
            $_SESSION['tech_email'] = $tech_data['email'];
            $_SESSION['tech_phone'] = $tech_data['phone_number'];
            $_SESSION['clearance_id'] = $tech_data['clearance_id'];

            // Redirect to tech_dashboard.php after successful login
            header("Location: tech_dashboard.php");
            exit();
        } else {
            // Invalid password
            echo "Invalid email or password!";
        }
    } else {
        // Invalid email
        echo "Invalid email or password!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Technician Login</title>
    <link rel="stylesheet" type="text/css" href="../login_styles.css">
</head>

<body class="login-page">
    <div class="form-container">
        <div class="back-button-container">
            <button onclick="window.location.href='../index.php'" class="back-button">&laquo; Go Back</button>
        </div>

        <h2>Technician Login</h2>
        <p>
            Please log into your Technician account.
        </p>
        <?php if (!empty($error_message)) : ?>
        <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>

            <input type="submit" value="Login">
            <input type="button" value="Sign Up" onclick="window.location.href='tech_register.php'">
        </form>
    </div>
</body>

</html>