<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to sign in
header("Location: sign_in.html");
exit;
?>


