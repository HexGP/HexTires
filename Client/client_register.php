<?php
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
        echo "Error: This email is already registered. Please use a different email.";
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
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Close the connection
$conn->close();
?>

<!-- Registration Form -->
<h2>Client Registration</h2>
<form method="POST" action="">
    First Name: <input type="text" name="first_name" required><br><br>
    Last Name: <input type="text" name="last_name" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Phone Number: <input type="text" name="phone_number" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" value="Register">
</form>

<p>Already have an account? <a href="client_login.php">Login here</a></p>