<?php
session_start();

// Check if the user (technician, admin, or client) is logged in
if (!isset($_SESSION['tech_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['client_id'])) {
    header("Location: login.php");  // Redirect to login page
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure appointment ID and status are set
if (isset($_POST['appointment_id']) && isset($_POST['new_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];

    // Initialize the SQL query for different status changes
    $update_sql = "";

    // Handle different statuses based on user roles and actions
    if ($new_status === 'cancelled') {
        // If canceling, set status to 'cancelled' and unassign technician
        $update_sql = "UPDATE Appointments SET appointment_status = 'cancelled', technician_id = NULL WHERE appointment_id = $appointment_id";
    } elseif ($new_status === 'scheduled') {
        // Accept appointment (requested → scheduled)
        if (isset($_SESSION['tech_id'])) {
            $tech_id = $_SESSION['tech_id'];
            $update_sql = "UPDATE Appointments SET appointment_status = 'scheduled', technician_id = $tech_id WHERE appointment_id = $appointment_id";
        }
    } elseif ($new_status === 'in progress') {
        // Start appointment (scheduled → in progress)
        if (isset($_SESSION['tech_id'])) {
            $update_sql = "UPDATE Appointments SET appointment_status = 'in progress' WHERE appointment_id = $appointment_id";
        }
    } elseif ($new_status === 'tech approved') {
        // Mark appointment as tech approved
        if (isset($_SESSION['tech_id'])) {
            $update_sql = "UPDATE Appointments SET appointment_status = 'tech approved' WHERE appointment_id = $appointment_id";
        }
    } elseif ($new_status === 'client approved') {
        // Mark appointment as client approved
        if (isset($_SESSION['client_id'])) {
            $update_sql = "UPDATE Appointments SET appointment_status = 'client approved' WHERE appointment_id = $appointment_id";
        }
    } elseif ($new_status === 'completed') {
        // Mark appointment as completed (both client and tech have approved)
        $update_sql = "UPDATE Appointments SET appointment_status = 'completed' WHERE appointment_id = $appointment_id";
    }

    // Execute the update query if not empty
    if (!empty($update_sql) && $conn->query($update_sql) === TRUE) {
        // Redirect to correct dashboard based on the user role
        if (isset($_SESSION['tech_id'])) {
            header("Location: ../PHP/Technician/tech_dashboard.php");
        } elseif (isset($_SESSION['admin_id'])) {
            header("Location: ../Admin/admin_dashboard.php");
        } elseif (isset($_SESSION['client_id'])) {
            header("Location: ../PHP/Client/client_dashboard.php");  // Fix this redirection
        }
        exit();
    } else {
        echo "Error updating appointment: " . $conn->error;
    }
} else {
    echo "Invalid request. Appointment ID or status missing.";
}

// Close the connection
$conn->close();
?>
