<?php
// Generate CSRF token (once per session)
// Generate a secure CSRF token with expiry time
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate new secure token
        $_SESSION['csrf_token_expiry'] = time() + 3600; // Token expires in 1 hour
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token with expiry and session ID check
function validateCsrfToken($csrfToken, $redirectPage = 'profile.php') {
    // Check if token exists, matches session token, and has not expired
    if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token'] || time() > $_SESSION['csrf_token_expiry']) {
        // Destroy session and alert user
        session_destroy();
        echo "<script>
                alert('Security alert: CSRF validation failed. Please refresh the page.');
                window.location.href = '$redirectPage';
              </script>";
        exit;
    }

    // ✅ Rotate CSRF token after successful validation (prevents replay attacks)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expiry'] = time() + 3600; // Extend expiry time
}

// Function to check if CSRF token is expired
function isCsrfTokenExpired() {
    return time() > $_SESSION['csrf_token_expiry'];
}


?>
