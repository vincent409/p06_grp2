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
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];

// Handle form submission
if (isset($_POST['add-button'])) {
    // Retrieve form inputs
    $equipment_name = $_POST['equipment_name'];
    $equipment_type = $_POST['equipment_type'];
    $purchase_date = $_POST['purchase_date'];
    $model_number = $_POST['model_number'];

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

        form input[type="text"], form input[type="date"], form button {
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
