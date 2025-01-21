<?php
session_start();

// Manually include PHPMailer files
require 'C:\xampp\htdocs\xampp\p06_grp2\PHPMailer-master\src\PHPMailer.php';   // Path to PHPMailer.php file
require 'C:\xampp\htdocs\xampp\p06_grp2\PHPMailer-master\src\Exception.php';   // Path to Exception.php file
require 'C:\xampp\htdocs\xampp\p06_grp2\PHPMailer-master\src\SMTP.php';         // Path to SMTP.php file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if email is provided
if (isset($_POST['email'])) {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $sql = "SELECT id FROM Profile WHERE email = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate a unique token for the password reset link
        $token = bin2hex(random_bytes(50)); // Generate a secure token
        $current_time = date('Y-m-d H:i:s');

        // Store the token in the database
        $update_sql = "UPDATE Profile SET reset_token = ?, reset_token_time = ? WHERE email = ?";
        $stmt_update = $connect->prepare($update_sql);
        $stmt_update->bind_param("ss", $token, $email,$current_time);
        $stmt_update->execute();

        // Send the password reset link to the user's email using PHPMailer
        $reset_link = "http://localhost/xampp/p06_grp2/reset_password.php?token=" . $token;
        $message = "Click the link to reset your password: <a href='" . $reset_link . "'>Reset Password</a>";
        $subject = "Password Reset Request";
        // Set up PHPMailer
        $mail = new PHPMailer(true); // Create a new PHPMailer instance

        try {
            // Server settings
            $mail->isSMTP();                          // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';       // Outlook SMTP server
            $mail->SMTPAuth = true;                   // Enable SMTP authentication
            $mail->Username = 'amctemasek@gmail.com'; // Your Outlook email address
            $mail->Password = 'itub szoc bbtw mqld';         // Your Outlook email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
            $mail->Port = 587;                        // TCP port for Outlook SMTP server

            // Recipients
            $mail->setFrom('amctemasek@gmail.com');
            $mail->addAddress($email);                // Add recipient's email address

            // Content
            $mail->isHTML(true);                      // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = nl2br($message); 

            // Send the email
            $mail->send();
            echo 'Password reset link has been sent to your email.';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No user found with that email address.";
    }

    $stmt->close();
    mysqli_close($connect);
} else {
    // If email is not set, redirect to forgot password page
    header("Location: forgot_password.php");
    exit();
}
?>
