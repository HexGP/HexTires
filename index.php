<!-- index.php -->
<?php 
$pageTitle = "Home - Panther Tire Service"; 
include 'header.php'; 
?>

<!DOCTYPE html>
<html>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <div class="container">

        <!-- Welcome Section -->
        <div class="row featurette mt-5">
            <div class="col-md-6 text-left">
                <p class="info-text">
                    <img src="images/Icon.png" alt="Check Icon" width="20" height="20">
                    Easy Appointments, No Hidden Fees.
                </p>
                <h3>Welcome to Panther Tire Service</h3>
                <h3>Your <span class="highlight">Premier One-Stop</span> Tire Solution</h3>
                <p class="intro-text">
                    "At Panther Tire Service, our mission is to offer top-quality tire solutions that keep you safe and
                    confident on the road. We are dedicated to providing personalized, reliable, and innovative services
                    tailored to meet the unique needs of every driver."
                </p>
            </div>
            <div class="col-md-6 text-center">
                <img src="images/tire_top.png" alt="Tire Image" class="img-fluid rounded" width="515" height="400">
            </div>
        </div>

        <!-- Login Section -->
        <section id="logins" class="login-section">
            <h2 class="section-title mt-5">Logins</h2>
            <div class="login-container">
                <div class="login-box">
                    <img src="images/client.svg" alt="Client Login">
                    <div class="login-details">
                        <h6>Client Login</h6>
                        <a href="Client/client_login.php" target="_blank">
                            <button class="btn-custom">Client Login</button>
                        </a>
                    </div>
                </div>
                <div class="login-box">
                    <img src="images/tech.svg" alt="Technician Login">
                    <div class="login-details">
                        <h6>Technician Login</h6>
                        <a href="Technician/tech_login.php" target="_blank">
                            <button class="btn-custom">Technician Login</button>
                        </a>
                    </div>
                </div>
                <div class="login-box">
                    <img src="images/admin.svg" alt="Admin Login">
                    <div class="login-details">
                        <h6>Admin Login</h6>
                        <a href="Admin/admin_login.php" target="_blank">
                            <button class="btn-custom">Admin Login</button>
                        </a>
                    </div>
                </div>
            </div>
        </section>


        <!-- Service Section -->
        <h2 class="section-title mt-5">Our <span class="highlight">Service</span></h2>
        <div class="row featurette service-section mt-3">
            <div class="col-md-6">
                <p>Discover our full range of professional tire services at Panther Tire Service, tailored to meet your
                    vehicle's unique requirements and ensure you stay safe and secure on the road.</p>
            </div>
            <div class="col-md-6 text-right">
                <div class="btn-group" role="group">
                </div>
            </div>
        </div>

        <!-- Service Offerings -->
        <div class="row featurette mt-5">
            <div class="col-md-4 text-center service-item">
                <img src="images/geer.png" alt="Gear Icon" class="service-icon">
                <h3>Tire Installation</h3>
                <p>Get reliable and professional tire installation services that ensure your vehicle's safety and
                    performance.</p>
            </div>
            <div class="col-md-4 text-center service-item">
                <img src="images/screaw.png" alt="Screw Icon" class="service-icon">
                <h3>Tire Repair</h3>
                <p>Restore your tires to optimal condition with our comprehensive repair services, extending the life of
                    your tires.</p>
            </div>
            <div class="col-md-4 text-center service-item">
                <img src="images/focus.png" alt="Focus Icon" class="service-icon">
                <h3>Tire Rotation and Balancing</h3>
                <p>Maintain even tire wear and improve vehicle handling with our rotation and balancing services for a
                    smooth ride.</p>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section mt-5">
            <h2>Frequently Asked <span class="highlight">Questions</span></h2>
            <div class="faq-item">
                <h5>How do I book an appointment with Panther Tire Service?</h5>
                <p>Booking an appointment is easy; visit our website, select a service, and choose an available time
                    slot.</p>
            </div>
            <div class="faq-item">
                <h5>What services does Panther Tire Service offer?</h5>
                <p>We offer a wide range of tire services, including replacements, balancing, alignments, and seasonal
                    tire changeovers.</p>
            </div>
            <div class="faq-item">
                <h5>Are your services safe and reliable?</h5>
                <p>Absolutely, we ensure that all our services meet industry standards and our staff are highly trained
                    professionals.</p>
            </div>
            <!-- <a href="#" class="btn btn-outline-custom mt-3">Load More FAQs</a> -->
        </div>

        <!-- Testimonials Section -->
        <div class="testimonial-section mt-5">
            <h2>Our <span class="highlight">Testimonials</span></h2>
            <div class="testimonial-card">
                <p>"Coming to Panther Tire Service was the best decision for my vehicle! They were professional and
                    quick." - Jane D.</p>
            </div>
            <div class="testimonial-card">
                <p>"Panther Tire Service has provided our business with top-notch service for our entire fleet.
                    Expertise unmatched." - John P.</p>
            </div>
        </div>

        <!-- Call to Action Section -->
        <div class="cta-section mt-5">
            <h3>Get rolling with Panther Tire Service today!</h3>
            <p>Experience the difference in quality, service, and value for all your tire needs.</p>
            <button onclick="window.location.href='Client/client_login.php'" class="btn btn-custom">Book
                Appointment</button>
        </div>
    </div>

</html>
<?php include 'footer.php'; ?>