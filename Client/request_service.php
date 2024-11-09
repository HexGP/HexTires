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
    if (isset($_POST['service_id']) && isset($_POST['appointment_date']) && isset($_POST['appointment_time'])) {
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
    } else {
        echo "Please fill in all fields.";
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Request New Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="client_styles.css">
</head>

<body>
    <h1>Schedule your appointment</h1>

    <!-- Back to dashboard Button -->
    <form action="client_dashboard.php" method="POST">
        <button type="submit" name="back">
            <i class="fa-solid fa-right-from-bracket"></i> Back to dashboard
        </button>
    </form>

    <!-- Main form to request service -->
    <div class="service-container">
        <div class="service-section">
            <form method="POST" action="">
                <!-- Car Type Section -->
                <div class="form-row">
                    <label for="">Car Type Section:</label>
                    What is the year of your vehicle?
                </div>

                <!-- Service Section -->
                <div class="form-row">
                    <label for="service_id">Select Service:</label>
                    <select name="service_id" required>
                        <?php while ($row = $services_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['service_id']; ?>">
                            <?php echo $row['service_name'] . " - $" . $row['service_price']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select><br><br>
                </div>

                <!-- Date / Time -->
                <div class="form-row">
                    <label for="appointment_date">Preferred Date:</label>
                    <input type="date" name="appointment_date" required>
                </div>

                <!-- Address Section -->
                <div class="form-row">
                    <label for="appointment_time">Address</label>
                </div>

                <!-- Service Section -->
                <div class="form-row">
                    <label for="appointment_time">Car Type:</label>
                </div>
            </form>

            <div class="service-confirm">
            <button type="submit" name="request" value="Request Service">
                Confirm Service
                <i class="fa-solid fa-calendar-check"></i>
            </button>
        </div>
        </div>

        
    </div>

</body>

</html>