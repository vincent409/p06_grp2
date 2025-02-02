<?php
// Generate a secure CSRF token with expiry time
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate new secure token
        $_SESSION['csrf_token_expiry'] = time() + 3600; // Token expires in 1 hour
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token with expiry and session ID check
function validateCsrfToken($csrfToken) {
    // Check if token exists, matches session token, and has not expired
    if (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token'] || time() > $_SESSION['csrf_token_expiry']) {
        // Destroy session and alert user
        session_destroy();
        echo "<script>
                alert('Security alert: CSRF validation failed. Please refresh the page.');
                window.location.href = '/p06_grp2/sites/index.php';
              </script>";
        exit;
    }

    // Rotate CSRF token after successful validation (prevents replay attacks)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expiry'] = time() + 3600; // Extend expiry time
}

// Function to check if CSRF token is expired
function isCsrfTokenExpired() {
    return time() > $_SESSION['csrf_token_expiry'];
}

define('ENCRYPTION_KEY', '$w@pk3Y'); // Replace with your actual key

// AES encryption function
function aes_encrypt($data) {
    // Hardcoded key (hashed to 32 bytes)
    $key = hash('sha256', ENCRYPTION_KEY, true); // Hash the key to ensure it's 32 bytes long

    // Generate a random IV (Initialization Vector) for CBC mode
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the data
    $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

    // Combine the IV and ciphertext to store together
    $iv_and_ciphertext = base64_encode($iv . $ciphertext);

    return $iv_and_ciphertext;
}

// AES decryption function
function aes_decrypt($encrypted_data) {
    // Hardcoded key (hashed to 32 bytes)
    $key = hash('sha256', ENCRYPTION_KEY, true); // Hash the key to ensure it's 32 bytes long

    // Decode the base64-encoded string (contains both IV and ciphertext)
    $data = base64_decode($encrypted_data);

    // Extract the IV and ciphertext
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_length);
    $ciphertext = substr($data, $iv_length);
    if (strlen($iv) !== $iv_length) {
        $iv = str_pad($iv, $iv_length, "\0"); // Pad IV with null bytes if too short
    }

    // Decrypt the data
    $decrypted_data = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);

    return $decrypted_data;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendAccountCreationEmail($email, $admin_number, $plain_password) {
    require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\PHPMailer.php';
    require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\Exception.php';
    require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\SMTP.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'amctemasek@gmail.com';
        $mail->Password = 'itub szoc bbtw mqld'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('amctemasek@gmail.com', 'Admin Team');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Account Created - Login Credentials";
        $mail->Body = "
            <h3>Welcome to the System</h3>
            <p>Your student account has been created successfully. Here are your login details:</p>
            <p><strong>Admin Number:</strong> $admin_number</p>
            <p><strong>Password:</strong> $plain_password</p>
            <p>Please login and change your password upon first login.</p>
            <p><a href='http://localhost/p06_grp2/sites/index.php'>Login Here</a></p>
            <p>Regards,<br>Admin Team</p>
        ";

        $mail->send();
        return true;  // Email sent successfully
    } catch (Exception $e) {
        return "Profile created, but email could not be sent. Error: " . htmlspecialchars($mail->ErrorInfo);
    }
}





?>
