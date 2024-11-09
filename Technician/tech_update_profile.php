<?php
session_start();

// Check if the technician is logged in; if not, redirect to the login page
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

// Retrieve the technician's current profile information
$tech_sql = "SELECT email, phone_number FROM Technicians WHERE technician_id = $tech_id";
$tech_result = $conn->query($tech_sql);

if ($tech_result->num_rows > 0) {
    // Fetch technician details
    $tech_info = $tech_result->fetch_assoc();
} else {
    echo "Error fetching profile information.";
}

// Retrieve technician's schedule, including both appointments and availability
$schedule_sql = "SELECT 
                    schedule_date, 
                    start_time, 
                    end_time, 
                    status
                 FROM 
                    schedule 
                 WHERE 
                    technician_id = $tech_id 
                 ORDER BY 
                    schedule_date, start_time";

$schedule_result = $conn->query($schedule_sql);

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Update the technician's profile
    $update_sql = "UPDATE Technicians SET email = '$email', phone_number = '$phone_number' WHERE technician_id = $tech_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $_SESSION['tech_email'] = $email;
        $_SESSION['tech_phone'] = $phone_number;
        header("Location: tech_dashboard.php");
        exit();
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Technician Profile</title>
    <link rel="stylesheet" type="text/css" href="tech_styles.css">
</head>

<body>
    <h1>Edit Profile</h1>

    <!-- Back to Dashboard page -->
    <form method="GET" action="tech_dashboard.php" style="display:inline;">
        <input type="submit" value="Back to Dashboard">
    </form>

    <!-- Profile Update Form -->
    <form action="tech_update_profile.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($tech_info['email']); ?>" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($tech_info['phone_number']); ?>" required><br>

        <input type="submit" name="update_profile" value="Update Profile">
    </form>

    <h2>Upcoming Schedule</h2>

    <?php if ($schedule_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
            <?php while ($row = $schedule_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['schedule_date']; ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No schedule available.</p>
    <?php endif; ?>

</body>

</html>
