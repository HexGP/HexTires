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

    <header>
        <nav class="navbar">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Logo">
                <span>Panther Tire Service</span>
            </a>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="myaccount.php">MyPanther</a></li>
            </ul>
            <div class="btn-container">
                <a href="Client/client_register.php" class="btn btn-signup">Sign Up</a>
                <a href="Client/client_login.php" class="btn btn-login">Login</a>
            </div>
        </nav>
    </header>