<?php
session_start();

// Check if the technician is logged in, if not redirect to the login page
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
    <link rel="stylesheet" type="text/css" href="tech_styles.css">
</head>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="appointment">
    <h1>Technician Dashboard</h1>

    <div class="appointment-container">
        <!-- Available Appointments Section -->
        <section class="form-group">
            <h2>Available Appointments</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Client Name</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display available appointments
                    // Example query: SELECT * FROM Appointments WHERE status='Available'
                    // while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php /* echo $row['appointment_id']; */ ?></td>
                        <td><?php /* echo $row['client_name']; */ ?></td>
                        <td><?php /* echo $row['service_name']; */ ?></td>
                        <td><?php /* echo $row['date']; */ ?></td>
                        <td><?php /* echo $row['time']; */ ?></td>
                        <td><button class="service-confirm">Accept</button></td>
                    </tr>
                    <?php // endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Accepted Appointments Section -->
        <section class="form-group">
            <h2>Accepted Appointments</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Client Name</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display accepted appointments
                    // Example query: SELECT * FROM Appointments WHERE status='Accepted'
                    ?>
                    <tr>
                        <td><?php /* echo $row['appointment_id']; */ ?></td>
                        <td><?php /* echo $row['client_name']; */ ?></td>
                        <td><?php /* echo $row['service_name']; */ ?></td>
                        <td><?php /* echo $row['date']; */ ?></td>
                        <td><?php /* echo $row['time']; */ ?></td>
                        <td>Accepted</td>
                    </tr>
                    <?php // endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Completed/Approved Appointments Section -->
        <section class="form-group">
            <h2>Completed/Approved Appointments</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Client Name</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display completed or approved appointments
                    // Example query: SELECT * FROM Appointments WHERE status='Completed' OR status='Approved'
                    ?>
                    <tr>
                        <td><?php /* echo $row['appointment_id']; */ ?></td>
                        <td><?php /* echo $row['client_name']; */ ?></td>
                        <td><?php /* echo $row['service_name']; */ ?></td>
                        <td><?php /* echo $row['date']; */ ?></td>
                        <td><?php /* echo $row['time']; */ ?></td>
                        <td>Completed</td>
                    </tr>
                    <?php // endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Schedule Section -->
        <section class="form-group">
            <h2>Schedule</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display technician schedule
                    // Example query: SELECT * FROM Schedule WHERE technician_id = $technician_id
                    ?>
                    <tr>
                        <td><?php /* echo $row['schedule_id']; */ ?></td>
                        <td><?php /* echo $row['date']; */ ?></td>
                        <td><?php /* echo $row['start_time']; */ ?></td>
                        <td><?php /* echo $row['end_time']; */ ?></td>
                        <td><?php /* echo $row['status']; */ ?></td>
                    </tr>
                    <?php // endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>


</html>
