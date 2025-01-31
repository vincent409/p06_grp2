<?php
session_start();

// Securely Include PHPMailer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include PHPMailer & Encryption Functions
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\PHPMailer.php';
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\Exception.php';
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\SMTP.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include_once 'C:/xampp/htdocs/p06_grp2/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Singapore');

if (isset($_POST['email'])) {
    $input_email = $_POST['email'];

    // Fetch encrypted emails from the database and decrypt them
    $sql = "SELECT id, email FROM Profile";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $profile_id = null;
    $decrypted_email = null;

    while ($row = $result->fetch_assoc()) {
        $decrypted_email = aes_decrypt($row['email']); // Decrypt email
        if ($decrypted_email === $input_email) {
            $profile_id = $row['id'];
            break;
        }
    }
    $stmt->close();

    if ($profile_id) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $current_time = date('Y-m-d H:i:s');

        // Insert reset token into the database
        $insert_sql = "INSERT INTO PasswordReset (profile_id, reset_token, reset_token_time) VALUES (?, ?, ?)";
        $stmt_insert = $connect->prepare($insert_sql);
        $stmt_insert->bind_param("iss", $profile_id, $token, $current_time);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Send email
        $reset_link = "http://localhost/p06_grp2/sites/reset_password.php?token=" . $token;
        $message = "Click the link to reset your password: <a href='" . $reset_link . "'>Reset Password</a>";
        $subject = "Password Reset Request";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'amctemasek@gmail.com';
            $mail->Password = 'itub szoc bbtw mqld';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('amctemasek@gmail.com');
            $mail->addAddress($decrypted_email); // Use decrypted email

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br($message);

            $mail->send();
            echo 'Password reset link has been sent to your email.';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('No user found with that email address.'); window.location.href='forgot_password.php';</script>";
    }

    mysqli_close($connect);
} else {
    header("Location: sites/forgot_password.php");
    exit();
}

?>
