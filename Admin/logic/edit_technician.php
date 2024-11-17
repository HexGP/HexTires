<?php
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

// Get the technician ID from the URL
$tech_id = $_GET['technician_id'];

// Fetch technician details for the given ID
$tech_sql = "SELECT * FROM Technicians WHERE technician_id = $tech_id";
$tech_result = $conn->query($tech_sql);

if ($tech_result->num_rows > 0) {
    $tech = $tech_result->fetch_assoc();
} else {
    echo "Technician not found.";
    exit();
}

// Handle form submission to update technician
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $clearance_id = $_POST['clearance_id'];
    $salary = $_POST['salary'];
    $is_inhouse = isset($_POST['is_inhouse']) ? 1 : 0;

    // Update the technician's details
    $update_sql = "UPDATE Technicians 
                   SET first_name = '$first_name', last_name = '$last_name', email = '$email', 
                       phone_number = '$phone_number', clearance_id = '$clearance_id', salary = '$salary',
                       is_inhouse = '$is_inhouse' 
                   WHERE technician_id = $tech_id";

    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Technician updated successfully!'); window.location.href='manage_technicians.php';</script>";
    } else {
        echo "Error updating technician: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Technician</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Edit Technician</h1>

    <!-- Back to Manage Technicians page -->
    <form method="GET" action="manage_technicians.php" style="display:inline;">
        <input type="submit" value="Back to Technicians">
    </form>

    <form action="edit_technician.php?technician_id=<?php echo $tech_id; ?>" method="POST" class="compact-form">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($tech['first_name']); ?>" required>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($tech['last_name']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($tech['email']); ?>" required>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($tech['phone_number']); ?>" required>
        
        <label for="salary">Salary:</label>
        <input type="number" name="salary" value="<?php echo htmlspecialchars($tech['salary']); ?>" required>

        <label for="clearance_id">Clearance:</label>
        <select name="clearance_id" required>
            <option value="1" <?php echo $tech['clearance_id'] == 1 ? 'selected' : ''; ?>>Basic</option>
            <option value="2" <?php echo $tech['clearance_id'] == 2 ? 'selected' : ''; ?>>Advanced</option>
            <option value="3" <?php echo $tech['clearance_id'] == 3 ? 'selected' : ''; ?>>Specialist</option>
            <option value="4" <?php echo $tech['clearance_id'] == 4 ? 'selected' : ''; ?>>Expert</option>
        </select>

        <label for="is_inhouse" class="inline-label">In-House?</label>
        <input type="checkbox" name="is_inhouse" value="1" <?php echo $tech['is_inhouse'] ? 'checked' : ''; ?>>

        <input type="submit" value="Update Technician">
    </form>
</body>

</html>
