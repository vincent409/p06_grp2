<?php
session_start();//start the session to authenticate users
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    header("Location: /p06_grp2/sites/index.php?error=No permission");
    exit();
}
//include necessary files for database connection,validation,functions and cookie
include 'C:/xampp/htdocs/p06_grp2/functions.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
//redirect users to index.php if inactive
manageCookieAndRedirect("/p06_grp2/sites/index.php");

//initialize variable and arrays
$inputErrors = [];
$success_message = '';
//generate a csrf token
generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token']);

    // Collect form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $admin_number = trim($_POST['admin_number']);
    $phone_number = trim($_POST['phone_number']);
    $department = trim($_POST['department']);
    $role_id = 3; // assign role ID for students

    // 🔹 Encrypt values before inserting into the database
    $encrypted_name = aes_encrypt($name);
    $encrypted_email = aes_encrypt($email);
    $encrypted_phone = aes_encrypt($phone_number);

    // Generate a random password
    $plain_password = bin2hex(random_bytes(4)); // Generates an 8-character password
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT); // Hash before storing

    // Validate admin number (should be 7 digits followed by 1 letter)
    if (!preg_match($admin_number_pattern, $admin_number)) {
        $inputErrors[] = "Admin number must be 7 digits followed by 1 letter (e.g., 1234567A).";
    }

    // Validate name
    if (!preg_match($alphanumeric_pattern, $name)) {
        $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
    }

    // Validate email
    $emailValidationResult = validateEmail($email);
    if ($emailValidationResult !== true) {
        $inputErrors[] = $emailValidationResult;
    }

    // Validate phone number
    if (!preg_match($phonePattern, $phone_number)) {
        $inputErrors[] = "Phone number must start with 8 or 9 and be exactly 8 digits.";
    }

    // 🔹 Check for duplicate name
    $check_name_sql = "SELECT id, name FROM Profile";
    $checknamestmt = $connect->prepare($check_name_sql);
    $checknamestmt->execute();
    $result = $checknamestmt->get_result();
    
    $duplicate_found = false;// initialize a flag variable
    //loop thorugh each row in the result
    while ($row = $result->fetch_assoc()) {
        if (aes_decrypt($row['name']) === $name) {
            $duplicate_found = true;//if a match is found set the flag to true
            break; // Stop checking further once a match is found
        }
    }
    $checknamestmt->close();
    
    if ($duplicate_found) {
        $inputErrors[] = "This name is already registered.";
    }
    

    // 🔹 **Check for duplicate admin number**
    $check_admin_sql = "SELECT id FROM Profile WHERE admin_number = ?";
    $checkadminstmt = $connect->prepare($check_admin_sql);
    $checkadminstmt->bind_param("s", $admin_number);
    $checkadminstmt->execute();
    $checkadminstmt->store_result();
    if ($checkadminstmt->num_rows > 0) { //check the num_rows > 0 
        $inputErrors[] = "This Admin Number is already registered.";
    }
    $checkadminstmt->close();

    // 🔹 **Check for duplicate email**
// 🔹 Check for duplicate email
    $check_email_sql = "SELECT id, email FROM Profile";
    $checkemailstmt = $connect->prepare($check_email_sql);
    $checkemailstmt->execute();
    $result = $checkemailstmt->get_result();
    //initialize the flag
    $duplicate_email = false;
    //loop through each row in result
    while ($row = $result->fetch_assoc()) {
        if (aes_decrypt($row['email']) === $email) {
            $duplicate_email = true;//if duplicate email is found se tto true
            break;
        }
    }
    $checkemailstmt->close();

    if ($duplicate_email) {
        $inputErrors[] = "This email address is already registered.";
    }

    // 🔹 Check for duplicate phone number
    $check_phone_sql = "SELECT id, phone_number FROM Profile";
    $checkphonestmt = $connect->prepare($check_phone_sql);
    $checkphonestmt->execute();
    $result = $checkphonestmt->get_result();
    //initialize the flag 
    $duplicate_phone = false;
    //loop through each result in row
    while ($row = $result->fetch_assoc()) {
        if (aes_decrypt($row['phone_number']) === $phone_number) {
            $duplicate_phone = true;
            break;
        }
    }
    $checkphonestmt->close();

    if ($duplicate_phone) {
        $inputErrors[] = "This phone number is already registered.";
    }

    // If no errors, insert into the database
    if (empty($inputErrors)) {
        $insert_sql = "INSERT INTO Profile (name, email, admin_number, phone_number, department, role_id) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connect->prepare($insert_sql);
        //bind the parameters to string or interger
        $stmt->bind_param("sssssi", $encrypted_name, $encrypted_email, $admin_number, $encrypted_phone, $department, $role_id);

        if ($stmt->execute()) {
            $profile_id = $stmt->insert_id; // Get the last inserted profile ID

            // Store user credentials
            $insert_cred_sql = "INSERT INTO User_Credentials (profile_id, password) VALUES (?, ?)";
            $stmt_cred = $connect->prepare($insert_cred_sql);
            $stmt_cred->bind_param("is", $profile_id, $hashed_password);
            $stmt_cred->execute();
            $stmt_cred->close();

            // Call sendEmail function from functions.php
        $emailResult = sendAccountCreationEmail($email, $admin_number, $plain_password);
        if ($emailResult === true) {
            $success_message = "Profile created successfully! Login credentials have been sent to the student's email.";
        } else {
            $inputErrors[] = $emailResult; // Store the error message from sendEmail function
        }

        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Profile</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
<head>
<body>
<header>
    <div class="logo">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
    </div>
    <div class="dashboard-title">Dashboard</div>
    <div class="logout-btn">
        <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
    </div>
</header>

<nav>
    <a href="/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
    <a href="/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Assignments</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="main-container">
    <h1>Create New Student Profile</h1>
    <?php if (!empty($success_message)) { ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
    <ul class="error-message">
        <?php foreach ($inputErrors as $error) { ?>
            <li><?php echo $error; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>


        <form method="POST" action="add_profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br><br>

        <label for="admin_number">Admin Number:</label><br> <!-- ✅ New field -->
        <input type="text" id="admin_number" name="admin_number" required><br><br>


        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="phone_number">Phone Number:</label><br>
        <input type="text" id="phone_number" name="phone_number"><br><br>

        <label for="department">Department:</label><br>
        <select id="department" name="department" required style="width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc;">

            <option value="">Select a Department</option>
            <option value="School of Informatics & IT">School of Informatics & IT</option>
            <option value="School of Humanities & Social Sciences">School of Humanities & Social Sciences</option>
            <option value="School of Business">School of Business</option>
        </select><br><br>

        <button type="submit">Create Profile</button>
        <button type="button" onclick="window.location.href='profile.php';">View Profiles</button>
    </form>

</div>
</body>
</html>
