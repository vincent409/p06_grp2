<?php
// Start the session
session_start();

// Unset specific session variables
unset($_SESSION['profile_id']);
unset($_SESSION['role']);
unset($_SESSION['email']);

// Optionally, unset all session variables (if needed)
session_unset();

// Destroy the session
session_destroy();

// Clear the 'logout_occurred' cookie if it exists
if (isset($_COOKIE['logout_occurred'])) {
    setcookie('logout_occurred', '', time() - 3600, '/'); // Clear the cookie
}


// Redirect to the login page
header("Location: sites/index.php");
exit();
?>
