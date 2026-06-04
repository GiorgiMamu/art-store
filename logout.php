<?php
// logout.php
// Destroys the session and clears the remember me cookie, then sends user home.

session_start();

// Empty the session data
$_SESSION = [];

// Destroy the session completely
session_destroy();

// Delete the remember me cookie if it exists
// Setting the expiry to the past deletes it
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Send user back to homepage
header('Location: /art-store/index.php');
exit;
?>