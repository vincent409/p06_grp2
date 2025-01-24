<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /xampp/p06_grp2/sites/index.php");
    exit(); // Stop further execution
}
// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Handle form submission
if (isset($_POST['add-button'])) {
    // Retrieve form inputs
    $equipment_name = $_POST['equipment_name'];
    $equipment_type = $_POST['equipment_type'];
    $purchase_date = $_POST['purchase_date'];
    $model_number = $_POST['model_number'];

    // Insert new equipment into the database
    $query = $connect->prepare("INSERT INTO Equipment (name, type, purchase_date, model_number) VALUES (?, ?, ?, ?)");

    // Bind parameters: "ssss" means:
    // s = string, s = string, s = string, s = string
    $query->bind_param("ssss", $equipment_name, $equipment_type, $purchase_date, $model_number);

    // Execute the query
    if ($query->execute()) {
        echo "New equipment added successfully!";
    } else {
        echo "Error: " . $query->error;
    }

    // Close the statement
    $query->close();
}

// Close the database connection
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
    <style>
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
    </style>
</head>
<body>
<header>
        <div class="logo">
            <img src="/xampp/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='/xampp/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/xampp/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
        <a href="/xampp/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
        <a href="/xampp/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/xampp/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/xampp/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    </nav>
    <h1>Add New Equipment</h1>
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
</body>
</html>
