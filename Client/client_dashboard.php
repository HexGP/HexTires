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

// Default filters
$appointment_filter = isset($_GET['appointment_filter']) ? $_GET['appointment_filter'] : 'all';

// Define the condition for filtering appointments
$appointment_condition = "";
if ($appointment_filter === 'requested') {
    $appointment_condition = "AND a.appointment_status = 'requested'";
} elseif ($appointment_filter === 'scheduled') {
    $appointment_condition = "AND a.appointment_status = 'scheduled'";
} elseif ($appointment_filter === 'completed') {
    $appointment_condition = "AND a.appointment_status = 'completed'";
}

// Query for fetching appointments
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                            s.service_name, s.service_price, 
                            t.first_name AS tech_first_name, t.last_name AS tech_last_name, 
                            a.appointment_status 
                     FROM Appointments a 
                     JOIN Services s ON a.service_id = s.service_id
                     LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                     WHERE a.client_id = $client_id AND a.appointment_status != 'cancelled' $appointment_condition
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Execute the query and handle potential errors
$appointments_result = $conn->query($appointments_sql);

// Calculate total tips and total amount
$total_tips_sql = "SELECT SUM(tip_amount) AS total_tips FROM Payments WHERE client_id = $client_id";
$total_amount_sql = "SELECT SUM(amount_paid) AS total_amount FROM Payments WHERE client_id = $client_id";

// Fetch total tips
$total_tips_result = $conn->query($total_tips_sql);
$total_tips = $total_tips_result ? $total_tips_result->fetch_assoc()['total_tips'] : 0;

// Fetch total amount
$total_amount_result = $conn->query($total_amount_sql);
$total_amount = $total_amount_result ? $total_amount_result->fetch_assoc()['total_amount'] : 0;


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

function formatPhoneNumber($phoneNumber) {
    // Remove all non-numeric characters
    $cleaned = preg_replace('/\D/', '', $phoneNumber);

    // Check if the cleaned number has 10 digits
    if (strlen($cleaned) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($cleaned, 0, 3), // Area code
            substr($cleaned, 3, 3), // First 3 digits
            substr($cleaned, 6, 4)  // Last 4 digits
        );
    }

    // Return the original number if it's not 10 digits
    return $phoneNumber;
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
        <nav class="sidebar">
            <h2>Welcome, <?php echo $client_info['first_name']; ?></h2>
            <!-- Other sidebar content here -->
            <div class="sidebar-logout">
                <form action="client_logout.php" method="POST">
                    <button type="submit" name="logout" class="logout-button">Logout</button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="dashboard-grid">
            <!-- Profile Section -->
            <section class="profile-section">
                <div class="circle-frame">
                    <img src="../images/client.svg" alt="Profile Picture" class="profile-preview">
                </div>
                <div class="profile-info">
                    <h3><?php echo $client_info['first_name'] . ' ' . $client_info['last_name']; ?></h3>
                    <h3><?php echo $client_info['email']; ?></h3>
                    <h3><?php echo formatPhoneNumber($client_info['phone_number']); ?></h3>
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
                <!-- <h2>Appointments</h2> -->
                <div class="table-header">
                    <h2>Appointments</h2>
                    <div class="totals">
                        <span>Total Tips: $<?php echo number_format($total_tips, 2); ?></span>
                        <span>Total Amount: $<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="filters">
                        <button onclick="window.location.href='?appointment_filter=all';">All</button>
                        <button onclick="window.location.href='?appointment_filter=requested';">Requested</button>
                        <button onclick="window.location.href='?appointment_filter=scheduled';">Scheduled</button>
                        <button onclick="window.location.href='?appointment_filter=completed';">Completed</button>
                        <!-- <button onclick="window.location.href='?appointment_filter=cancelled';">Cancelled</button> -->
                    </div>
                </div>
                <div class="table-container scrollable">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Technician</th>
                                <th>Price</th>
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
                                <td>$<?php echo $row['service_price']; ?></td>
                                <td><?php echo date("m/d/Y", strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo date("g:i A", strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo ucfirst($row['appointment_status']); ?></td>
                                <td>
                                    <?php if ($row['appointment_status'] == 'requested'): ?>
                                    <form method="POST" action="app_update_status.php" style="display:inline;">
                                        <input type="hidden" name="appointment_id"
                                            value="<?php echo $row['appointment_id']; ?>">
                                        <input type="submit" name="cancel_appointment" value="Cancel"
                                            class="action-button">
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

</body>

</html>