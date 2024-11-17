<?php
session_start();

// Check if the technician is logged in
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $technician_id = (int)$_SESSION['tech_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['start_time'];

    // Define end time (15 minutes duration for appointments)
    $end_time = date("H:i:s", strtotime("+15 minutes", strtotime($appointment_time)));

    // Update the appointment status and assign the technician
    $assign_sql = "UPDATE Appointments 
                   SET technician_id = $technician_id, appointment_status = 'scheduled' 
                   WHERE appointment_id = $appointment_id";
    if ($conn->query($assign_sql) === TRUE) {
        // Call the TechnicianScheduler stored procedure
        $stmt = $conn->prepare("CALL TechnicianScheduler(?, ?, 'scheduled', ?, ?, ?)");
        $stmt->bind_param("iisss", $appointment_id, $technician_id, $appointment_date, $appointment_time, $end_time);

        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>alert('Appointment scheduled successfully!'); window.location.href = 'tech_dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error calling TechnicianScheduler procedure: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Error assigning technician: " . $conn->error . "');</script>";
    }
}

$conn->close();
?>

