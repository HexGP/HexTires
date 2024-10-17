<?php
session_start();

// Check if the client is logged in, if not redirect to the login page
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the appointment ID from the URL
$appointment_id = $_GET['appointment_id'];

// Fetch appointment details
$appointment_sql = "SELECT * FROM Appointments WHERE appointment_id = $appointment_id";
$appointment_result = $conn->query($appointment_sql);
$appointment = $appointment_result->fetch_assoc();

// When the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_date = $_POST['appointment_date'];
    $new_time = $_POST['appointment_time'];

    // Update the appointment date and time
    $update_sql = "UPDATE Appointments SET appointment_date = '$new_date', appointment_time = '$new_time'
                   WHERE appointment_id = $appointment_id";

    if ($conn->query($update_sql) === TRUE) {
        echo "Appointment updated successfully!";
        header("Location: client_dashboard.php");
        exit();
    } else {
        echo "Error updating appointment: " . $conn->error;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Appointment</title>
</head>
<body>

<h1>Edit Appointment</h1>

<form method="POST" action="">
    <label for="appointment_date">New Date:</label>
    <input type="date" name="appointment_date" value="<?php echo $appointment['appointment_date']; ?>" required><br><br>

    <label for="appointment_time">New Time:</label>
    <input type="time" name="appointment_time" value="<?php echo $appointment['appointment_time']; ?>" required><br><br>

    <input type="submit" value="Update Appointment">
</form>

<a href="client_dashboard.php">Go back to dashboard</a>

</body>
</html>
