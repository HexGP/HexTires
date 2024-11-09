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

// Get service details based on the service_id passed in GET
$service_id = $_GET['service_id'] ?? null;
$service = null;

if ($service_id) {
    $service_sql = "SELECT service_id, service_name, service_description, service_price, clearance_id, is_exclusive 
                    FROM Services 
                    WHERE service_id = $service_id";
    $result = $conn->query($service_sql);

    if ($result && $result->num_rows > 0) {
        $service = $result->fetch_assoc();
    } else {
        echo "Service not found.";
    }
}

// Handle form submission for updating the service
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_name = $_POST['service_name'];
    $service_description = $_POST['service_description'];
    $service_price = $_POST['service_price'];
    $clearance_id = $_POST['clearance_id'];
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;

    $update_sql = "UPDATE Services SET 
                    service_name = '$service_name', 
                    service_description = '$service_description', 
                    service_price = $service_price, 
                    clearance_id = $clearance_id, 
                    is_exclusive = $is_exclusive 
                   WHERE service_id = $service_id";

    if ($conn->query($update_sql) === TRUE) {
        echo "Service updated successfully.";
        header("Location: manage_services.php");
        exit();
    } else {
        echo "Error updating service: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Service</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Edit Service</h1>

    <!-- Back to Manage Services page -->
    <form method="GET" action="manage_services.php" style="display:inline;">
        <input type="submit" value="Back to Manage Services" class="button">
    </form>

    <?php if ($service): ?>
    <form action="edit_service.php?service_id=<?php echo $service_id; ?>" method="POST" class="compact-form">
        <label for="service_name">Name:</label>
        <input type="text" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>" required>

        <label for="service_description">Description:</label>
        <textarea name="service_description" rows="2"><?php echo htmlspecialchars($service['service_description']); ?></textarea>

        <label for="service_price">Price:</label>
        <input type="number" step="0.01" name="service_price" value="<?php echo $service['service_price']; ?>" required>

        <label for="clearance_id">Clearance:</label>
        <select name="clearance_id" required>
            <option value="1" <?php if ($service['clearance_id'] == 1) echo 'selected'; ?>>Basic</option>
            <option value="2" <?php if ($service['clearance_id'] == 2) echo 'selected'; ?>>Advanced</option>
            <option value="3" <?php if ($service['clearance_id'] == 3) echo 'selected'; ?>>Specialist</option>
            <option value="4" <?php if ($service['clearance_id'] == 4) echo 'selected'; ?>>Expert</option>
        </select>

        <label for="is_exclusive" class="inline-label">Exclusive:</label>
        <input type="checkbox" name="is_exclusive" value="1" <?php if ($service['is_exclusive']) echo 'checked'; ?>>

        <input type="submit" value="Save Changes">
    </form>
    <?php else: ?>
    <p>Service details not available.</p>
    <?php endif; ?>

</body>

</html>
