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

// Fetch data for each section
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                     c.first_name AS client_first_name, c.last_name AS client_last_name, 
                     t.first_name AS tech_first_name, t.last_name AS tech_last_name, a.appointment_status
                     FROM Appointments a
                     JOIN Services s ON a.service_id = s.service_id
                     JOIN Clients c ON a.client_id = c.client_id
                     LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                     ORDER BY appointment_date ASC LIMIT 15";
$appointments_result = $conn->query($appointments_sql);

$car_types_sql = "SELECT car_type_id, car_desc FROM CarTypes ORDER BY car_type_id ASC LIMIT 5";
$car_types_result = $conn->query($car_types_sql);

$tire_types_sql = "SELECT tire_type_id, tire_type FROM TireTypes ORDER BY tire_type_id ASC LIMIT 5";
$tire_types_result = $conn->query($tire_types_sql);

// Fetch Payments data
$payments_sql = "SELECT p.payment_id, c.first_name AS client_first_name, c.last_name AS client_last_name, 
                t.first_name AS tech_first_name, t.last_name AS tech_last_name, p.amount_paid, p.payment_date
                FROM Payments p
                JOIN Clients c ON p.client_id = c.client_id
                LEFT JOIN Technicians t ON p.technician_id = t.technician_id
                ORDER BY p.payment_date ASC LIMIT 5";
$payments_result = $conn->query($payments_sql);


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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_styles.css"> <!-- Link to your CSS file -->
</head>

<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="admin-info">
                <div class="admin-text">
                    <h3>Tech</h3>
                    <div class="image-frame">
                        <img src="<?php echo $img_src; ?>" class="profile-preview" alt="Tech Profile Picture">
                    </div>
                    <p><?php echo $_SESSION['tech_name']; ?></p>
                </div>
            </div>
            <div class="button-group">
                <button onclick="window.location.href='tech_update_profile.php';"
                    class="settings-button">Settings</button>
                <button onclick="window.location.href='tech_logout.php';" class="logout-button">Logout</button>
            </div>
        </nav>

        <main class="dashboard-grid">
            <div class="grid-stack">

                <div class="grid-stamp">
                    <!-- Car Types Table View -->
                    <div class="grid-item car-types">
                        <h2>Car Types</h2>
                        <div class="table-container">
                            <div class="table-header">
                                <span>ID</span>
                                <span>Description</span>
                            </div>
                            <?php while ($row = $car_types_result->fetch_assoc()): ?>
                            <div class="table-row">
                                <span><?php echo $row['car_type_id']; ?></span>
                                <span><?php echo $row['car_desc']; ?></span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <a href="logic/manage_car_types.php" class="overlay-link"></a>
                    </div>

                    <!-- Tire Types Table View -->
                    <div class="grid-item tire-types">
                        <h2>Tire Types</h2>
                        <div class="table-container">
                            <div class="table-header">
                                <span>ID</span>
                                <span>Type</span>
                            </div>
                            <?php while ($row = $tire_types_result->fetch_assoc()): ?>
                            <div class="table-row">
                                <span><?php echo $row['tire_type_id']; ?></span>
                                <span><?php echo $row['tire_type']; ?></span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <a href="logic/manage_tire_types.php" class="overlay-link"></a>
                    </div>
                </div>
            </div>

            <!-- Payments Table View -->
            <div class="grid-item payments">
                <h2>Payments</h2>
                <div class="table-container">
                    <div class="table-header">
                        <span>Payment ID</span>
                        <span>Client</span>
                        <span>Technician</span>
                        <span>Amount Paid</span>
                        <span>Payment Date</span>
                    </div>
                    <?php while ($row = $payments_result->fetch_assoc()): ?>
                    <div class="table-row">
                        <span><?php echo $row['payment_id']; ?></span>
                        <span><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></span>
                        <span><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></span>
                        <span>$<?php echo number_format($row['amount_paid'], 2); ?></span>
                        <span><?php echo date("m/d/Y", strtotime($row['payment_date'])); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_payments.php" class="overlay-link"></a>
            </div>

            <!-- Appointments Table View -->
            <div class="grid-item appointments">
                <h2>Appointments</h2>
                <div class="table-container">
                    <div class="table-header">
                        <span>ID</span>
                        <span>Client</span>
                        <span>Technician</span>
                        <span>Date</span>
                        <span>Status</span>
                    </div>
                    <?php while ($row = $appointments_result->fetch_assoc()): ?>
                    <div class="table-row">
                        <span><?php echo $row['appointment_id']; ?></span>
                        <span><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></span>
                        <span><?php echo $row['tech_first_name'] ? $row['tech_first_name'] . ' ' . $row['tech_last_name'] : 'N/A'; ?></span>
                        <span><?php echo date("m/d/Y", strtotime($row['appointment_date'])); ?></span>
                        <span><?php echo $row['appointment_status']; ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_appointments.php" class="overlay-link"></a>
            </div>
            <div class="grid-stack">
                <div class="grid-snap">



                    <!-- Schedule Table View -->
                    <div class="grid-item schedule">
                        <h2>Schedule</h2>
                        <div class="table-container">
                            <div class="table-header">
                                <span>ID</span>
                                <span>Technician ID</span>
                                <span>Date</span>
                                <span>Start</span>
                                <span>End</span>
                            </div>
                            <?php while ($row = $schedule_result->fetch_assoc()): ?>
                            <div class="table-row">
                                <span><?php echo $row['schedule_id']; ?></span>
                                <span><?php echo $row['technician_id']; ?></span>
                                <span><?php echo date("m/d/Y", strtotime($row['date'])); ?></span>
                                <span><?php echo $row['start_time']; ?></span>
                                <span><?php echo $row['end_time']; ?></span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <a href="logic/manage_schedule.php" class="overlay-link"></a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>