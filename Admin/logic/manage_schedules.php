<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle sorting logic
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'schedule_date';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
$next_sort_order = $sort_order === 'ASC' ? 'desc' : 'asc';

// Fetch schedules with sorting
$schedule_sql = "
    SELECT s.schedule_id, s.technician_id, s.appointment_id, s.schedule_date, s.start_time, s.end_time, s.status,
           t.first_name AS tech_first_name, t.last_name AS tech_last_name
    FROM schedule s
    LEFT JOIN Technicians t ON s.technician_id = t.technician_id
    ORDER BY $sort_column $sort_order";

$schedule_result = $conn->query($schedule_sql);

if (!$schedule_result) {
    die("Error retrieving schedules: " . $conn->error);
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Technician Schedules</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>

    <h1>Manage Technician Schedules</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <!-- Display schedules -->
    <?php if ($schedule_result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th><a href="?sort=schedule_id&order=<?php echo $next_sort_order; ?>">Schedule ID</a></th>
                <th><a href="?sort=technician_id&order=<?php echo $next_sort_order; ?>">Technician</a></th>
                <th><a href="?sort=appointment_id&order=<?php echo $next_sort_order; ?>">Appointment ID</a></th>
                <th><a href="?sort=schedule_date&order=<?php echo $next_sort_order; ?>">Date</a></th>
                <th><a href="?sort=start_time&order=<?php echo $next_sort_order; ?>">Start Time</a></th>
                <th><a href="?sort=end_time&order=<?php echo $next_sort_order; ?>">End Time</a></th>
                <th><a href="?sort=status&order=<?php echo $next_sort_order; ?>">Status</a></th>
            </tr>
            <?php while ($row = $schedule_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['schedule_id']; ?></td>
                    <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
                    <td><?php echo $row['appointment_id'] ? $row['appointment_id'] : 'N/A'; ?></td>
                    <td><?php echo $row['schedule_date']; ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No schedules found.</p>
    <?php endif; ?>

</body>
</html>
