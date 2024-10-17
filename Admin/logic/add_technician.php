<?php
session_start();

// Check if the admin is logged in, if not redirect to the login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

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

<!DOCTYPE html>
<html>
<head>
    <title>Add Technician</title>
</head>
<body>
    <h1>Add Technician</h1>

    <!-- Back to Manage Technicians page -->
    <form method="GET" action="manage_technicians.php" style="display:inline;">
        <input type="submit" value="Back to Technicians">
    </form>

    <form action="add_technician.php" method="POST">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required><br>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required><br>

        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" required><br>

        <label for="clearance_id">Clearance Level:</label>
        <select name="clearance_id" required>
            <option value="1">Basic Service</option>
            <option value="2">Advanced Service</option>
            <option value="3">Specialist Service</option>
            <option value="4">Expert Service</option>
        </select><br>

        <label for="is_inhouse">Is In-House Technician?</label>
        <input type="checkbox" name="is_inhouse" value="1" checked><br>

        <input type="submit" value="Add Technician">
    </form>
</body>
</html>
