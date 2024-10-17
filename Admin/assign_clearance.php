<?php
// assign_clearance.php

// Connect to the database
$conn = new mysqli("localhost", "root", "", "tire_service");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission to assign clearance level
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $technician_id = $_POST['technician_id'];
    $clearance_level = $_POST['clearance_level'];

    // Update the technician's clearance level
    $sql = "UPDATE Technicians SET clearance_level = '$clearance_level' WHERE technician_id = $technician_id";

    if ($conn->query($sql) === TRUE) {
        echo "Clearance level assigned successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch technicians without clearance level
$tech_sql = "SELECT technician_id, first_name, last_name FROM Technicians WHERE clearance_level IS NULL";
$tech_result = $conn->query($tech_sql);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Clearance Level</title>
</head>
<body>

<h2>Assign Clearance Level to Technicians</h2>

<form action="assign_clearance.php" method="POST">
    <label for="technician">Select Technician:</label>
    <select name="technician_id" required>
        <?php while ($row = $tech_result->fetch_assoc()): ?>
            <option value="<?php echo $row['technician_id']; ?>">
                <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="clearance_level">Clearance Level:</label>
    <select name="clearance_level" required>
        <option value="1">Basic Service</option>
        <option value="2">Advanced Service</option>
        <option value="3">Specialist Service</option>
        <option value="4">Expert Service</option>
    </select><br><br>

    <input type="submit" value="Assign Clearance">
</form>

</body>
</html>
