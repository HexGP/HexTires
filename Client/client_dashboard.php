<?php
session_start();

// Check if the client is logged in, if not redirect to the login page
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

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

// Fetch services with their names and SVG paths
$services_sql = "SELECT service_name, svg_icon FROM Services LIMIT 12";
$services_result = $conn->query($services_sql);

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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" type="text/css" href="client_styles.css">
</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Welcome, <?php echo $client_info['first_name']; ?></h2>
            <!-- Other sidebar content here -->
            <div class="sidebar-logout">
                <form action="client_logout.php" method="POST">
                    <button type="submit" name="logout" class="logout-button">Logout</button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Profile Section -->
            <section class="profile-section">
                <div class="profile-image"></div>
                <div class="profile-info">
                    <h3><?php echo $client_info['first_name'] . ' ' . $client_info['last_name']; ?></h3>
                    <h3><?php echo $client_info['email']; ?></h3>
                    <h3><?php echo $client_info['phone_number']; ?></h3>
                </div>
                <div class="button-container">
                    <button class="edit-button">Edit Information</button>
                </div>
            </section>

            <!-- Service Request Section -->
            <section class="service-request-section">
                <div class="service-grid">
                    <?php if ($services_result->num_rows > 0): ?>
                    <?php while ($service = $services_result->fetch_assoc()): ?>
                    <div class="service-card">
                        <img src="data:image/svg+xml;base64,<?php echo base64_encode($service['svg_icon']); ?>"
                            alt="<?php echo htmlspecialchars($service['service_name']); ?>" class="service-icon">
                        <p class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></p>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <p class="no-services">No services available at the moment.</p>
                    <?php endif; ?>
                </div>
                <div class="button-container">
                    <button onclick="location.href='request_service.php'" class="request-service-button">Request
                        Service</button>
                </div>
            </section>

            <!-- Appointments Section -->
            <section class="appointments-section">
                <h2>Appointments</h2>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Technician</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $appointments_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['service_name']; ?></td>
                            <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
                            <td><?php echo date("m/d/Y", strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo date("g:i A", strtotime($row['appointment_time'])); ?></td>
                            <td><?php echo ucfirst($row['appointment_status']); ?></td>
                            <td>
                                <?php if ($row['appointment_status'] == 'requested' || $row['appointment_status'] == 'scheduled'): ?>
                                <form method="POST" action="app_update_status.php" style="display:inline;">
                                    <input type="hidden" name="appointment_id"
                                        value="<?php echo $row['appointment_id']; ?>">
                                    <input type="submit" name="cancel_appointment" value="Cancel" class="action-button">
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>

</html>