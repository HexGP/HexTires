<?php
// edit_service.php

session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the service ID from the query string
$service_id = $_GET['service_id'];

// Fetch the current service details
$service_sql = "SELECT * FROM Services WHERE service_id = $service_id";
$service_result = $conn->query($service_sql);
$service = $service_result->fetch_assoc();

// Handle form submission for updating the service
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = $_POST['service_name'];
    $service_description = $_POST['service_description'];
    $price = $_POST['service_price'];

    // Update the service details in the database
    $update_sql = "UPDATE Services SET service_name = '$service_name', service_description = '$service_description', service_price = $price WHERE service_id = $service_id";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "Service updated successfully!";
        header("Location: manage_services.php");
        exit();
    } else {
        echo "Error updating service: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Service</title>
</head>
<body>
    <h1>Edit Service</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="manage_services.php" style="display:inline;">
        <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
        <input type="submit" value="All Services" class="button">
    </form>

    <form action="" method="POST">
        <label for="service_name">Service Name:</label>
        <input type="text" name="service_name" value="<?php echo $service['service_name']; ?>" required><br>
        <label for="service_description">Service Description:</label>
        <textarea name="service_description" required><?php echo $service['service_description']; ?></textarea><br>
        <label for="service_price">Service Price:</label>
        <input type="number" name="service_price" step="1.00" value="<?php echo $service['service_price']; ?>" required><br>
        <input type="submit" value="Update Service">
    </form>
</body>
</html>
