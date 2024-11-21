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

// Default sorting for Payments
$payments_sort_column = $_GET['payments_sort'] ?? 'payment_date';
$payments_sort_order = $_GET['payments_order'] ?? 'desc';
$payments_next_sort_order = ($payments_sort_order === 'asc') ? 'desc' : 'asc';

$payments_allowed_columns = ['payment_id', 'appointment_id', 'client_first_name', 'tech_first_name', 'amount_paid', 'tip_amount', 'payment_date', 'payment_status'];
if (!in_array($payments_sort_column, $payments_allowed_columns)) {
    $payments_sort_column = 'payment_date';
}

// Fetch Payments Made data
$payments_sql = "
    SELECT 
        p.payment_id, 
        p.appointment_id, 
        c.first_name AS client_first_name, 
        c.last_name AS client_last_name, 
        t.first_name AS tech_first_name, 
        t.last_name AS tech_last_name, 
        p.amount_paid, 
        p.tip_amount, 
        p.payment_date, 
        p.payment_status
    FROM Payments p
    JOIN Clients c ON p.client_id = c.client_id
    LEFT JOIN Technicians t ON p.technician_id = t.technician_id
    ORDER BY $payments_sort_column $payments_sort_order";

$payments_result = $conn->query($payments_sql);
if (!$payments_result) {
    die("Error in Payments SQL Query: " . $conn->error);
}


// Default sorting for Technician Salary
$salary_sort_column = $_GET['salary_sort'] ?? 'total_earnings';
$salary_sort_order = $_GET['salary_order'] ?? 'desc';
$salary_next_sort_order = ($salary_sort_order === 'asc') ? 'desc' : 'asc';

$salary_allowed_columns = ['technician_id', 'first_name', 'total_earnings', 'appointments_handled'];
if (!in_array($salary_sort_column, $salary_allowed_columns)) {
    $salary_sort_column = 'total_earnings';
}

// Fetch Technician Salary data
// $salary_sql = "
//     SELECT 
//         t.technician_id, 
//         t.first_name, 
//         t.last_name, 
//         COALESCE(SUM(p.amount_paid), 0) AS total_earnings, 
//         COUNT(p.payment_id) AS appointments_handled
//     FROM Technicians t
//     LEFT JOIN Payments p ON t.technician_id = p.technician_id
//     GROUP BY t.technician_id
//     ORDER BY $salary_sort_column $salary_sort_order";

// $salary_result = $conn->query($salary_sql);

$salary_sql = "
    SELECT 
        t.technician_id, 
        t.first_name, 
        t.last_name, 
        COALESCE(SUM(p.amount_paid), 0) AS total_earnings, 
        COUNT(p.payment_id) AS appointments_handled,
        COALESCE(tv.total_bonus, 0) AS total_bonus,
        COALESCE(tv.total_tips, 0) AS total_tips
    FROM Technicians t
    LEFT JOIN Payments p ON t.technician_id = p.technician_id
    LEFT JOIN techbonusview_yearly tv ON t.technician_id = tv.technician_id
    GROUP BY t.technician_id
    ORDER BY $salary_sort_column $salary_sort_order";

$salary_result = $conn->query($salary_sql);


if (!$salary_result) {
    die("Error in Technician Salary SQL Query: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments</title>
    <link rel="stylesheet" type="text/css" href="manage_styles.css">
</head>

<body>
    <h1>Manage Payments</h1>

    <!-- Back button to the admin dashboard -->
    <form method="GET" action="../admin_dashboard.php" style="display:inline;">
        <input type="submit" value="Dashboard" class="button">
    </form>

    <div class="container">
        <!-- Payments List Table -->
        <div class="table-section">
            <h2>Payments Made</h2>
            <?php if ($payments_result->num_rows > 0): ?>
            <table border="1">
                <tr>
                    <th><a href="?payments_sort=payment_id&payments_order=<?php echo $payments_next_sort_order; ?>">Payment
                            ID</a></th>
                    <th><a href="?payments_sort=appointment_id&payments_order=<?php echo $payments_next_sort_order; ?>">Appointment
                            ID</a></th>
                    <th><a
                            href="?payments_sort=client_first_name&payments_order=<?php echo $payments_next_sort_order; ?>">Client</a>
                    </th>
                    <th><a
                            href="?payments_sort=tech_first_name&payments_order=<?php echo $payments_next_sort_order; ?>">Technician</a>
                    </th>
                    <th><a href="?payments_sort=amount_paid&payments_order=<?php echo $payments_next_sort_order; ?>">Amount
                            Paid</a></th>
                    <th><a
                            href="?payments_sort=tip_amount&payments_order=<?php echo $payments_next_sort_order; ?>">Tip</a>
                    </th>
                    <th><a
                            href="?payments_sort=payment_date&payments_order=<?php echo $payments_next_sort_order; ?>">Date</a>
                    </th>
                    <th><a
                            href="?payments_sort=payment_status&payments_order=<?php echo $payments_next_sort_order; ?>">Status</a>
                    </th>
                </tr>
                <?php while ($row = $payments_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['payment_id']; ?></td>
                    <td><?php echo $row['appointment_id']; ?></td>
                    <td><?php echo $row['client_first_name'] . ' ' . $row['client_last_name']; ?></td>
                    <td><?php echo $row['tech_first_name'] . ' ' . $row['tech_last_name']; ?></td>
                    <td>$<?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td>$<?php echo number_format($row['tip_amount'], 2); ?></td>
                    <td><?php echo date("m/d/Y", strtotime($row['payment_date'])); ?></td>
                    <td><?php echo ucfirst($row['payment_status']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No payments found.</p>
            <?php endif; ?>
        </div>

        <!-- Service Earnings Table -->
        <div class="table-section">
            <h2>Service Earnings Generated by Technicians</h2>
            <?php if ($salary_result->num_rows > 0): ?>
            <table border="1">
                <tr>
                    <th><a href="?salary_sort=technician_id&salary_order=<?php echo $salary_next_sort_order; ?>">Technician
                            ID</a></th>
                    <th><a href="?salary_sort=appointments_handled&salary_order=<?php echo $salary_next_sort_order; ?>">Appointments
                            Handled</a></th>
                    <th><a href="?salary_sort=first_name&salary_order=<?php echo $salary_next_sort_order; ?>">Name</a>
                    </th>
                    <th><a href="?salary_sort=total_earnings&salary_order=<?php echo $salary_next_sort_order; ?>">Total
                            Earnings</a></th>

                    <th><a href="?salary_sort=total_bonus&salary_order=<?php echo $salary_next_sort_order; ?>">Total
                            Bonus</a></th>
                    <th><a href="?salary_sort=total_tips&salary_order=<?php echo $salary_next_sort_order; ?>">Amount
                            Tipped</a></th>
                </tr>
                <?php while ($row = $salary_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['technician_id']; ?></td>
                    <td><?php echo $row['appointments_handled']; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td>$<?php echo number_format($row['total_earnings'], 2); ?></td>
                    <td>$<?php echo number_format($row['total_bonus'], 2); ?></td>
                    <td>$<?php echo number_format($row['total_tips'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No technician salary data available.</p>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>