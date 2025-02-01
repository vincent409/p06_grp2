<?php
session_start();
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to create profiles.");
}

include 'C:/xampp/htdocs/p06_grp2/functions.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\PHPMailer.php';
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\Exception.php';
require 'C:\xampp\htdocs\p06_grp2\PHPMailer-master\src\SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];
$success_message = '';

$csrf_token = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token'], 'profile.php');

    // Collect form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $admin_number = trim($_POST['admin_number']);
    $phone_number = trim($_POST['phone_number']);
    $department = trim($_POST['department']);
    $role_id = 3; // Student role

    // Encrypt the email before storing
    $encrypted_email = aes_encrypt($email);

    // Generate a random password
    $plain_password = bin2hex(random_bytes(4)); // Generates an 8-character password
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT); // Hash before storing

    // Validate inputs
    if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", $admin_number)) {
        $inputErrors[] = "Admin number must be 7 digits followed by 1 letter (e.g., 1234567A).";
    }
    if (!preg_match($alphanumeric_pattern, $name)) {
        $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
    }
    $emailValidationResult = validateEmail($email);
    if ($emailValidationResult !== true) {
        $inputErrors[] = $emailValidationResult;
    }
    if (!preg_match($phonePattern, $phone_number)) {
        $inputErrors[] = "Phone number must start with 8 or 9 and be exactly 8 digits.";
    }
    
    $name = aes_encrypt($name);
    $phone_number = aes_encrypt($phone_number);

    // Check for duplicate admin number
    $check_admin_sql = "SELECT id FROM Profile WHERE admin_number = ?";
    $stmt = $connect->prepare($check_admin_sql);
    $stmt->bind_param("s", $admin_number);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This Admin Number is already registered.";
    }
    $stmt->close();

    // Check for duplicate email (encrypted)
    $check_email_sql = "SELECT id FROM Profile WHERE email = ?";
    $stmt = $connect->prepare($check_email_sql);
    $stmt->bind_param("s", $encrypted_email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This email address is already registered.";
    }
    $stmt->close();

    // If no errors, insert into the database
    if (empty($inputErrors)) {
        $insert_sql = "INSERT INTO Profile (name, email, admin_number, phone_number, department, role_id, has_logged_in) 
                       VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmt = $connect->prepare($insert_sql);
        $stmt->bind_param("sssssi", $name, $encrypted_email, $admin_number, $phone_number, $department, $role_id);

        if ($stmt->execute()) {
            $profile_id = $stmt->insert_id; // Get the last inserted profile ID

            // Store user credentials
            $insert_cred_sql = "INSERT INTO User_Credentials (profile_id, password) VALUES (?, ?)";
            $stmt_cred = $connect->prepare($insert_cred_sql);
            $stmt_cred->bind_param("is", $profile_id, $hashed_password);
            $stmt_cred->execute();
            $stmt_cred->close();

            // Send login credentials via email
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
                $success_message = "Profile created successfully! Login credentials sent to the student's email.";
            } catch (Exception $e) {
                $inputErrors[] = "Profile created, but email could not be sent. Error: " . $mail->ErrorInfo;
            }
        } else {
            $inputErrors[] = "An error occurred while creating the profile. Please try again.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Profile</title>
</head>
<body>
    <h1>Create New Student Profile</h1>

    <?php if (!empty($success_message)) { ?>
        <div style="color: green;"><?php echo $success_message; ?></div>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
        <ul style="color: red;">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form method="POST" action="add_profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="admin_number">Admin Number:</label>
        <input type="text" id="admin_number" name="admin_number" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" required>

        <label for="department">Department:</label>
        <select id="department" name="department" required>
            <option value="">Select a Department</option>
            <option value="School of Informatics & IT">School of Informatics & IT</option>
            <option value="School of Business">School of Business</option>
        </select>

        <button type="submit">Create Profile</button>
    </form>
</body>
</html>
