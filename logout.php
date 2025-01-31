<?php
// Start the session
session_start();

// Unset specific session variables
unset($_SESSION['profile_id']);
unset($_SESSION['role']);
unset($_SESSION['email']);

// Destroy the session and clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_unset();  // Unset all session variables
session_destroy();  // Destroy the session

// Clear the 'logout_occurred' cookie if it exists
if (isset($_COOKIE['logout_occurred'])) {
    setcookie('logout_occurred', '', time() - 3600, '/'); // Clear the cookie
}

// Redirect to the login page
header("Location: sites/index.php");
exit();
?>
