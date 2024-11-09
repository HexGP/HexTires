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

// Display the binary image as a base64 encoded image
$admin_img = base64_encode($admin['admin_Img']);
$img_src = "data:image/png;base64," . $admin_img;

// Fetch data for appointment section
$appointments_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, 
           c.first_name AS client_first_name, c.last_name AS client_last_name, 
           t.first_name AS tech_first_name, t.last_name AS tech_last_name, a.appointment_status
    FROM Appointments a
    JOIN Services s ON a.service_id = s.service_id
    JOIN Clients c ON a.client_id = c.client_id
    LEFT JOIN Technicians t ON a.technician_id = t.technician_id
    ORDER BY appointment_date DESC LIMIT 5";
$appointments_result = $conn->query($appointments_sql);

// Fetch data for appointment section
$technicians_sql = "SELECT technician_id, first_name, last_name, email, phone_number FROM Technicians ORDER BY technician_id DESC LIMIT 15";
$technicians_result = $conn->query($technicians_sql);

// Fetch data for client section
$clients_sql = "SELECT client_id, first_name, last_name, email, phone_number FROM Clients ORDER BY client_id DESC LIMIT 15";
$clients_result = $conn->query($clients_sql);

// Fetch data for services section
$services_sql = "SELECT service_id, service_name, service_price FROM Services ORDER BY service_id ASC LIMIT 25";
$services_result = $conn->query($services_sql);

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
                <div class="admin-text">
                    <h3>Admin</h3>
                    <!-- Display admin profile picture -->
                    <!-- Hexagon Frame Wrapper -->
                    <div class="hexagon-frame">
                        <img src="<?php echo $img_src; ?>" class="profile-preview" alt="Admin Profile Picture">
                    </div>
                    <p><?php echo $_SESSION['admin_name']; ?></p>
                </div>
            </div>
            <div class="button-group">
                <form action="logic/manage_settings.php" method="GET" class="settings-form">
                    <button type="submit">Settings</button>
                </form>
                <form action="admin_logout.php" method="POST" class="logout-form">
                    <button type="submit">Logout</button>
                </form>
            </div>
        </nav>

        <main class="dashboard-grid">
            <a href="logic/manage_appointments.php" class="grid-item appointments">
                <h2>Appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Technician</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $appointments_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['appointment_id']; ?></td>
                            <td><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></td>
                            <td><?php echo $row['tech_first_name'] ? $row['tech_first_name'] . ' ' . $row['tech_last_name'] : 'N/A'; ?>
                            </td>
                            <td><?php echo $row['appointment_date']; ?></td>
                            <td><?php echo $row['appointment_status']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </a>

            <a href="logic/manage_services.php" class="grid-item services">
                <h2>Services</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service Name</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $services_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['service_id']; ?></td>
                            <td><?php echo $row['service_name']; ?></td>
                            <td><?php echo number_format($row['service_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </a>

            <a href="logic/manage_clients.php" class="grid-item clients">
                <h2>Clients</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $clients_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['client_id']; ?></td>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </a>

            <a href="logic/manage_technicians.php" class="grid-item technicians">
                <h2>Technicians</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $technicians_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['technician_id']; ?></td>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </a>

            <a href="logic/manage_schedules.php" class="grid-item schedules">
                <h2>Schedules</h2>
                <p>Manage technicians schedules and work hours.</p>
            </a>
        </main>
    </div>
</body>

</html>
