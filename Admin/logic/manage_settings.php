<?php
// manage_settings.php

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

// Fetch the current admin's data
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle form submission to update the admin's data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Check if a new profile picture is uploaded
    if (isset($_FILES['admin_Img']) && $_FILES['admin_Img']['error'] === UPLOAD_ERR_OK) {
        // Get the uploaded file's contents as binary data
        $file_tmp = $_FILES['admin_Img']['tmp_name'];
        $img_data = file_get_contents($file_tmp);

        // Prepare and execute the update query with the image
        $update_sql = "UPDATE Admins SET first_name = ?, last_name = ?, email = ?, phone_number = ?, admin_Img = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssbsi", $first_name, $last_name, $email, $phone_number, $img_data, $admin_id);
        $stmt->send_long_data(4, $img_data);

    } else {
        // If no new image is uploaded, update other details only
        $update_sql = "UPDATE Admins SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone_number, $admin_id);
    }

    // Execute the statement and check for errors
    if ($stmt->execute()) {
        $_SESSION['admin_name'] = $first_name . ' ' . $last_name; // Update session with new name
        echo "Information updated successfully!";
    } else {
        echo "Error updating information: " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Settings</title>
    <link rel="stylesheet" href="../admin_styles.css">
</head>

<body>
    <div class="form-container">
        <h2>Manage Settings</h2>

        <form method="GET" action="../admin_dashboard.php" style="display:inline;">
            <input type="submit" value="Dashboard" class="button">
        </form>

        <div class="hex-frame">
            <img src="data:image/png;base64,<?php echo base64_encode($admin['admin_Img']); ?>" class="profile-preview"
                alt="Profile Picture">
        </div>

        <form method="POST" enctype="multipart/form-data">
            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>"
                required><br>

            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>"
                required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required><br>

            <label for="phone_number">Phone Number:</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($admin['phone_number']); ?>"
                required><br>

            <label for="admin_Img">Profile Picture:</label>
            <input type="file" name="admin_Img" accept="image/*"><br><?php if (!empty($admin['admin_Img'])): ?>
            <?php endif; ?>

            <input type="submit" value="Update Information">
        </form>
    </div>
</body>

</html>