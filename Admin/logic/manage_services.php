<?php
// manage_services.php

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
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'service_id'; // Default to 'service_id'
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc'; // Default to 'asc'
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC'; // Default sorting order

// Toggle sorting order (ASC or DESC) for the current column
$next_sort_order = $sort_order == 'ASC' ? 'desc' : 'asc';

// Fetch all services with sorting
$services_sql = "SELECT s.service_id, s.service_name, s.service_description, s.service_price, s.clearance_id, s.is_exclusive, c.clearance_name 
                 FROM Services s
                 JOIN Clearances c ON s.clearance_id = c.clearance_id 
                 ORDER BY $sort_column $sort_order";

$services_result = $conn->query($services_sql);

// Add error handling for the query
if (!$services_result) {
    die("Query failed: " . $conn->error);
}

//----------------------------------------------------------------//
// Handle adding a new service
if (isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $service_description = $_POST['service_description'];
    $service_price = $_POST['service_price'];
    $clearance_id = $_POST['clearance_id']; // Get clearance level from the form

    // Handle is_exclusive checkbox, default to 0 (false) if not set
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;

    // Validate clearance level (between 1 and 4)
    if ($clearance_id >= 1 && $clearance_id <= 4) {
        $add_sql = "INSERT INTO Services (service_name, service_description, service_price, clearance_id, is_exclusive) 
                    VALUES ('$service_name', '$service_description', $service_price, $clearance_id, $is_exclusive)";

        if ($conn->query($add_sql) === TRUE) {
            echo "New service added successfully.";
            header("Location: manage_services.php");
            exit();
        } else {
            echo "Error adding service: " . $conn->error;
        }
    } else {
        echo "Error: Clearance level must be between 1 and 4.";
    }
}

// Handle updating an existing service
if (isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $service_name = $_POST['service_name'];
    $service_price = $_POST['service_price'];
    $update_sql = "UPDATE Services SET service_name = '$service_name', service_price = $service_price WHERE service_id = $service_id";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "Service updated successfully.";
        header("Location: manage_services.php");
        exit();
    } else {
        echo "Error updating service: " . $conn->error;
    }
}

// Handle deleting a service
if (isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];
    $delete_sql = "DELETE FROM Services WHERE service_id = $service_id";
    
    if ($conn->query($delete_sql) === TRUE) {
        echo "Service deleted successfully.";
        header("Location: manage_services.php");
        exit();
    } else {
        echo "Error deleting service: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Services</title>
</head>

<body>
    <h1>Manage Services</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <!-- Add New Service -->
    <h2>Add New Service</h2>    
    <form action="add_service.php" method="POST">
        <label for="service_name">Service Name:</label>
        <input type="text" name="service_name" required><br>

        <label for="service_description">Service Description:</label>
        <textarea name="service_description"></textarea><br>

        <label for="service_price">Service Price:</label>
        <input type="number" step="0.01" name="service_price" required><br>

        <label for="clearance_id">Clearance Level:</label>
        <select name="clearance_id" required>
            <option value="1">Basic Service</option>
            <option value="2">Advanced Service</option>
            <option value="3">Specialist Service</option>
            <option value="4">Expert Service</option>
        </select><br>

        <label for="is_exclusive">Exclusive Service:</label>
        <input type="checkbox" name="is_exclusive" value="1"><br>

        <input type="submit" name="add_service" value="Add Service">
    </form>

    <!-- List of Existing Services -->
    <h2>Existing Services</h2>
    <?php if ($services_result->num_rows > 0): ?>
    <table border="1">
        <tr>
            <th><a href="?sort=service_id&order=<?php echo $next_sort_order; ?>">ID</a></th>
            <th><a href="?sort=service_name&order=<?php echo $next_sort_order; ?>">Service Name</a></th>
            <th><a href="?sort=service_price&order=<?php echo $next_sort_order; ?>">Service Price</a></th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $services_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['service_id']; ?></td>
            <td><?php echo $row['service_name']; ?></td>
            <td><?php echo $row['service_price']; ?></td>
            <td>
                <!-- Update Service -->
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                    <input type="text" name="service_name" value="<?php echo $row['service_name']; ?>" required>
                    <input type="number" name="service_price" value="<?php echo $row['service_price']; ?>" step="1.00" required>
                    <input type="submit" name="update_service" value="Update">
                </form>
                <!-- Delete Service -->
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                    <input type="submit" name="delete_service" value="Delete" onclick="return confirm('Are you sure you want to delete this service?');">
                </form>
                <!-- Edit Service Details -->
                <form method="GET" action="edit_service.php" style="display:inline;">
                    <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                    <input type="submit" value="Edit" class="button">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No services found.</p>
    <?php endif; ?>

</body>

</html>
