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

// Handle sorting logic
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'technician_id'; // Default to 'technician_id'
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc'; // Default to 'asc'
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC'; // Default sorting order

// Toggle sorting order (ASC or DESC) for the current column
$next_sort_order = $sort_order == 'ASC' ? 'desc' : 'asc';

// Fetch all technicians with sorting
$tech_sql = "SELECT t.technician_id, t.first_name, t.last_name, t.email, t.phone_number, t.is_inhouse, c.clearance_name, t.salary 
             FROM Technicians t 
             JOIN Clearances c ON t.clearance_id = c.clearance_id 
             ORDER BY $sort_column $sort_order";

$tech_result = $conn->query($tech_sql);

// To format the phone number as a US number
function formatPhoneNumber($phoneNumber) {
    // Remove any non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Check if the number has 10 digits
    if (strlen($cleaned) == 10) {
        return '(' . substr($cleaned, 0, 3) . ') ' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
    }

    // If it's not 10 digits, return the original
    return $phoneNumber;
}

// Delete a technician
if (isset($_POST['delete_technician'])) {
    $tech_id = $_POST['technician_id'];
    $delete_sql = "DELETE FROM Technicians WHERE technician_id = $tech_id";
    if ($conn->query($delete_sql) === TRUE) {
        echo "Technician deleted successfully!";
        header("Location: manage_technicians.php");
        exit();
    } else {
        echo "Error deleting technician: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Technicians</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Manage Technicians</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <div class="container">
        <!-- Left Section: Technician List Table -->
        <div class="table-section">
            <h2>Technician List</h2>
            <?php if ($tech_result->num_rows > 0): ?>
            <table border="1">
                <tr>
                    <th><a href="?sort=technician_id&order=<?php echo $next_sort_order; ?>">ID</a></th>
                    <th><a href="?sort=first_name&order=<?php echo $next_sort_order; ?>">Name</a></th>
                    <th><a href="?sort=email&order=<?php echo $next_sort_order; ?>">Email</a></th>
                    <th><a href="?sort=phone_number&order=<?php echo $next_sort_order; ?>">Phone Number</a></th>
                    <th><a href="?sort=is_inhouse&order=<?php echo $next_sort_order; ?>">Employee/Contractor</a></th>
                    <th><a href="?sort=clearance_name&order=<?php echo $next_sort_order; ?>">Clearance</a></th>
                    <th><a href="?sort=salary&order=<?php echo $next_sort_order; ?>">Salary</a></th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $tech_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['technician_id']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo formatPhoneNumber($row['phone_number']); ?></td>

                    <td><?php echo $row['is_inhouse'] ? 'Employee' : 'Contractor'; ?></td>
                    <td><?php echo $row['clearance_name']; ?></td>
                    <td>$<?php echo number_format($row['salary'], 2); ?></td>
                    <td>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="technician_id" value="<?php echo $row['technician_id']; ?>">
                            <input type="submit" name="delete_technician" value="Delete" class="delete-button"
                                onclick="return confirm('Are you sure you want to delete this technician?');">
                        </form>

                        <form method="GET" action="edit_technician.php" style="display:inline;">
                            <input type="hidden" name="technician_id" value="<?php echo $row['technician_id']; ?>">
                            <input type="submit" value="Edit Tech Details" class="button">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No technicians found.</p>
            <?php endif; ?>
        </div>

        <!-- Right Section: Add New Technician Form -->
        <div class="form-section">
            <h2>Add New Technician</h2>
            <form action="add_technician.php" method="POST">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" required>

                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="phone_number">Phone Number:</label>
                <input type="tel" name="phone_number" required>

                <label for="clearance_id">Clearance Level:</label>
                <select name="clearance_id" required>
                    <option value="1">Basic Service</option>
                    <option value="2">Advanced Service</option>
                    <option value="3">Specialist Service</option>
                    <option value="4">Expert Service</option>
                </select>

                <label for="salary">Salary:</label>
                <input type="number" name="salary" required>

                <input type="submit" value="Add Technician">
            </form>
        </div>
    </div>

</body>

</html>