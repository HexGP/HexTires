<?php
// add_service.php

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

// Handle form submission for adding a service
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = $_POST['service_name'];
    $service_description = $_POST['service_description'];
    $service_price = $_POST['service_price'];  // Correct field name
    $clearance_id = $_POST['clearance_id'];  // Add clearance_id from form
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;  // Set is_exclusive (optional)

    // Validate and ensure service_price is numeric and clearance_id is valid
    if (!empty($service_price) && is_numeric($service_price) && $clearance_id >= 1 && $clearance_id <= 4) {
        // Insert the new service into the database
        $add_service_sql = "INSERT INTO Services (service_name, service_description, service_price, clearance_id, is_exclusive) 
                            VALUES ('$service_name', '$service_description', $service_price, $clearance_id, $is_exclusive)";
        
        if ($conn->query($add_service_sql) === TRUE) {
            echo "Service added successfully!";
            header("Location: manage_services.php");
            exit();
        } else {
            echo "Error adding service: " . $conn->error;
        }
    } else {
        echo "Error: Service price must be a valid number and Clearance Level must be valid.";
    }
}

// Close the connection
$conn->close();
?>


test