<?php
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to create profiles.");
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];
    $role_id = 3; // Assuming '3' corresponds to the Student role

    // Insert the new profile into the database
    $insert_sql = "INSERT INTO Profile (name, email, phone_number, department, role_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($insert_sql);
    $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $role_id);
    $stmt->execute();

    // Redirect after successful creation
    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Profile</title>
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
            <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='/xampp/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
        <a href="/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
        <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    </nav>
    <h1>Create New Student Profile</h1>
    <form action="add_profile.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required><br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required><br><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" id="phone_number"><br><br>

        <label for="department">Department:</label>
        <input type="text" name="department" id="department" required><br><br>

        <input type="submit" value="Create Profile">
    </form>
</body>
</html>
