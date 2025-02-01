    <?php
    session_start();

    // Ensure user is an Admin or Facility Manager
    if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
        die("You do not have permission to edit or delete profiles.");
    }

    // Include necessary files
    include_once 'C:/xampp/htdocs/p06_grp2/functions.php';
    include 'C:/xampp/htdocs/p06_grp2/validation.php';
    include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
    include 'C:/xampp/htdocs/p06_grp2/cookie.php';
    manageCookieAndRedirect("/p06_grp2/sites/index.php");

    // Generate CSRF token
    generateCsrfToken();

    // Initialize variables
    $id = $name = $email = $phone_number = $department = "";
    $inputErrors = [];
    // Fetch profile ID (Supports both POST and GET)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
    } else {
        die("Error: No ID provided.");
    }

    // Fetch profile details from database
    $sql = "SELECT id, name, admin_number, email, phone_number, department FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        $name = aes_decrypt($profile['name']);
        $admin_number = $profile['admin_number'];
        $email = aes_decrypt($profile['email']); // ✅ Decrypt email for display
        $phone_number = aes_decrypt($profile['phone_number']);
        $department = $profile['department'];
    } else {
        die("Profile not found.");
    }

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        validateCsrfToken($_POST['csrf_token']); // ✅ Validate CSRF token
    
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $admin_number = trim($_POST['admin_number']);
        $entered_email = (trim($_POST['email'])); // ✅ Encrypt email before storing
        $phone_number = trim($_POST['phone_number']);
        $department = trim($_POST['department']);
    
        // ✅ Validate Admin Number (7 digits + 1 letter)
        if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", $admin_number)) {
            $inputErrors[] = "Admin number must be 7 digits followed by 1 letter (e.g., 2304581H).";
        }
    
        // ✅ Validate Name
        if (!preg_match("/^[a-zA-Z0-9\s]+$/", $name)) {
            $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
        }
    
        // ✅ Validate Email Format
        $emailValidationResult = validateEmail($entered_email); // ✅ Validate before encrypting
        // Decrypt first to validate
        if ($emailValidationResult !== true) {
            $inputErrors[] = $emailValidationResult;
        }
    
        // ✅ Validate Phone Number
        if (!preg_match("/^[89][0-9]{7}$/", $phone_number)) {
            $inputErrors[] = "Phone number must start with 8 or 9 and be exactly 8 digits.";
        }
        
        $encrypted_name = aes_encrypt($name);
        $encrypted_phone = aes_encrypt($phone_number);
        $encrypted_email = aes_encrypt($entered_email); // Encrypt only for database storage


        // ✅ Check if Name Already Exists (Exclude current ID)
        $check_name_sql = "SELECT id, name FROM Profile WHERE id != ?";
        $checknamestmt = $connect->prepare($check_name_sql);
        $checknamestmt->bind_param("i", $id);
        $checknamestmt->execute();
        $result = $checknamestmt->get_result();
        
        $duplicate_found = false;
        $entered_name = trim($_POST['name']); // Get the user input (not encrypted yet)
        
        while ($row = $result->fetch_assoc()) {
            $decrypted_name = aes_decrypt($row['name']); // 🔹 Decrypt before comparing
            if (strcasecmp($decrypted_name, $entered_name) === 0) { // ✅ Compare plain input
                $duplicate_found = true;
                break;
            }
        }
        $checknamestmt->close();
        
        if ($duplicate_found) {
            $inputErrors[] = "This name is already registered.";
        }
        
        // ✅ Check if Admin Number Already Exists (Exclude current ID)
        $check_admin_sql = "SELECT id FROM Profile WHERE admin_number = ? AND id != ?";
        $checkadminstmt = $connect->prepare($check_admin_sql);
        $checkadminstmt->bind_param("si", $admin_number, $id);
        $checkadminstmt->execute();
        $checkadminstmt->store_result();    
        if ($checkadminstmt->num_rows > 0) {
            $inputErrors[] = "This Admin Number is already registered.";
        }
        $stmt->close();
    
        // ✅ Check if Email Already Exists (Exclude current ID)
        $check_email_sql = "SELECT id, email FROM Profile WHERE id != ?";
        $checkemailstmt = $connect->prepare($check_email_sql);
        $checkemailstmt->bind_param("i", $id);
        $checkemailstmt->execute();
        $result = $checkemailstmt->get_result();
        
        $duplicate_email = false;
        $entered_email = trim($_POST['email']); // Get user input before encryption
        
        while ($row = $result->fetch_assoc()) {
            $decrypted_email = aes_decrypt($row['email']); // 🔹 Decrypt before comparing
            if (strcasecmp($decrypted_email, $entered_email) === 0) { // ✅ Compare plain input
                $duplicate_email = true;
                break;
            }
        }
        $checkemailstmt->close();
        
        if ($duplicate_email) {
            $inputErrors[] = "This email address is already registered.";
        }
        
        // ✅ Check if Phone Number Already Exists (Exclude current ID)
        $check_phone_sql = "SELECT id, phone_number FROM Profile WHERE id != ?";
        $checkphonestmt = $connect->prepare($check_phone_sql);
        $checkphonestmt->bind_param("i", $id);
        $checkphonestmt->execute();
        $result = $checkphonestmt->get_result();

        $duplicate_phone = false;
        $entered_phone = trim($_POST['phone_number']); // Get user input before encryption

        while ($row = $result->fetch_assoc()) {
            $decrypted_phone = aes_decrypt($row['phone_number']); // 🔹 Decrypt before comparing
            if (strcasecmp($decrypted_phone, $entered_phone) === 0) { // ✅ Compare plain input
                $duplicate_phone = true;
                break;
            }
        }
        $checkphonestmt->close();

        if ($duplicate_phone) {
            $inputErrors[] = "This phone number is already registered.";
        }

        // ✅ Update the profile if no validation errors
        if (empty($inputErrors)) {
            $updateSql = "UPDATE Profile SET name = ?, admin_number = ?, email = ?, phone_number = ?, department = ? WHERE id = ?";
            $stmt = $connect->prepare($updateSql);
            $stmt->bind_param("sssssi", $encrypted_name, $admin_number, $encrypted_email, $encrypted_phone, $department, $id);

            
    
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                exit;
            } else {
                $inputErrors = "Error updating profile: " . $stmt->error;

            }
            $stmt->close();
        }
    }
    
    // Handle profile deletion
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
        validateCsrfToken($_POST['csrf_token']);
        if ($_SESSION['role'] != 'Admin') {
            die("You do not have permission to delete profiles.");
        }
    
        $id = intval($_POST['id']);
    
        // Ensure the ID is valid before proceeding
        if ($id > 0) {
            // Temporarily disable foreign key checks (useful if Profile has dependencies)
            $connect->query("SET FOREIGN_KEY_CHECKS=0");
    
            // Delete profile from database
            $delete_sql = "DELETE FROM Profile WHERE id = ?";
            $deletestmt = $connect->prepare($delete_sql);
            $deletestmt->bind_param("i", $id);
    
            if ($deletestmt->execute()) {
                $connect->query("SET FOREIGN_KEY_CHECKS=1");
                    $success_message = "Profile deleted successfully!";
                exit;
            } else {
                $inputErrors = "Error deleting profile: " . $deletestmt->error;
                $connect->query("SET FOREIGN_KEY_CHECKS=1");
            }
            $deletestmt->close();
        } else {
            $inputErrors ="Invalid profile ID.";
        }
    }
    
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit rofile</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
    </head>
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
        <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
        <a href="/p06_grp2/sites/admin/status.php">Status</a>
    </nav>

    <div class="main-container">
        <h1>Edit Profile</h1>

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

        <form action="edit_profile.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_POST['id']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            <label for="admin_number">Admin Number:</label> <!-- ✅ Added Admin Number -->
            <input type="text" name="admin_number" value="<?php echo htmlspecialchars($admin_number); ?>" required>
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <label for="phone_number">Phone Number:</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            <label for="department">Department:</label>
            <select name="department" id="department" required style="width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc;">
                <option value="School of Informatics & IT" <?php echo ($department == "School of Informatics & IT") ? "selected" : ""; ?>>School of Informatics & IT</option>
                <option value="School of Humanities & Social Sciences" <?php echo ($department == "School of Humanities & Social Sciences") ? "selected" : ""; ?>>School of Humanities & Social Sciences</option>
                <option value="School of Business" <?php echo ($department == "School of Business") ? "selected" : ""; ?>>School of Business</option>
            </select>
            <button type="submit" name="update">Update Profile</button>
            <button type="button" onclick="window.location.href='profile.php';">View All Profiles</button>
        </form>

        <?php if ($_SESSION['role'] == 'Admin') { ?>
        <form action="edit_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this profile?');">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <button type="submit" name="delete">Delete Profile</button>
        </form>
    <?php } ?>

    </div>
    </body>
    </html>
