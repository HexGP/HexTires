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

// Get the logged-in client's ID
$client_id = $_SESSION['client_id'];

// Fetch client information
$client_sql = "SELECT first_name, last_name, email, phone_number FROM Clients WHERE client_id = $client_id";
$client_result = $conn->query($client_sql);
$client_info = $client_result->fetch_assoc();

// Fetch scheduled appointments (excluding canceled ones)
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, t.first_name AS tech_first_name, t.last_name AS tech_last_name, a.appointment_status 
                     FROM Appointments a 
                     JOIN Services s ON a.service_id = s.service_id
                     LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                     WHERE a.client_id = $client_id AND a.appointment_status != 'cancelled'
                     ORDER BY a.appointment_date, a.appointment_time";
$appointments_result = $conn->query($appointments_sql);

// Fetch service history (completed appointments)
$history_sql = "SELECT a.appointment_date, a.appointment_time, s.service_name, t.first_name AS tech_first_name, t.last_name AS tech_last_name
                FROM Appointments a
                JOIN Services s ON a.service_id = s.service_id
                LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                WHERE a.client_id = $client_id AND a.appointment_status = 'completed'
                ORDER BY a.appointment_date DESC";
$history_result = $conn->query($history_sql);

// Fetch upcoming reminders (appointments within the next 3 days)
$reminder_sql = "SELECT a.appointment_date, a.appointment_time, s.service_name
                 FROM Appointments a
                 JOIN Services s ON a.service_id = s.service_id
                 WHERE a.client_id = $client_id AND a.appointment_status = 'scheduled'
                 AND a.appointment_date >= CURDATE() AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
$reminder_result = $conn->query($reminder_sql);

// Cancel an appointment
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    // Update the appointment status to 'cancelled'
    $cancel_sql = "UPDATE Appointments SET appointment_status = 'cancelled' WHERE appointment_id = $appointment_id";
    
    if ($conn->query($cancel_sql) === TRUE) {
        echo "Appointment cancelled successfully.";
        header("Location: client_dashboard.php");
        exit();  // Ensure the page reloads after cancellation
    } else {
        echo "Error cancelling appointment: " . $conn->error;
    }
}
// Revise a cancelled appointment (only within an hour of cancellation)
if (isset($_POST['revise_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    // Update the appointment status back to 'requested'
    $revise_sql = "UPDATE Appointments SET appointment_status = 'requested' WHERE appointment_id = $appointment_id";
    
    if ($conn->query($revise_sql) === TRUE) {
        echo "Appointment revised successfully.";
        header("Location: client_dashboard.php");
        exit();  // Ensure the page reloads after the revision
    } else {
        echo "Error revising appointment: " . $conn->error;
    }
}

// Fetch cancelled appointments
$cancelled_appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, t.first_name AS tech_first_name, t.last_name AS tech_last_name, TIMESTAMPDIFF(MINUTE, CONCAT(a.appointment_date, ' ', a.appointment_time), NOW()) AS minutes_since_cancellation
                               FROM Appointments a
                               JOIN Services s ON a.service_id = s.service_id
                               LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                               WHERE a.client_id = $client_id AND a.appointment_status = 'cancelled'
                               ORDER BY a.appointment_date, a.appointment_time";
$cancelled_appointments_result = $conn->query($cancelled_appointments_sql);


// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Client Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>

    <h1>Welcome, <?php echo $client_info['first_name'] . ' ' . $client_info['last_name']; ?></h1>

    <!-- Profile Information -->
    <h2>Profile Information</h2>
    <p><strong>Email:</strong> <?php echo $client_info['email']; ?></p>
    <p><strong>Phone Number:</strong> <?php echo $client_info['phone_number']; ?></p>

    <!-- Logout Button -->
    <form action="client_logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>

    <!-- Upcoming Appointments -->
    <h2>My Appointments</h2>
    <!-- Display filtered appointments -->
    <?php if ($appointments_result->num_rows > 0): ?>
    <table border="1">
        <tr>
            <th>Service</th>
            <th>Technician</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $appointments_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['service_name']; ?></td>
            <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
            <td><?php echo $row['appointment_date']; ?></td>
            <td><?php echo $row['appointment_time']; ?></td>
            <td><?php echo ucfirst($row['appointment_status']); ?></td>
            <td>
                <!-- If status is 'requested' or 'scheduled', allow cancel/edit actions -->
                <?php if ($row['appointment_status'] == 'requested' || $row['appointment_status'] == 'scheduled'): ?>
                <!-- Cancel Appointment -->
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="cancelled">
                    <input type="submit" name="cancel_appointment" value="Cancel">
                </form>

                <!-- Edit Appointment -->
                <form method="GET" action="edit_appointment.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="submit" value="Edit">
                </form>

                <?php elseif ($row['appointment_status'] == 'in progress'): ?>
                <p>No changes allowed during service.</p>

                <?php elseif ($row['appointment_status'] == 'tech approved'): ?>

                <!-- Rating Form -->
                <form method="POST" action="client_rate_appointment.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <label for="rating">Rate (1-5):</label>
                    <input type="number" name="rating" min="1" max="5" required>
                    <input type="submit" name="rate_appointment" value="Submit Rating">
                </form>

                <!-- Mark Done -->
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="client approved">
                    <input type="submit" name="mark_done" value="Mark Done">
                </form>
                <?php endif; ?>

            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No appointments found.</p>
    <?php endif; ?>


    <!-- Cancelled Appointments Section -->
    <h2>Cancelled Appointments</h2>
    <?php
if ($cancelled_appointments_result->num_rows > 0): ?>
    <table border="1">
        <tr>
            <th>Service</th>
            <th>Technician</th>
            <th>Date</th>
            <th>Time</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $cancelled_appointments_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['service_name']; ?></td>
            <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
            <td><?php echo $row['appointment_date']; ?></td>
            <td><?php echo $row['appointment_time']; ?></td>
            <td>
                <?php if ($row['minutes_since_cancellation'] <= 60): ?>
                <!-- Revise Appointment Button (only available if cancelled within the last hour) -->
                <form method="POST" action=""
                    onsubmit="return confirm('Are you sure you want to revise this appointment?');">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="submit" name="revise_appointment" value="Revise Appointment">
                </form>
                <?php else: ?>
                <p>Revision time expired</p>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No cancelled appointments.</p>
    <?php endif; ?>

    <!-- Button to request a new service -->
    <h2>Request a New Service</h2>
    <a href="request_service.php">Request New Service</a>

</body>

</html>