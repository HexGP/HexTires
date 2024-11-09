<?php
// manage_clients.php

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
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'client_id'; // Default to 'client_id'
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc'; // Default to 'asc'
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC'; // Default sorting order

// Toggle sorting order (ASC or DESC) for the current column
$next_sort_order = $sort_order == 'ASC' ? 'desc' : 'asc';

// Fetch all clients with sorting
$client_sql = "SELECT c.client_id, c.first_name, c.last_name, c.email, c.phone_number
             FROM Clients c
             ORDER BY $sort_column $sort_order";

$client_result = $conn->query($client_sql);

// Delete a technician
if (isset($_POST['delete_client'])) {
    $client_id = $_POST['client_id'];
    $delete_sql = "DELETE FROM Clients WHERE client_id = $client_id";
    if ($conn->query($delete_sql) === TRUE) {
        echo "Client deleted successfully!";
        header("Location: manage_clients.php");
        exit();
    } else {
        echo "Error deleting client: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Clients</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Manage Clients</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <div class="container">
        <!-- Left Section: Client List Table Only -->
        <div class="table-section">
            <h2>Client List</h2>
            <?php if ($client_result->num_rows > 0): ?>
            <table border="1">
                <tr>
                    <th><a href="?sort=client_id&order=<?php echo $next_sort_order; ?>">ID</a></th>
                    <th><a href="?sort=first_name&order=<?php echo $next_sort_order; ?>">Name</a></th>
                    <th><a href="?sort=email&order=<?php echo $next_sort_order; ?>">Email</a></th>
                    <th><a href="?sort=phone_number&order=<?php echo $next_sort_order; ?>">Phone Number</a></th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $client_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['client_id']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['phone_number']; ?></td>
                    <td>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="client_id" value="<?php echo $row['client_id']; ?>">
                            <input type="submit" name="delete_client" value="Delete">
                        </form>
                        <form method="GET" action="edit_client.php" style="display:inline;">
                            <input type="hidden" name="client_id" value="<?php echo $row['client_id']; ?>">
                            <input type="submit" value="Edit Client Details" class="button">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No clients found.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>
