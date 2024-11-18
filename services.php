<!-- services.php -->
<?php 
$pageTitle = "Services - Panther Tire Service"; 
include 'header.php'; 

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all services
$services_sql = "SELECT service_name, service_price, service_description, svg_icon FROM Services";
$services_result = $conn->query($services_sql);
$conn->close();
?>

<main>
    <div class="services-container">
        <h1>Our Services</h1>
        <div class="services-grid">
            <?php while ($row = $services_result->fetch_assoc()): ?>
            <div class="service-card">
                <!-- Icon -->
                <div class="service-icon">
                    <img src="data:image/svg+xml;base64,<?php echo base64_encode($row['svg_icon']); ?>"
                        alt="<?php echo htmlspecialchars($row['service_name']); ?>">
                </div>

                <!-- Info -->
                <div class="service-info">
                    <h3 class="service-name"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                </div>

                <!-- Desc -->
                <div class="service-desc">
                    <p class="service-price">$<?php echo number_format($row['service_price'], 2); ?></p>
                    <p class="service-description"><?php echo htmlspecialchars($row['service_description']); ?></p>
                </div>
            </div>

            <?php endwhile; ?>
        </div>

    </div>
    <link rel="stylesheet" type="text/css" href="styles.css">
</main>

<?php 
include 'footer.php'; 
?>