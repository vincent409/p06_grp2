<?php
session_start();
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to create profiles.");
}
include 'C:/xampp/htdocs/p06_grp2/Function.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];
$success_message = '';

$csrf_token = generateCsrfToken();



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token']);


    // Collect form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $department = trim($_POST['department']);
    $role_id = 3; // Assuming '3' corresponds to the Student role


    // Validate name
    if (!preg_match($alphanumeric_pattern, $name)) {
        $inputErrors[] = "Name must contain only alphanumeric characters and spaces.";
    }


    // Validate email using the function from validation.php
    $emailValidationResult = validateEmail($email);

    if ($emailValidationResult !== true) {
        $inputErrors[] = $emailValidationResult; // Add the validation error to the inputErrors array
    }


    // Validate phone number (optional field)
    if (!empty($phone_number) && !preg_match($phonePattern, $phone_number)) {
        $inputErrors[] = "Phone number must be 8.";
    }



    // Check for duplicate email
    $check_email_sql = "SELECT id FROM Profile WHERE email = ?";
    $stmt = $connect->prepare($check_email_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This email address is already registered.";
    }
    $stmt->close();

    // Check for duplicate phone number (if provided)
    if (!empty($phone_number)) {
        $check_phone_sql = "SELECT id FROM Profile WHERE phone_number = ?";
        $stmt = $connect->prepare($check_phone_sql);
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $inputErrors[] = "This phone number is already registered.";
        }
        $stmt->close();
    }

    // Check for duplicate name
    $check_name_sql = "SELECT id FROM Profile WHERE name = ?";
    $stmt = $connect->prepare($check_name_sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $inputErrors[] = "This name is already registered.";
    }
    $stmt->close();

    // If no errors, insert into the database
    if (empty($inputErrors)) {
        $insert_sql = "INSERT INTO Profile (name, email, phone_number, department, role_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connect->prepare($insert_sql);
        $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $role_id);

        if ($stmt->execute()) {
            $success_message = "New student profile created successfully!";
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
        }

        form button:hover {
            background-color: #0056b3;
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
    <h1>Create New Student Profile</h1>

    <?php if (!empty($success_message)) { ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
        <ul class="error-messages">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

        <form method="POST" action="add_profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br><br>

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
