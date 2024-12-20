<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch appointment details
$appointment_id = $_GET['appointment_id'];
$appointment_sql = "
    SELECT a.*, c.first_name AS client_first_name, c.last_name AS client_last_name, 
           t.first_name AS tech_first_name, t.last_name AS tech_last_name, 
           t.technician_id, s.service_name
    FROM Appointments a
    JOIN Clients c ON a.client_id = c.client_id
    LEFT JOIN Technicians t ON a.technician_id = t.technician_id
    JOIN Services s ON a.service_id = s.service_id
    WHERE a.appointment_id = $appointment_id";
$appointment_result = $conn->query($appointment_sql);
$appointment = $appointment_result->fetch_assoc();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['new_status'];

    // Fetch current status and details
    $current_status = $appointment['appointment_status'];
    $technician_id = $appointment['technician_id'];
    $client_id = $appointment['client_id'];
    $appointment_date = $appointment['appointment_date'];
    $start_time = $appointment['appointment_time'];
    $end_time = date("H:i:s", strtotime("+1 hour", strtotime($start_time))); // Assuming 1-hour duration

    // Append to status history
    $status_history = $appointment['status_history'] ?? '';
    $new_history_entry = $current_status . ' → ' . $new_status . ' by Admin on ' . date('Y-m-d H:i:s') . "\n";
    $updated_history = $status_history . $new_history_entry;

    // Update the appointment status and status history
    $update_sql = "UPDATE Appointments SET appointment_status = '$new_status', status_history = '$updated_history' WHERE appointment_id = $appointment_id";
    if ($conn->query($update_sql) === TRUE) {
        // Call TechnicianScheduler procedure for specific statuses
        if (in_array($new_status, ['assigned', 'scheduled', 'completed'])) {
            $stmt = $conn->prepare("CALL TechnicianScheduler(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iissss",
                $appointment_id,
                $technician_id,
                $new_status,
                $appointment_date,
                $start_time,
                $end_time
            );

            if (!$stmt->execute()) {
                echo "Error calling TechnicianScheduler procedure: " . $stmt->error;
            }
            $stmt->close();
        }

        // Call HandlePayment when the status is changed to 'completed'
        if ($new_status === 'completed') {
            $tip_amount = isset($_POST['tip_amount']) ? (float)$_POST['tip_amount'] : 0.00;

            $stmt = $conn->prepare("CALL HandlePayment(?, ?, ?, ?)");
            $stmt->bind_param(
                "iiid",
                $appointment_id,
                $client_id,
                $technician_id,
                $tip_amount
            );

            if (!$stmt->execute()) {
                echo "Error calling HandlePayment procedure: " . $stmt->error;
            }
            $stmt->close();
        }

        echo "Appointment status updated successfully.";
        header("Location: manage_appointments.php");
        exit();
    } else {
        echo "Error updating appointment status: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Appointment</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
    <script>
    function confirmStatusChange() {
        return confirm('Are you sure you want to change the appointment status?');
    }
    </script>
</head>

<body>
    <h1>Edit Appointment #<?php echo $appointment_id; ?></h1>

    <h2>Appointment Details</h2>
    <p><strong>Service:</strong> <?php echo $appointment['service_name']; ?></p>
    <p><strong>Client:</strong>
        <?php echo $appointment['client_first_name'] . ' ' . $appointment['client_last_name']; ?></p>
    <p><strong>Technician:</strong>
        <?php echo $appointment['tech_first_name'] . ' ' . $appointment['tech_last_name']; ?></p>
    <p><strong>Date:</strong> <?php echo $appointment['appointment_date']; ?></p>
    <p><strong>Time:</strong> <?php echo $appointment['appointment_time']; ?></p>
    <p><strong>Status:</strong> <?php echo ucfirst($appointment['appointment_status']); ?></p>

    <h2>Change Status</h2>
    <form method="POST" action="" onsubmit="return confirmStatusChange();">
        <label for="new_status">New Status:</label>
        <select name="new_status" required>
            <option value="requested"
                <?php echo ($appointment['appointment_status'] === 'requested') ? 'selected' : ''; ?>>Requested</option>
            <option value="scheduled"
                <?php echo ($appointment['appointment_status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
            <option value="completed"
                <?php echo ($appointment['appointment_status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled"
                <?php echo ($appointment['appointment_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <input type="submit" value="Update Status">
    </form>

    <h2>Status History</h2>
    <p><?php echo nl2br($appointment['status_history']); ?></p>

    <a href="manage_appointments.php">Back to Appointments</a>
</body>

</html>