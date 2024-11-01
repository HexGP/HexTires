<?php
session_start();

// Check if the technician is logged in, if not redirect to the login page
if (!isset($_SESSION['tech_id'])) {
    header("Location: tech_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tech_id = $_SESSION['tech_id'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Update the technician's profile
    $update_sql = "UPDATE Technicians SET email = '$email', phone_number = '$phone_number' WHERE technician_id = $tech_id";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "Profile updated successfully!";
        // Update the session variables with new values
        $_SESSION['tech_email'] = $email;
        $_SESSION['tech_phone'] = $phone_number;
        header("Location: tech_dashboard.php");
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

$conn->close();
?>
