<?php
// admin_dashboard.php

session_start();

// Check if the admin is logged in, if not redirect to the login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['admin_name']; ?>!</h1>

    <h2>Admin Dashboard</h2>
    <p>Here you can manage technicians, clients, and appointments.</p>

    <!-- Add links or actions for managing the system -->
    <a href="logic/manage_technicians.php">Manage Technicians</a><br>
    <a href="logic/manage_clients.php">Manage Clients</a><br>
    <a href="logic/manage_services.php">Manage Services</a><br>
    <a href="logic/manage_appointments.php">Manage Appointments</a><br>

    <!-- Logout Button -->
    <form action="admin_logout.php" method="POST">
        <input type="submit" value="Logout">
    </form>
</body>
</html>
