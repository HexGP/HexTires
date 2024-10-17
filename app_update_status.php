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

// Ensure appointment ID and new status are set
if (isset($_POST['appointment_id']) && isset($_POST['new_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];

    // Fetch current status and history
    $appointment_sql = "SELECT appointment_status, status_history FROM Appointments WHERE appointment_id = $appointment_id";
    $appointment_result = $conn->query($appointment_sql);
    $appointment = $appointment_result->fetch_assoc();

    $current_status = $appointment['appointment_status'];
    $status_history = $appointment['status_history'] ?? '';

    // Identify the user type who is updating the status
    if (isset($_SESSION['admin_id'])) {
        $user_role = 'Admin';
    } elseif (isset($_SESSION['tech_id'])) {
        $user_role = 'Technician';
    } elseif (isset($_SESSION['client_id'])) {
        $user_role = 'Client';
    } else {
        $user_role = 'Unknown';
    }

    // Append to status history
    $new_history_entry = $current_status . ' → ' . $new_status . ' by ' . $user_role . ' on ' . date('Y-m-d H:i:s') . '; ';
    $updated_history = $status_history . $new_history_entry;

    // Update the appointment status and status history
    $update_sql = "UPDATE Appointments SET appointment_status = '$new_status', status_history = '$updated_history' WHERE appointment_id = $appointment_id";
    
    if ($conn->query($update_sql) === TRUE) {
        // Redirect to the appropriate dashboard
        if (isset($_SESSION['tech_id'])) {
            header("Location: ../HexTires/Technician/tech_dashboard.php");
        } elseif (isset($_SESSION['admin_id'])) {
            header("Location: ../HexTires/Admin/admin_dashboard.php");
        } elseif (isset($_SESSION['client_id'])) {
            header("Location: ../HexTires/Client/client_dashboard.php");
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
