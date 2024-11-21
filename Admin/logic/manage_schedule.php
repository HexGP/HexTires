<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Default sorting
$sort_column = $_GET['sort'] ?? 'appointment_date';
$sort_order = $_GET['order'] ?? 'asc';
$next_sort_order = ($sort_order === 'asc') ? 'desc' : 'asc';

// Fetch weekly schedule
// $week_start = date('Y-m-d', strtotime('monday this week'));
// $week_end = date('Y-m-d', strtotime('sunday this week'));

// $schedule_sql = "
//     SELECT s.schedule_id, s.technician_id, t.first_name AS tech_first_name, t.last_name AS tech_last_name,
//            a.appointment_id, a.appointment_date, a.appointment_time AS start_time, 
//            ADDTIME(a.appointment_time, '01:00:00') AS end_time
//     FROM Schedule s
//     JOIN Technicians t ON s.technician_id = t.technician_id
//     JOIN Appointments a ON s.appointment_id = a.appointment_id
//     WHERE a.appointment_date BETWEEN '$week_start' AND '$week_end'
//     ORDER BY $sort_column $sort_order";

// $schedule_result = $conn->query($schedule_sql);


// Fetch the entire schedule
$schedule_sql = "
    SELECT s.schedule_id, s.technician_id, t.first_name AS tech_first_name, t.last_name AS tech_last_name,
           a.appointment_id, a.appointment_date, a.appointment_time AS start_time, 
           ADDTIME(a.appointment_time, '01:00:00') AS end_time
    FROM Schedule s
    LEFT JOIN Technicians t ON s.technician_id = t.technician_id
    LEFT JOIN Appointments a ON s.appointment_id = a.appointment_id
    ORDER BY $sort_column $sort_order";

$schedule_result = $conn->query($schedule_sql);

// Error handling
if ($schedule_result === false) {
    die("Query Error: " . $conn->error);
}
if ($schedule_result->num_rows === 0) {
    echo "<script>alert('No schedules found.');</script>";
}



// Handle Add to Log button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_log'])) {
    $stmt = $conn->prepare("CALL AddToScheduleLog(?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $_POST['technician_id'], $_POST['appointment_id'], $_POST['schedule_date'], $_POST['start_time'], $_POST['end_time']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule added to log successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding to log: ' . $stmt->error]);
    }

    $stmt->close();
    exit();
}

// Handle Add All to Log button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_all_to_log'])) {
    $stmt = $conn->prepare("CALL AddAllSchedulesToLog()");
    
    if ($stmt->execute()) {
        echo "<script>alert('All schedules added to log and removed from schedule table successfully!');</script>";
    } else {
        echo "<script>alert('Error adding schedules to log: " . $stmt->error . "');</script>";
    }
    
    $stmt->close();
}

// Handle Delete Schedule button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];

    $delete_sql = "DELETE FROM Schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $schedule_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $stmt->error]);
    }

    $stmt->close();
    exit();
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="manage_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body>
    <h1>Manage Schedule</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>



    <div class="container">
        <!-- Schedule Table -->
        <div class="table-section">
            <h2>Weekly Schedule</h2>
            <form id="schedule-actions">
                <table border="1">
                    <tr>
                        <th><a href="?sort=tech_first_name&order=<?php echo $next_sort_order; ?>">Technician</a></th>
                        <th><a href="?sort=appointment_id&order=<?php echo $next_sort_order; ?>">Appointment ID</a></th>
                        <th><a href="?sort=appointment_date&order=<?php echo $next_sort_order; ?>">Date</a></th>
                        <th><a href="?sort=start_time&order=<?php echo $next_sort_order; ?>">Start Time</a></th>
                        <th><a href="?sort=end_time&order=<?php echo $next_sort_order; ?>">End Time</a></th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($row = $schedule_result->fetch_assoc()): ?>
                    <tr id="row-<?php echo $row['schedule_id']; ?>">
                        <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
                        <td><?php echo $row['appointment_id']; ?></td>
                        <td><?php echo date("m/d/Y", strtotime($row['appointment_date'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                        <td>
                            <!-- Add to Log Button -->
                            <input type="hidden" name="technician_id" value="<?php echo $row['technician_id']; ?>">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                            <input type="hidden" name="schedule_date" value="<?php echo $row['appointment_date']; ?>">
                            <input type="hidden" name="start_time" value="<?php echo $row['start_time']; ?>">
                            <input type="hidden" name="end_time" value="<?php echo $row['end_time']; ?>">
                            <input type="button" value="Add to Log" class="button"
                                onclick="addToLog(this.closest('tr'))">

                            <!-- Delete Schedule Button -->
                            <input type="hidden" name="schedule_id" value="<?php echo $row['schedule_id']; ?>">
                            <input type="button" value="Delete" class="delete-button"
                                onclick="deleteSchedule(<?php echo $row['schedule_id']; ?>)">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </form>
        </div>

        <div class="form-section">
            <!-- Add All to Log Button -->
            <form method="POST" action="" style="margin-top: 20px;">
            <h2>This button will add all schedules to the logs</h2>
                <input type="submit" name="add_all_to_log" value="Add All to Log" class="button">
            </form>
        </div>
    </div>

    <script>
    function addToLog(row) {
        const formData = new FormData();
        formData.append('add_to_log', true);
        formData.append('technician_id', row.querySelector('input[name="technician_id"]').value);
        formData.append('appointment_id', row.querySelector('input[name="appointment_id"]').value);
        formData.append('schedule_date', row.querySelector('input[name="schedule_date"]').value);
        formData.append('start_time', row.querySelector('input[name="start_time"]').value);
        formData.append('end_time', row.querySelector('input[name="end_time"]').value);

        axios.post('', formData)
            .then(response => {
                alert(response.data.message);
                if (response.data.success) {
                    row.querySelector('input[value="Add to Log"]').disabled = true; // Disable button
                }
            })
            .catch(error => {
                alert('Error adding log: ' + error.response.data.message);
            });
    }

    function deleteSchedule(scheduleId) {
        if (!confirm('Are you sure you want to delete this schedule?')) return;

        const formData = new FormData();
        formData.append('delete_schedule', true);
        formData.append('schedule_id', scheduleId);

        axios.post('', formData)
            .then(response => {
                alert(response.data.message);
                if (response.data.success) {
                    document.getElementById('row-' + scheduleId).remove(); // Remove the row dynamically
                }
            })
            .catch(error => {
                alert('Error deleting schedule: ' + error.response.data.message);
            });
    }
    </script>
</body>

</html>