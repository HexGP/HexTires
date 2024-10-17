<?php
// client_logout.php

// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to client login page
header("Location: client_login.php");
exit();
?>
