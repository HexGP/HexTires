<?php
session_start();

// Check if the technician is logged in, if not redirect to the login page
if (!isset($_SESSION['tech_id'])) {
    header("Location: tech_login.php");
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the logged-in technician's ID
$tech_id = $_SESSION['tech_id'];

// Fetch technician's details
$tech_sql = "SELECT first_name, last_name, email, phone_number FROM Technicians WHERE technician_id = $tech_id";
$tech_result = $conn->query($tech_sql);
$tech_info = $tech_result->fetch_assoc();

// Default filters
$appointment_filter = isset($_GET['appointment_filter']) ? $_GET['appointment_filter'] : 'all';
$payment_filter = isset($_GET['payment_filter']) ? $_GET['payment_filter'] : 'all';
$schedule_filter = isset($_GET['schedule_filter']) ? $_GET['schedule_filter'] : 'all';


// Fetch filtered appointments
$appointment_condition = "";
if ($appointment_filter === 'requested') {
    $appointment_condition = "AND a.appointment_status = 'requested'";
} elseif ($appointment_filter === 'scheduled') {
    $appointment_condition = "AND a.appointment_status = 'scheduled'";
} elseif ($appointment_filter === 'completed') {
    $appointment_condition = "AND a.appointment_status = 'completed'";
}

$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                            s.service_name, s.service_price, 
                            c.first_name AS client_first_name, c.last_name AS client_last_name, 
                            a.appointment_status
                     FROM Appointments a
                     JOIN Services s ON a.service_id = s.service_id
                     JOIN Clients c ON a.client_id = c.client_id
                     WHERE a.technician_id = $tech_id $appointment_condition
                     ORDER BY a.appointment_status ASC";
$appointments_result = $conn->query($appointments_sql);

// Fetch filtered payments
$payment_condition = ($payment_filter == 'completed') ? "WHERE p.payment_status = 'completed'" : "";
$payments_sql = "SELECT p.payment_id, p.amount_paid, p.tip_amount, p.payment_date, 
                        c.first_name AS client_first_name, c.last_name AS client_last_name
                 FROM Payments p
                 JOIN Clients c ON p.client_id = c.client_id
                 WHERE p.technician_id = $tech_id
                 ORDER BY p.payment_date DESC";
$payments_result = $conn->query($payments_sql);

// Fetch filtered schedule
$schedule_sql = "SELECT schedule_id, schedule_date, start_time, end_time 
                 FROM Schedule 
                 WHERE technician_id = $tech_id 
                 ORDER BY schedule_date DESC";
$schedule_result = $conn->query($schedule_sql);

// Calculate totals for requested and completed appointments
$total_requested_sql = "SELECT COUNT(*) AS total_requested FROM Appointments WHERE technician_id = $tech_id AND appointment_status = 'requested'";
$total_completed_sql = "SELECT COUNT(*) AS total_completed FROM Appointments WHERE technician_id = $tech_id AND appointment_status = 'completed'";

$total_requested_result = $conn->query($total_requested_sql)->fetch_assoc();
$total_completed_result = $conn->query($total_completed_sql)->fetch_assoc();

$total_requested = $total_requested_result['total_requested'];
$total_completed = $total_completed_result['total_completed'];

// Calculate total time in hours
$total_hours_sql = "
    SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) AS total_minutes
    FROM Schedule
    WHERE technician_id = $tech_id";
$total_minutes = $conn->query($total_hours_sql)->fetch_assoc()['total_minutes'];
$total_hours = ($total_minutes) ? round($total_minutes / 60, 2) : 0;

// Calculate total tips and total amount
$total_tips_sql = "SELECT SUM(tip_amount) AS total_tips FROM Payments WHERE technician_id = $tech_id";
$total_amount_sql = "SELECT SUM(amount_paid) AS total_amount FROM Payments WHERE technician_id = $tech_id";
$total_tips = $conn->query($total_tips_sql)->fetch_assoc()['total_tips'];
$total_amount = $conn->query($total_amount_sql)->fetch_assoc()['total_amount'];

// Find the most common client
$most_common_client_sql = "
    SELECT CONCAT(c.first_name, ' ', c.last_name) AS client_name, COUNT(*) AS count
    FROM Payments p
    JOIN Clients c ON p.client_id = c.client_id
    WHERE p.technician_id = $tech_id
    GROUP BY p.client_id
    ORDER BY count DESC
    LIMIT 1";
$most_common_client_result = $conn->query($most_common_client_sql);
$most_common_client = ($most_common_client_result->num_rows > 0) ? $most_common_client_result->fetch_assoc()['client_name'] : 'None';

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

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="tech_styles.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="tech-info">
                <h3>Technician</h3>
                <div class="circle-frame">
                    <img src="../images/tech.svg" alt="Profile Picture" class="profile-preview">
                </div>
                <p><?php echo htmlspecialchars($tech_info['first_name'] . " " . $tech_info['last_name']); ?></p>
                <p><?php echo htmlspecialchars($tech_info['email']); ?></p>
                <p><?php echo formatPhoneNumber(htmlspecialchars($tech_info['phone_number'])); ?></p>
            </div>

            <div class="button-group">
                <button onclick="window.location.href='tech_update_profile.php';"
                    class="settings-button">Settings</button>
                <button onclick="window.location.href='tech_logout.php';" class="logout-button">Logout</button>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="dashboard-grid">
            <!-- Payments -->
            <div class="grid-item">
                <div class="table-header">
                    <h2>Payments</h2>
                    <div class="totals">
                        <span>Total Tips: $<?php echo number_format($total_tips, 2); ?></span>
                        <span>Total Amount: $<?php echo number_format($total_amount, 2); ?></span>
                        <span>Most Common Client: <?php echo htmlspecialchars($most_common_client); ?></span>
                    </div>
                    <div class="filters">
                        <button onclick="window.location.href='?payment_filter=all';">All</button>
                        <button onclick="window.location.href='?payment_filter=completed';">Completed</button>
                        <button onclick="window.location.href='?payment_filter=pending';">Pending</button>
                    </div>
                </div>

                <div class="table-container scrollable">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Amount Paid</th>
                                <th>Tip</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $payments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['payment_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['client_first_name'] . " " . $row['client_last_name']); ?>
                                </td>
                                <td>$<?php echo number_format($row['amount_paid'], 2); ?></td>
                                <td>$<?php echo number_format($row['tip_amount'], 2); ?></td>
                                <td><?php echo date("m/d/Y", strtotime($row['payment_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Schedule -->
            <div class="grid-item">
                <div class="table-header">
                    <h2>Schedule</h2>
                    <div class="totals">
                        <span>Total Hours Assigned: <?php echo $total_hours; ?> hrs</span>
                    </div>
                    <div class="filters">
                        <button onclick="window.location.href='?schedule_filter=all';">All</button>
                        <button onclick="window.location.href='?schedule_filter=upcoming';">Upcoming</button>
                        <button onclick="window.location.href='?schedule_filter=completed';">Completed</button>
                    </div>
                </div>

                <div class="table-container scrollable">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $schedule_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['schedule_id']); ?></td>
                                <td><?php echo date("m/d/Y", strtotime($row['schedule_date'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Appointments -->
            <div class="grid-item">
                <div class="table-header">
                    <h2>Appointments</h2>
                    <div class="totals">
                        <span>Total Requested: <?php echo $total_requested; ?></span>
                        <span>Total Completed: <?php echo $total_completed; ?></span>
                    </div>
                    <div class="filters">
                        <button onclick="window.location.href='?appointment_filter=all';">All</button>
                        <button onclick="window.location.href='?appointment_filter=requested';">Requested</button>
                        <button onclick="window.location.href='?appointment_filter=scheduled';">Scheduled</button>
                        <button onclick="window.location.href='?appointment_filter=completed';">Completed</button>
                    </div>
                </div>

                <div class="table-container scrollable">
                    <table>
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Price</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $appointments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['client_first_name'] . ' ' . $row['client_last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td>$<?php echo number_format($row['service_price'], 2); ?></td>
                                <td><?php echo date("m/d/Y", strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_status']); ?></td>
                                <td>
                                    <?php if ($row['appointment_status'] === 'requested'): ?>
                                    <form method="POST" action="schedule_appointment.php" style="margin:0;">
                                        <input type="hidden" name="appointment_id"
                                            value="<?php echo htmlspecialchars($row['appointment_id']); ?>">
                                        <input type="hidden" name="appointment_date"
                                            value="<?php echo htmlspecialchars($row['appointment_date']); ?>">
                                        <input type="hidden" name="start_time"
                                            value="<?php echo htmlspecialchars($row['appointment_time']); ?>">
                                        <input type="hidden" name="technician_id"
                                            value="<?php echo htmlspecialchars($tech_id); ?>">
                                        <button type="submit" name="schedule" class="schedule-button">Schedule</button>
                                    </form>
                                    <?php else: ?>
                                    N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>