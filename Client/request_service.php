<?php
session_start();

// Check if the client is logged in, if not redirect to the login page
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "hextire");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available services
$services_sql = "SELECT service_id, service_name, service_description, service_price, svg_icon FROM Services";
$services_result = $conn->query($services_sql);

// Fetch car types
$car_types_sql = "SELECT car_type_id, car_type_code, car_desc FROM CarTypes";
$car_types_result = $conn->query($car_types_sql);

// Fetch client information
$client_id = $_SESSION['client_id'];
$client_sql = "SELECT first_name, last_name, email, phone_number FROM Clients WHERE client_id = $client_id";
$client_result = $conn->query($client_sql);

if ($client_result && $client_result->num_rows > 0) {
    $client_data = $client_result->fetch_assoc();
} else {
    $client_data = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone_number' => ''];
}

// When the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_id']) && isset($_POST['appointment_date']) && isset($_POST['appointment_time'])) {
    $client_id = $_SESSION['client_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    // Define end time based on a one-hour duration
    $end_time = date("H:i:s", strtotime("+1 hour", strtotime($appointment_time)));

    // Call the stored procedure to handle both appointment insertion and technician assignment
    $stmt = $conn->prepare("CALL AssignTechnician(?, ?, ?, ?)");
    $stmt->bind_param("isss", $client_id, $service_id, $appointment_date, $appointment_time);


    if ($stmt->execute()) {
        // Close the statement
        $stmt->close();
        // Redirect to the client dashboard
        header("Location: client_dashboard.php");
        exit();
    } else {
        echo "Error calling stored procedure: " . $stmt->error;
    }
}

// Close the connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <title>Schedule Your Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="client_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body class="appointment">
    <h1>Schedule Your Appointment</h1>

    <div>
        <form action="client_dashboard.php" method="POST">
            <button type="submit" name="back" class="back-button">
                <i class="fa-solid fa-right-from-bracket"></i> Back to dashboard
            </button>
        </form>
    </div>

    <div class="appointment-container">
        <form method="POST" action="" class="service-form">
            <!-- Hidden fields to capture appointment details -->
            <!-- <input type="hidden" name="appointment_date" id="appointment_date">
            <input type="hidden" name="appointment_time" id="appointment_time"> -->

            <!-- Car Type Selection -->
            <div class="form-group car-type">
                <h2>Select a Car Type</h2>
                <div class="radio-list">
                    <?php while ($row = $car_types_result->fetch_assoc()): ?>
                    <label class="radio-option">
                        <input type="radio" name="car_type_id" value="<?php echo $row['car_type_id']; ?>" required>
                        <?php echo $row['car_desc']; ?>
                    </label>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Service Selection -->
            <div class="form-group service-select">
                <h2>Select Service</h2>
                <div class="radio-wrapper">
                    <?php while ($row = $services_result->fetch_assoc()): ?>
                    <label class="radio-option">
                        <input type="radio" name="service_id" value="<?php echo $row['service_id']; ?>"
                            data-name="<?php echo htmlspecialchars($row['service_name']); ?>"
                            data-description="<?php echo htmlspecialchars($row['service_description']); ?>"
                            data-price="<?php echo htmlspecialchars($row['service_price']); ?>" required>

                        <!-- SVG Icon -->
                        <img src="data:image/svg+xml;base64,<?php echo base64_encode($row['svg_icon']); ?>"
                            alt="<?php echo htmlspecialchars($row['service_name']); ?>" class="service-icon">

                        <!-- Service Name -->
                        <span class="service-name"><?php echo $row['service_name']; ?></span>
                    </label>
                    <?php endwhile; ?>
                </div>

                <div id="serviceDetails" class="service-details" style="display: none;">
                    <p id="serviceName"></p>
                    <p id="serviceDescription"></p>
                    <p id="servicePrice"></p>
                </div>
            </div>

            <!-- Date Selection -->
            <div class="form-group calendar">
                <h2>Select a Date</h2>
                <div id="inline-calendar"></div>
                <input type="hidden" name="appointment_date" id="appointment_date" required>
            </div>

            <!-- Time Slot Selection -->
            <div class="form-group time-slots">
                <h2>Select a Time</h2>
                <div id="time-slots"></div>
                <input type="hidden" name="appointment_time" id="appointment_time" required>
            </div>



            <!-- Confirm Button -->
            <div class="form-group confirm">
                <button type="submit" name="request" class="service-confirm">
                    Confirm Service <i class="fa-solid fa-calendar-check"></i>
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Flatpickr setup for inline calendar
        flatpickr("#inline-calendar", {
            inline: true,
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                document.getElementById("appointment_date").value = dateStr;
                document.getElementById("appointment_time").value = ""; // Clear previous time
                loadTimeSlots(dateStr);
            },
        });

        // Function to load time slots
        function loadTimeSlots(dateStr) {
            const timeSlotsContainer = document.getElementById("time-slots");
            timeSlotsContainer.innerHTML = ""; // Clear previous time slots

            const startTime = new Date(`1970-01-01T08:00:00`);
            const endTime = new Date(`1970-01-01T17:45:00`);
            while (startTime <= endTime) {
                const timeString = startTime.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const slotButton = document.createElement("button");
                slotButton.type = "button";
                slotButton.className = "time-slot";
                slotButton.textContent = timeString;
                slotButton.addEventListener("click", () => selectTimeSlot(timeString));
                timeSlotsContainer.appendChild(slotButton);
                startTime.setMinutes(startTime.getMinutes() + 15);
            }
        }

        // Function to handle time slot selection
        function selectTimeSlot(time) {
            document.querySelectorAll(".time-slot").forEach(slot => slot.classList.remove("selected"));
            const selectedSlot = Array.from(document.querySelectorAll(".time-slot")).find(slot => slot
                .textContent === time);
            if (selectedSlot) {
                selectedSlot.classList.add("selected");
                document.getElementById("appointment_time").value = time; // Set selected time
            }
        }

        // Ensure form cannot be submitted without a valid time for the selected date
        document.querySelector(".service-form").addEventListener("submit", function(e) {
            const date = document.getElementById("appointment_date").value;
            const time = document.getElementById("appointment_time").value;
            if (!date || !time) {
                e.preventDefault();
                alert("Please select both a date and time for your appointment.");
            }
        });

        // Highlight for service selection radio buttons
        const serviceRadioOptions = document.querySelectorAll(".service-select input[type='radio']");
        serviceRadioOptions.forEach(radio => {
            radio.addEventListener("change", function() {
                serviceRadioOptions.forEach(option => option.parentNode.classList.remove(
                    "selected"));
                if (radio.checked) {
                    radio.parentNode.classList.add("selected");
                }
                
                const name = radio.getAttribute("data-name");
                const description = radio.getAttribute("data-description");
                const price = radio.getAttribute("data-price");

                document.getElementById("serviceName").textContent = name;
                document.getElementById("serviceDescription").textContent = description;
                document.getElementById("servicePrice").textContent = `Price: $${price}`;
                document.getElementById("serviceDetails").style.display = "block";
            });
        });

        // Highlight for car type selection radio buttons
        const carTypeRadioOptions = document.querySelectorAll(".car-type input[type='radio']");
        carTypeRadioOptions.forEach(radio => {
            radio.addEventListener("change", function() {
                carTypeRadioOptions.forEach(option => option.parentNode.classList.remove(
                    "selected"));
                if (radio.checked) {
                    radio.parentNode.classList.add("selected");
                }
            });
        });
    });
    </script>
</body>

</html>