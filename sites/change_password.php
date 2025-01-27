<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

// Get the student's profile ID from the session
$profile_id = $_SESSION['profile_id'];

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the current password
    $password_query = "SELECT password FROM User_Credentials WHERE profile_id = ?";
    $stmt = $connect->prepare($password_query);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $stored_password)) {
        echo "<script>
            alert('Your current password is incorrect!');
            window.history.back();
        </script>";
        exit();
    } elseif ($new_password !== $confirm_password) {
        echo "<script>
            alert('New password and confirmation password do not match!');
            window.history.back();
        </script>";
        exit();
    } elseif (strlen($new_password) < 6) {
        echo "<script>
            alert('New password must be at least 6 characters long!');
            window.history.back();
        </script>";
        exit();
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_password_query = "UPDATE User_Credentials SET password = ? WHERE profile_id = ?";
        $stmt_update = $connect->prepare($update_password_query);
        $stmt_update->bind_param("si", $hashed_password, $profile_id);
        $stmt_update->execute();
        $stmt_update->close();

        echo "<script>
            alert('Password changed successfully!');
            window.location.href = '/p06_grp2/sites/student/profile.php';
        </script>";
        exit();
    }
}

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #E5D9B6;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            color: black;
            padding: 10px 20px;
        }
        .dashboard-title {
            flex: 1;
            text-align: center;
            font-size: 18px;
            color: #333;
            font-weight: bold;
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
            background-color: #FF6347;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        .logout-btn button:hover {
            background-color: #FF4500;
        }
        h1 {
            margin: 20px 0;
            text-align: center;
            color: #333;
        }
        .form-container {
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-container input {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        .btn-container button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-container button:hover {
            background-color: #0056b3;
        }
        .btn-container a button {
            background-color: #6c757d;
        }
        .btn-container a button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">
            Change Password
        </div>
        <div class="logout-btn">
            <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/p06_grp2/sites/student/student-dashboard.php">Home</a>
        <a href="/p06_grp2/sites/student/profile.php">Profile</a>
    </nav>

    <h1>Change Password</h1>

    <div class="form-container">
        <form method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <div class="btn-container">
                <a href="/p06_grp2/sites/student/profile.php"><button type="button">Back to Profile</button></a>
                <button type="submit">Change Password</button>
            </div>
        </form>
    </div>
</body>
</html>
