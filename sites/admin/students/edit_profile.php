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
$csrf_token = generateCsrfToken();

// Initialize variables
$id = $name = $email = $phone_number = $department = "";
$inputErrors = [];
$successMessage = "";
$errorMessage = "";

// Fetch profile ID (Supports both POST and GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    die("Error: No ID provided.");
}

// Fetch profile details from database
$sql = "SELECT id, name, email, phone_number, department FROM Profile WHERE id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $name = $profile['name'];
    $email = aes_decrypt($profile['email']);
    $phone_number = $profile['phone_number'];
    $department = $profile['department'];
} else {
    die("Profile not found.");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    validateCsrfToken($_POST['csrf_token'],'profile.php'); // Validate CSRF token

    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $department = trim($_POST['department']);

    // Validate inputs
    if (!preg_match("/^[a-zA-Z0-9\s]+$/", $name)) {
        $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
    }

    // Validate email using function from validation.php
    $emailValidationResult = validateEmail($email);
    if ($emailValidationResult !== true) {
        $inputErrors[] = $emailValidationResult;
    }

    $phone_number = trim($_POST['phone_number']);
    if (!preg_match($phonePattern, $phone_number)) {
        $inputErrors[] = "Phone number must start with 8 or 9 and be exactly 8 digits.";
    }
    


    // Update the profile if no validation errors
    if (empty($inputErrors)) {
        $updateSql = "UPDATE Profile SET name = ?, email = ?, phone_number = ?, department = ? WHERE id = ?";
        $stmt = $connect->prepare($updateSql);
        $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $id);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Profile updated successfully!');
                    window.location.href = 'profile.php';
                  </script>";
            exit;
        } else {
            $errorMessage = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle profile deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    if ($_SESSION['role'] != 'Admin') {
        die("You do not have permission to delete profiles.");
    }

    validateCsrfToken($_POST['csrf_token']); // Validate CSRF token

    $id = intval($_POST['id']);

    // Temporarily disable foreign key checks
    $connect->query("SET FOREIGN_KEY_CHECKS=0");

    // Delete profile from database
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $connect->query("SET FOREIGN_KEY_CHECKS=1");
        header("Location: profile.php?message=deleted");
        exit;
    } else {
        $errorMessage = "Error deleting profile: " . $stmt->error;
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

        .main-container {
            background-color: #FFFFFF;
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        form input[type="text"], form input[type="email"], form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        form button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            margin-top: 10px;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #E53D29; /* Red background */
            color: white;
        }

        .delete-button:hover {
            background-color: #C0392B; /* Darker red */
        }

        .success-message {
            color: green;
            margin-bottom: 10px;
        }

        .error-messages {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .error-messages li {
            margin-bottom: 5px;
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
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
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
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" name="delete" class="delete-button">Delete Profile</button>
    </form>
<?php } ?>

</div>
</body>
</html>
