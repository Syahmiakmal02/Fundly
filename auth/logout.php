<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to landing page
header("Location: ../views/landingPage.php");
exit();