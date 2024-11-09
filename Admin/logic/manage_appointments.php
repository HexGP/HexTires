<?php
// manage_appointments.php

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

// Handle sorting logic
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'appointment_date'; // Default to 'appointment_date'
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC'; // Default to 'ASC'

// Toggle sorting order (ASC or DESC) for the current column
$next_sort_order = $sort_order === 'ASC' ? 'desc' : 'asc';

// Fetch all appointments with sorting
$appointments_sql = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
           c.first_name AS client_first_name, c.last_name AS client_last_name, 
           t.first_name AS tech_first_name, t.last_name AS tech_last_name, a.appointment_status
    FROM Appointments a
    JOIN Services s ON a.service_id = s.service_id
    JOIN Clients c ON a.client_id = c.client_id
    LEFT JOIN Technicians t ON a.technician_id = t.technician_id
    ORDER BY $sort_column $sort_order";
$appointments_result = $conn->query($appointments_sql);

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $cancel_sql = "UPDATE Appointments SET appointment_status = 'cancelled', technician_id = NULL WHERE appointment_id = $appointment_id";
    if ($conn->query($cancel_sql) === TRUE) {
        echo "Appointment cancelled successfully.";
        header("Location: manage_appointments.php");
        exit();
    } else {
        echo "Error cancelling appointment: " . $conn->error;
    }
}

// Handle technician assignment to an appointment
if (isset($_POST['assign_technician'])) {
    $appointment_id = $_POST['appointment_id'];
    $technician_id = $_POST['technician_id'];
    $assign_sql = "UPDATE Appointments SET technician_id = $technician_id, appointment_status = 'scheduled' WHERE appointment_id = $appointment_id";
    if ($conn->query($assign_sql) === TRUE) {
        echo "Technician assigned successfully.";
        header("Location: manage_appointments.php");
        exit();
    } else {
        echo "Error assigning technician: " . $conn->error;
    }
}

// Fetch technicians for the dropdown
$technician_sql = "SELECT technician_id, first_name, last_name FROM Technicians";
$technician_result = $conn->query($technician_sql);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
    <script>
    // Confirmation dialog for marking complete
    function confirmCompletion() {
        return confirm('Are you sure you want to mark this appointment as completed?');
    }
    </script>
</head>
<body>
    <h1>Manage Appointments</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <!-- Appointment List -->
    <?php if ($appointments_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th><a href="?sort=appointment_id&order=<?php echo $next_sort_order; ?>">ID</a></th>
                <th><a href="?sort=service_name&order=<?php echo $next_sort_order; ?>">Service</a></th>
                <th><a href="?sort=client_first_name&order=<?php echo $next_sort_order; ?>">Client</a></th>
                <th><a href="?sort=tech_first_name&order=<?php echo $next_sort_order; ?>">Technician</a></th>
                <th><a href="?sort=appointment_date&order=<?php echo $next_sort_order; ?>">Date</a></th>
                <th><a href="?sort=appointment_time&order=<?php echo $next_sort_order; ?>">Time</a></th>
                <th><a href="?sort=appointment_status&order=<?php echo $next_sort_order; ?>">Status</a></th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $appointments_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['appointment_id']; ?></td>
                    <td><?php echo $row['service_name']; ?></td>
                    <td><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></td>
                    <td>
                        <?php if ($row['tech_first_name'] && $row['tech_last_name']): ?>
                            <?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?>
                        <?php else: ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                <select name="technician_id" required>
                                    <option value="">Assign Technician</option>
                                    <?php while ($tech = $technician_result->fetch_assoc()): ?>
                                        <option value="<?php echo $tech['technician_id']; ?>">
                                            <?php echo $tech['first_name'] . ' ' . $tech['last_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="submit" name="assign_technician" value="Assign">
                            </form>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['appointment_date']; ?></td>
                    <td><?php echo $row['appointment_time']; ?></td>
                    <td><?php echo ucfirst($row['appointment_status']); ?></td>
                    <td>
                        <!-- "Mark Complete" button for client or tech approved statuses -->
                        <?php if ($row['appointment_status'] == 'client approved' || $row['appointment_status'] == 'tech approved'): ?>
                            <form method="POST" action="../app_update_status.php" onsubmit="return confirmCompletion();" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                <input type="hidden" name="new_status" value="completed">
                                <input type="submit" value="Mark Complete">
                            </form>
                        <?php endif; ?>

                        <!-- Edit appointment button -->
                        <form method="GET" action="edit_appointment.php" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                            <input type="submit" value="Edit">
                        </form>

                        <!-- Cancel Appointment -->
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                            <input type="submit" name="cancel_appointment" value="Cancel">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No appointments found.</p>
    <?php endif; ?>
</body>
</html>
