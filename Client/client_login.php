<?php
// Start the session
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
            echo "Invalid password.";
        }
    } else {
        echo "No user found with this email.";
    }
}

// Close the connection
$conn->close();
?>

<!-- Sign-In Form -->
<h2>Client Login</h2>
<form method="POST" action="">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" value="Sign In">
</form>

<p>Don't have an account? <a href="client_register.php">Register here</a></p>
<p>Are you a <a href="../Technician/tech_login.php">Technician</a>?</p>
