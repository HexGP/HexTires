<!-- header.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Default Title"; ?></title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>

<body>
    <?php
    // Get the current script name
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <header>
        <nav class="navbar">
            <a class="navbar-brand" href="index.php">
                <div class="brand-logo">
                    <img src="images/logo.png" alt="Panther Tire Service Logo">
                </div>
                <span class="brand-text">Panther Tire Service</span>
            </a>

            <ul class="navbar-nav">
                <li><a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>"
                        href="index.php">Home</a></li>
                <li><a class="nav-link <?php echo $current_page == 'services.php' ? 'active' : ''; ?>"
                        href="services.php">Services</a></li>
                <li><a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>"
                        href="about.php">About</a></li>
            </ul>
            <div class="btn-container">
                <a href="Client/client_register.php" class="btn btn-signup">Sign Up</a>
                <a href="Client/client_login.php" class="btn btn-login">Login</a>
            </div>
        </nav>
    </header>
</body>

</html>