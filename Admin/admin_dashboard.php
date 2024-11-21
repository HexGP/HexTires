<?php
// admin_dashboard.php

session_start();

// Check if the admin is logged in, if not redirect to the login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the current admin's data for display
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$admin_img = base64_encode($admin['admin_Img']);
$img_src = "data:image/png;base64," . $admin_img;

// Fetch data for each section
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
                     c.first_name AS client_first_name, c.last_name AS client_last_name, 
                     t.first_name AS tech_first_name, t.last_name AS tech_last_name, a.appointment_status
                     FROM Appointments a
                     JOIN Services s ON a.service_id = s.service_id
                     JOIN Clients c ON a.client_id = c.client_id
                     LEFT JOIN Technicians t ON a.technician_id = t.technician_id
                     ORDER BY appointment_date ASC LIMIT 10";
$appointments_result = $conn->query($appointments_sql);


// $car_types_sql = "SELECT car_type_id, car_desc FROM CarTypes ORDER BY car_type_id ASC LIMIT 5";
// $car_types_result = $conn->query($car_types_sql);

// $tire_types_sql = "SELECT tire_type_id, tire_type FROM TireTypes ORDER BY tire_type_id ASC LIMIT 5";
// $tire_types_result = $conn->query($tire_types_sql);


// Fetch Payments data
$payments_sql = "SELECT p.payment_id, c.first_name AS client_first_name, c.last_name AS client_last_name, 
                t.first_name AS tech_first_name, t.last_name AS tech_last_name, p.amount_paid, p.payment_date
                FROM Payments p
                JOIN Clients c ON p.client_id = c.client_id
                LEFT JOIN Technicians t ON p.technician_id = t.technician_id
                ORDER BY p.payment_date ASC LIMIT 10";
$payments_result = $conn->query($payments_sql);

if (!$payments_result) {
    die("SQL Error: " . $conn->error);
}

$services_sql = "SELECT service_id, svg_icon, service_name, service_price FROM Services ORDER BY service_id ASC LIMIT 10";
$services_result = $conn->query($services_sql);

$technicians_sql = "SELECT technician_id, first_name, last_name, email, phone_number FROM Technicians ORDER BY technician_id ASC LIMIT 10";
$technicians_result = $conn->query($technicians_sql);

$schedule_sql = "SELECT schedule_id, technician_id, schedule_date AS date, start_time, end_time 
                 FROM Schedule 
                 ORDER BY schedule_date ASC 
                 LIMIT 10";
$schedule_result = $conn->query($schedule_sql);

if (!$schedule_result) {
    die("SQL Error: " . $conn->error);
}

$clients_sql = "SELECT client_id, first_name, last_name, email, phone_number FROM Clients ORDER BY client_id ASC LIMIT 10";
$clients_result = $conn->query($clients_sql);

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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_styles.css"> <!-- Link to your CSS file -->
</head>

<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="admin-info">
                <h3>Admin </h3>
                <div class="circle-frame">
                    <img src="../images/admin.svg" alt="Profile Picture" class="profile-preview">
                </div>

                <p><?php echo $admin['first_name'] . " " . $admin['last_name']; ?></p>
                <p><?php echo $admin['email']; ?></p>
                <p><?php echo formatPhoneNumber($admin['phone_number']); ?></p>
            </div>

            <div class="button-group">
                <button onclick="window.location.href='logic/manage_settings.php';"
                    class="settings-button">Settings</button>
                <button onclick="window.location.href='admin_logout.php';" class="logout-button">Logout</button>
            </div>
        </nav>

        <main class="dashboard-grid">
            <!-- Appointments Table View -->
            <div class="grid-item appointments">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/appointment.svg" class="profile-preview">
                    </div>
                    Appointments
                </h3>
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

            <!-- Payments Table View -->
            <div class="grid-item payments">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/payment.svg" class="profile-preview">
                    </div>
                    Payments
                </h3>
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

            <!-- Services Table View -->
            <div class="grid-item services">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/service.svg" class="profile-preview">
                    </div>
                    Services
                </h3>
                <div class="table-container">
                    <div class="table-header">
                        <span>ID</span>
                        <span>Icon</span>
                        <span>Service Name</span>
                        <span>Price</span>
                    </div>
                    <?php while ($row = $services_result->fetch_assoc()): ?>
                    <div class="table-row">
                        <span><?php echo $row['service_id']; ?></span>
                        <span>
                            <img src="data:image/svg+xml;base64,<?php echo base64_encode($row['svg_icon']); ?>"
                                alt="Service Icon" class="service-icon">
                        </span>
                        <span><?php echo $row['service_name']; ?></span>
                        <span><?php echo number_format($row['service_price'], 2); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_services.php" class="overlay-link"></a>
            </div>

            <!-- Technician Table View -->
            <div class="grid-item technicians">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/tech.svg" class="profile-preview">
                    </div>
                    Technicians
                </h3>
                <div class="table-container">
                    <div class="table-header">
                        <span>ID</span>
                        <span>Name</span>
                        <span>Email</span>
                        <span>Phone Number</span>
                    </div>
                    <?php while ($row = $technicians_result->fetch_assoc()): ?>
                    <div class="table-row">
                        <span><?php echo $row['technician_id']; ?></span>
                        <span><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span>
                        <span><?php echo $row['email']; ?></span>
                        <span><?php echo formatPhoneNumber($row['phone_number']); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_technicians.php" class="overlay-link"></a>
            </div>

            <!-- Clients Table View -->
            <div class="grid-item clients">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/client.svg" class="profile-preview">
                    </div>
                    Clients
                </h3>
                <div class="table-container">
                    <div class="table-header">
                        <span>ID</span>
                        <span>Name</span>
                        <span>Email</span>
                        <span>Phone Number</span>
                    </div>
                    <?php while ($row = $clients_result->fetch_assoc()): ?>
                    <div class="table-row">
                        <span><?php echo $row['client_id']; ?></span>
                        <span><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span>
                        <span><?php echo $row['email']; ?></span>
                        <span><?php echo formatPhoneNumber($row['phone_number']); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_clients.php" class="overlay-link"></a>
            </div>

            <!-- Schedule Table View -->
            <div class="grid-item schedule">
                <h3>
                    <div class="grid-svg">
                        <img src="../images/schedule.svg" class="profile-preview">
                    </div>
                    Schedule
                </h3>
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
                        <span><?php echo date("h:i A", strtotime($row['start_time'])); ?></span>
                        <span><?php echo date("h:i A", strtotime($row['end_time'])); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="logic/manage_schedule.php" class="overlay-link"></a>
            </div>
        </main>
    </div>
</body>

</html>