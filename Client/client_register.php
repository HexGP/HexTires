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
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Check if the email already exists
    $check_email_sql = "SELECT * FROM Clients WHERE email = '$email'";
    $check_email_result = $conn->query($check_email_sql);

    if ($check_email_result->num_rows > 0) {
        // If the email exists, show an error message
        $error_message = "This email is already registered. Please use a different email.";
    } else {
        // Insert the new user into the Clients table
        $sql = "INSERT INTO Clients (first_name, last_name, phone_number, email, password) 
                VALUES ('$first_name', '$last_name', '$phone_number', '$email', '$password')";

        if ($conn->query($sql) === TRUE) {
            // After successful registration, set session variables and redirect to client_dashboard.php
            $_SESSION['client_id'] = $conn->insert_id; // Get the newly created client ID
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            header("Location: client_dashboard.php");
            exit();
        } else {
            $error_message = "Error registering. Please try again.";
        }
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Client Registration</title>
    <link rel="stylesheet" type="text/css" href="client_styles.css">
</head>

<body>

    <div class="form-container">
        <h2>Register</h2>
        <p>
            Join our community today! Create an account to unlock exclusive features and personalized experiences.
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
            <input type="button" value="Login" onclick="window.location.href='client_login.php'">
        </form>

        <!-- <p>Already have an account? <a href="client_login.php">Login here</a></p> -->
    </div>

</body>

</html>