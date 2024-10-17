<?php
session_start();

// Check if the technician is logged in, if not redirect to the login page
if (!isset($_SESSION['tech_id'])) {
    header("Location: tech_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in technician's ID
$tech_id = $_SESSION['tech_id'];

// Fetch technician's details along with clearance level name
$tech_sql = "SELECT t.first_name, t.last_name, t.email, t.phone_number, c.clearance_name 
             FROM Technicians t 
             JOIN Clearances c ON t.clearance_id = c.clearance_id 
             WHERE t.technician_id = $tech_id";

$tech_result = $conn->query($tech_sql);
$tech_info = $tech_result->fetch_assoc();

// Fetch technician's upcoming appointments (excluding completed or canceled)
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                            c.first_name AS client_first_name, c.last_name AS client_last_name, 
                            a.appointment_status, t.is_inhouse
                     FROM Appointments a 
                     JOIN Services s ON a.service_id = s.service_id
                     JOIN Clients c ON a.client_id = c.client_id
                     JOIN Technicians t ON a.technician_id = t.technician_id
                     WHERE a.technician_id = $tech_id";


// Handle filter for different appointment statuses
// Modify the query based on the selected filter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    if ($filter == 'available') {
        // Show all available appointments that have status 'requested'
        $appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                                    c.first_name AS client_first_name, c.last_name AS client_last_name, a.appointment_status
                             FROM Appointments a 
                             JOIN Services s ON a.service_id = s.service_id
                             JOIN Clients c ON a.client_id = c.client_id
                             WHERE a.appointment_status = 'requested'";
    } elseif ($filter == 'accepted') {
        // Show accepted appointments (status 'scheduled' or 'in progress')
        $appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                                    c.first_name AS client_first_name, c.last_name AS client_last_name, a.appointment_status
                             FROM Appointments a 
                             JOIN Services s ON a.service_id = s.service_id
                             JOIN Clients c ON a.client_id = c.client_id
                             WHERE a.technician_id = $tech_id AND a.appointment_status IN ('scheduled', 'in progress')";
    } elseif ($filter == 'completed') {
        // Show completed appointments, including tech and client approved
        $appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                                    c.first_name AS client_first_name, c.last_name AS client_last_name, a.appointment_status
                             FROM Appointments a 
                             JOIN Services s ON a.service_id = s.service_id
                             JOIN Clients c ON a.client_id = c.client_id
                             WHERE a.technician_id = $tech_id AND a.appointment_status IN ('completed', 'tech approved', 'client approved')";
    } elseif ($filter == 'cancelled') {
        // Show cancelled appointments
        $appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                                    c.first_name AS client_first_name, c.last_name AS client_last_name, a.appointment_status
                             FROM Appointments a 
                             JOIN Services s ON a.service_id = s.service_id
                             JOIN Clients c ON a.client_id = c.client_id
                             WHERE a.technician_id = $tech_id AND a.appointment_status = 'cancelled'";
    }
} else {
    // Default: Show all appointments for this technician
    $appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                                c.first_name AS client_first_name, c.last_name AS client_last_name, a.appointment_status
                         FROM Appointments a 
                         JOIN Services s ON a.service_id = s.service_id
                         JOIN Clients c ON a.client_id = c.client_id
                         WHERE a.technician_id = $tech_id";
}

$appointments_result = $conn->query($appointments_sql);

// Display the appointments as a table (as in your original code)


// Fetch completed appointments for history
$completed_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                         c.first_name AS client_first_name, c.last_name AS client_last_name
                  FROM Appointments a 
                  JOIN Services s ON a.service_id = s.service_id
                  JOIN Clients c ON a.client_id = c.client_id
                  WHERE a.technician_id = $tech_id AND a.appointment_status = 'completed'
                  ORDER BY a.appointment_date DESC";
$completed_result = $conn->query($completed_sql);


// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Technician Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>

    <h1>Welcome, <?php echo $tech_info['first_name'] . ' ' . $tech_info['last_name']; ?></h1>

    <!-- Profile Information -->
    <h2>My Profile</h2>
    <p><strong>Email:</strong> <?php echo $tech_info['email']; ?></p>
    <p><strong>Phone Number:</strong> <?php echo $tech_info['phone_number']; ?></p>
    <p><strong>Clearance Level:</strong> <?php echo $tech_info['clearance_name']; ?></p>

    <h2>Update My Profile</h2>
    <form action="tech_update_profile.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo $_SESSION['tech_email']; ?>" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo $_SESSION['tech_phone']; ?>" required><br>

        <input type="submit" value="Update Profile">
    </form>

    <!-- Logout Button -->
    <form action="tech_logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>

    <h2>Filter Appointments</h2>
    <a href="tech_dashboard.php?filter=available">Available Appointments</a> |
    <a href="tech_dashboard.php?filter=accepted">Accepted Appointments</a> |
    <a href="tech_dashboard.php?filter=completed">Completed/Approved Appointments</a> |
    <a href="tech_dashboard.php?filter=cancelled">Cancelled Appointments</a>


    <!-- Display filtered appointments -->
    <?php if ($appointments_result->num_rows > 0): ?>
    <table border="1">
        <tr>
            <th>Service</th>
            <th>Client</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $appointments_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['service_name']; ?></td>
            <td><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></td>
            <td><?php echo $row['appointment_date']; ?></td>
            <td><?php echo $row['appointment_time']; ?></td>
            <td><?php echo ucfirst($row['appointment_status']); ?></td>

            <td>
                <!-- Show Accept button for requested appointments -->
                <?php if ($row['appointment_status'] == 'requested'): ?>
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="scheduled">
                    <input type="submit" name="accept_appointment" value="Accept">
                </form>
                <?php endif; ?>

                <!-- Cancel Appointment Button (for scheduled appointments) -->
                <?php if ($row['appointment_status'] == 'scheduled'): ?>
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="cancelled">
                    <input type="submit" name="cancel_appointment" value="Cancel"
                        onclick="return confirmCancel(<?php echo $row['appointment_id']; ?>, <?php echo isset($row['is_inhouse']) ? (int)$row['is_inhouse'] : 0; ?>);">
                </form>

                <!-- Start Appointment Button (scheduled → in progress) -->
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="in progress">
                    <input type="submit" value="Start Appointment">
                </form>
                <?php endif; ?>

                <!-- Mark as Tech Approved (in progress → tech approved) -->
                <?php if ($row['appointment_status'] == 'in progress'): ?>
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="tech approved">
                    <input type="submit" value="Complete">
                </form>
                <?php endif; ?>

                <!-- Final Completion Button (only visible after client approves) -->
                <?php if ($row['appointment_status'] == 'client approved'): ?>
                <form method="POST" action="../app_update_status.php" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                    <input type="hidden" name="new_status" value="completed">
                    <input type="submit" value="Complete Appointment">
                </form>
                <?php endif; ?>
            </td>
        </tr>

        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No appointments found.</p>
    <?php endif; ?>

    <!-- Add JavaScript to handle confirmation and future admin approval logic -->
    <script>
    function confirmCancel(appointmentId, isInhouse) {
        let message = "Are you sure you want to cancel this appointment?";

        // If the technician is in-house, notify about admin approval
        if (isInhouse == 1) {
            message += "\nThis cancellation will require admin approval.";
        }

        return confirm(message); // Shows the confirmation dialog
    }
    </script>


</body>

</html>