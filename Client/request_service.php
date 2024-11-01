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

// Fetch available services
$services_sql = "SELECT service_id, service_name, service_price FROM Services";
$services_result = $conn->query($services_sql);

// When the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_SESSION['client_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // Insert the new appointment with status 'requested'
    $insert_sql = "INSERT INTO Appointments (client_id, service_id, appointment_date, appointment_time, appointment_status)
                   VALUES ('$client_id', '$service_id', '$appointment_date', '$appointment_time', 'requested')";

    if ($conn->query($insert_sql) === TRUE) {
        echo "Service request submitted successfully!";
        // Optionally redirect to the dashboard or show a confirmation page
        header("Location: client_dashboard.php");
        exit();
    } else {
        echo "Error: " . $insert_sql . "<br>" . $conn->error;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request New Service</title>
</head>
<body>

<h1>Request a New Service</h1>

<form method="POST" action="">
    <label for="service_id">Select Service:</label>
    <select name="service_id" required>
        <?php while ($row = $services_result->fetch_assoc()): ?>
            <option value="<?php echo $row['service_id']; ?>">
                <?php echo $row['service_name'] . " - $" . $row['service_price']; ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="appointment_date">Preferred Date:</label>
    <input type="date" name="appointment_date" required><br><br>

    <label for="appointment_time">Preferred Time:</label>
    <input type="time" name="appointment_time" required><br><br>

    <input type="submit" value="Request Service">
</form>

<a href="client_dashboard.php">Go back to dashboard</a>

</body>
</html>
