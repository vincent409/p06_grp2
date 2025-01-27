<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

include 'C:/xampp/htdocs/p06_grp2/vaildation.php';  // Assuming validation functions are in this file
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];

// Handle form submission
if (isset($_POST['add-button'])) {
    // Retrieve form inputs
    $equipment_name = trim($_POST['equipment_name']);
    $equipment_type = trim($_POST['equipment_type']);
    $purchase_date = trim($_POST['purchase_date']);
    $model_number = trim($_POST['model_number']);

    // Validate the equipment name
    if (!preg_match($alphanumeric_pattern, $equipment_name)) {
        $inputErrors[] = "Equipment name must contain only alphanumeric characters and spaces.";
    }

    // Validate the equipment type
    if (!preg_match($alphabet_pattern, $equipment_type)) {
        $inputErrors[] = "Equipment type must contain only letters and spaces.";
    }

    // Validate the model number
    if (!preg_match($model_number_pattern, $model_number)) {
        $inputErrors[] = "Model number must be alphanumeric, with dashes or underscores allowed.";
    }

    // Validate the purchase date
    if (validateDate($purchase_date) !== true) {
        $inputErrors[] = validateDate($purchase_date);  // Get the validation message from validateDate
    }

    if (empty($inputErrors)) {
        // Insert new equipment into the database
        $query = $connect->prepare("INSERT INTO Equipment (name, type, purchase_date, model_number) VALUES (?, ?, ?, ?)");

        // Bind parameters: "ssss" means:
        // s = string, s = string, s = string, s = string
        $query->bind_param("ssss", $equipment_name, $equipment_type, $purchase_date, $model_number);

        // Execute the query
        if ($query->execute()) {
            $success_message = "New equipment added successfully!";
        }

        // Close the statement
        $query->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
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
    <h1>Add New Equipment</h1>

    <?php if (isset($success_message)) { ?>
        <div style="color:green;"><?php echo $success_message; ?></div>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
        <ul style="color: red; font-weight: bold;">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form method="POST" action="add-equipment.php">
        <label for="equipment_name">Equipment Name:</label><br>
        <input type="text" id="equipment_name" name="equipment_name" required><br><br>

        <label for="equipment_type">Equipment Type:</label><br>
        <input type="text" id="equipment_type" name="equipment_type" required><br><br>

        <label for="purchase_date">Purchase Date:</label><br>
        <input type="date" id="purchase_date" name="purchase_date" required><br><br>

        <label for="model_number">Model Number:</label><br>
        <input type="text" id="model_number" name="model_number" required><br><br>

        <button type="submit" name="add-button">Add Equipment</button>
        <button onclick="window.location.href='equipment.php';">View All Equipment</button>
    </form>
</div>

</body>
</html>
