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

// Handle sorting logic
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'service_id'; // Default to 'service_id'
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC'; // Default sorting order

// Toggle sorting order (ASC or DESC) for the next click
$next_sort_order = $sort_order == 'ASC' ? 'desc' : 'asc';

// Fetch all services with sorting
$services_sql = "SELECT s.service_id, s.service_name, s.service_description, s.service_price, 
                        s.clearance_id, s.is_exclusive, c.clearance_name 
                 FROM Services s
                 JOIN Clearances c ON s.clearance_id = c.clearance_id 
                 ORDER BY $sort_column $sort_order";

$services_result = $conn->query($services_sql);

if (!$services_result) {
    die("Query failed: " . $conn->error);
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Services</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Manage Services</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <div class="container">
        <!-- Left Section: Existing Services Table -->
        <div class="table-section">
            <h2>Existing Services</h2>
            <?php if ($services_result->num_rows > 0): ?>
            <table border="1">
                <tr>
                    <th class="sorted-asc"><a href="?sort=service_id&order=<?php echo $next_sort_order; ?>">ID</a></th>
                    <th><a href="?sort=service_name&order=<?php echo $next_sort_order; ?>">Service Name</a></th>
                    <th><a href="?sort=service_price&order=<?php echo $next_sort_order; ?>">Service Price</a></th>
                    <th><a href="?sort=clearance_id&order=<?php echo $next_sort_order; ?>">Clearance</a></th> <!-- Make clearance sortable -->
                    <th>Service Description</th> <!-- Non-sortable column for service description -->
                    <th>Actions</th>
                </tr>
                <?php while ($row = $services_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['service_id']; ?></td>
                    <td><?php echo $row['service_name']; ?></td>
                    <td><?php echo $row['service_price']; ?></td>
                    <td><?php echo $row['clearance_name']; ?></td> <!-- Display clearance name instead of clearance_id -->
                    <td><?php echo $row['service_description']; ?></td> <!-- Display service description here -->
                    <td>
                        <!-- Link to Edit Service Page -->
                        <form method="GET" action="edit_service.php" style="display:inline;">
                            <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                            <input type="submit" value="Edit" class="button">
                        </form>
                        <!-- Delete Service -->
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="service_id" value="<?php echo $row['service_id']; ?>">
                            <input type="submit" name="delete_service" value="Delete" class="delete-button" onclick="return confirm('Are you sure you want to delete this service?');">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No services found.</p>
            <?php endif; ?>
        </div>

        <!-- Right Section: Add New Service Form -->
        <div class="form-section">
            <h2>Add New Service</h2>    
            <form action="add_service.php" method="POST">
                <label for="service_name">Service Name:</label>
                <input type="text" name="service_name" required>

                <label for="service_description">Service Description:</label>
                <textarea name="service_description"></textarea>

                <label for="service_price">Service Price:</label>
                <input type="number" step="0.01" name="service_price" required>

                <label for="clearance_id">Clearance Level:</label>
                <select name="clearance_id" required>
                    <option value="1">Basic Service</option>
                    <option value="2">Advanced Service</option>
                    <option value="3">Specialist Service</option>
                    <option value="4">Expert Service</option>
                </select>

                <label for="is_exclusive">Exclusive Service:</label>
                <input type="checkbox" name="is_exclusive" value="1">

                <input type="submit" name="add_service" value="Add Service">
            </form>
        </div>
    </div>

</body>

</html>
