<?php
session_start();

// Check if the user is an Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
    die("You do not have permission to edit or delete profiles.");
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Initialize variables
$name = $email = $phone_number = $department = "";
$inputErrors = [];
$successMessage = "";
$errorMessage = "";
// Fetch the profile to edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Regenerate CSRF token after validation to prevent replay attacks
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Fetch profile data from the database
    $sql = "SELECT id, name, email, phone_number, department FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        $name = $profile['name'];
        $email = $profile['email'];
        $phone_number = $profile['phone_number'];
        $department = $profile['department'];
    } else {
        die("Profile not found.");
    }
}
// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $department = trim($_POST['department']);

    // Validation patterns
    $namePattern = "/^[a-zA-Z0-9\s]+$/"; // Allow alphanumeric and spaces
    $emailPattern = "/^[\w.%+-]+@[\w.-]+\.[a-zA-Z]{2,}$/"; // Email format
    $phonePattern = "/^[0-9]{8}$/"; // Phone number (8)

    // Validate the name
    if (!preg_match($namePattern, $name)) {
        $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
    }

    // Validate the email
    if (!preg_match($emailPattern, $email)) {
        $inputErrors[] = "Please enter a valid email address.";
    }

    // Validate the phone number (optional field)
    if (!empty($phone_number) && !preg_match($phonePattern, $phone_number)) {
        $inputErrors[] = "Phone number must contain only 8 digits ";
    }

    // Validate the department
    if (!preg_match($namePattern, $department)) {
        $inputErrors[] = "Department must contain only alphanumeric characters and spaces.";
    }

    // Check for duplicate email (excluding the current profile)
    $checkEmailSql = "SELECT id FROM Profile WHERE email = ? AND id != ?";
    $stmt = $connect->prepare($checkEmailSql);
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This email address is already registered.";
    }
    $stmt->close();

    // Check for duplicate phone number (excluding the current profile)
    if (!empty($phone_number)) {
        $checkPhoneSql = "SELECT id FROM Profile WHERE phone_number = ? AND id != ?";
        $stmt = $connect->prepare($checkPhoneSql);
        $stmt->bind_param("si", $phone_number, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $inputErrors[] = "This phone number is already registered.";
        }
        $stmt->close();
    }

    // Check for duplicate name (excluding the current profile)
    $checkNameSql = "SELECT id FROM Profile WHERE name = ? AND id != ?";
    $stmt = $connect->prepare($checkNameSql);
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This name is already registered.";
    }
    $stmt->close();

    // If there are no validation errors, proceed with the update
    if (empty($inputErrors)) {
        $updateSql = "UPDATE Profile SET name = ?, email = ?, phone_number = ?, department = ? WHERE id = ?";
        $stmt = $connect->prepare($updateSql);
        $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $id);

        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
        } else {
            $errorMessage = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    // Ensure only Admins can delete profiles
    if ($_SESSION['role'] != 'Admin') {
        die("You do not have permission to delete profiles.");
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    $id = $_POST['id'];

    // Temporarily disable foreign key checks
    $connect->query("SET FOREIGN_KEY_CHECKS=0");

    // Proceed to delete the profile
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Re-enable foreign key checks after successful deletion
        $connect->query("SET FOREIGN_KEY_CHECKS=1");
        // Redirect after successful deletion
        header("Location: profile.php?message=deleted");
        exit;
    } else {
        $errorMessage = "Error deleting profile: " . $stmt->error;
        // Re-enable foreign key checks even if an error occurs
        $connect->query("SET FOREIGN_KEY_CHECKS=1");
    }
    
    $stmt->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            background-color: #E5D9B6;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            color: black;
            padding: 10px 20px;
        }

        nav {
            display: flex;
            gap: 15px;
            background-color: #f4f4f4;
            padding: 10px 20px;
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        .logout-btn button {
            padding: 8px 12px;
            background-color: #E53D29;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .logout-btn button:hover {
            background-color: #E03C00;
        }

        .main-container {
            background-color: #FFFFFF;
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form input, form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        button[type="submit"][name="delete"] {
            background-color: #E53D29;
            color: white;
        }

        button[type="submit"][name="delete"]:hover {
            background-color: #E03C00;
        }

        button[type="submit"][name="update"] {
            background-color: #007bff;
            color: white;
        }

        button[type="submit"][name="update"]:hover {
            background-color: #0056b3;
        }
    </style>
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

    <?php if (!empty($successMessage)) { ?>
        <p style="color: green; font-weight: bold;"><?php echo $successMessage; ?></p>
    <?php } ?>

    <?php if (!empty($errorMessage)) { ?>
        <p style="color: red; font-weight: bold;"><?php echo $errorMessage; ?></p>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
        <ul style="color: red; font-weight: bold;">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form action="edit_profile.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_POST['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
        <label for="department">Department:</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" required>
        <button type="submit" name="update">Update Profile</button>
    </form>

    <?php if ($_SESSION['role'] == 'Admin') { ?>
    <form action="edit_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this profile?');">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" name="delete">Delete Profile</button>
    </form>
<?php } ?>

</div>
</body>
</html>
