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

// Optionally, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the login page
header("Location: sites/index.php");
exit();
?>
