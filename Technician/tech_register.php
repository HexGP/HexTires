<?php
// tech_register.php

// Start session to store technician ID after registration
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for technician registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Securely hash the password

    // Check if the email already exists
    $check_email_sql = "SELECT * FROM Technicians WHERE email = '$email'";
    $result = $conn->query($check_email_sql);
    
    if ($result->num_rows > 0) {
        echo "This email is already registered!";
    } else {
        // Insert the new technician into the Technicians table with default clearance_id = 1
        $sql = "INSERT INTO Technicians (first_name, last_name, email, phone_number, password)
                VALUES ('$first_name', '$last_name', '$email', '$phone_number', '$password')";

        if ($conn->query($sql) === TRUE) {
            // After successful registration, log the technician in and redirect to dashboard

            // Get the technician ID of the newly registered technician
            $technician_id = $conn->insert_id;

            // Store the technician ID and name in session for use in the dashboard
            $_SESSION['tech_id'] = $technician_id;
            $_SESSION['tech_name'] = $first_name . ' ' . $last_name;

            // Redirect to tech_dashboard.php after successful registration
            header("Location: tech_dashboard.php");
            exit();  // Ensure that no further code is executed after redirection
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Technician Registration</title>
    <link rel="stylesheet" type="text/css" href="../login_styles.css">
</head>

<body class="login-page">
    <div class="form-container">
        <h2>Technician Registration</h2>
        <p>
            Welcome Panther Tires
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
            <input type="button" value="Login" onclick="window.location.href='tech_login.php'">
        </form>
    </div>
</body>

</html>