<?php
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($csrfToken, $redirectPage = 'profile.php') {
    if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        echo "<script>
                alert('CSRF validation failed. Redirecting to a safe page.');
                window.location.href = '$redirectPage';
              </script>";
        exit;
    }
    // Regenerate CSRF token after validation to prevent replay attacks
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
