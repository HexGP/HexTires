<?php
// tech_logout.php

// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to technician login page
header("Location: tech_login.php");
exit();
?>
