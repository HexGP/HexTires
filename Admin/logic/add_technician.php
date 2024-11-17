<?php
session_start();

// Check if the admin is logged in, if not redirect to the login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission to add a new technician
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $clearance_id = $_POST['clearance_id'];
    $is_inhouse = isset($_POST['is_inhouse']) ? 1 : 0; // In-house or contractor

    // Insert into Technicians table
    $insert_sql = "INSERT INTO Technicians (first_name, last_name, email, phone_number, clearance_id, is_inhouse) 
                   VALUES ('$first_name', '$last_name', '$email', '$phone_number', '$clearance_id', '$is_inhouse')";

    if ($conn->query($insert_sql) === TRUE) {
        echo "<script>alert('Technician added successfully!'); window.location.href='manage_technicians.php';</script>";
    } else {
        echo "Error: " . $insert_sql . "<br>" . $conn->error;
    }
}

// Close the connection
$conn->close();
?>
