<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

// Get the student's profile ID from the session
$profile_id = $_SESSION['profile_id'];

// Fetch personal profile information
$query = "SELECT name, email, phone_number, department FROM Profile WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone_number, $department);
$stmt->fetch();
$stmt->close();
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
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
        .logo img {
            width: 135px;
            height: 50px;
        }
        .welcome-text {
            flex: 1;
            text-align: center;
            font-size: 18px;
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
        h1 {
            color: #333;
            margin: 20px 0;
            text-align: center;
        }
        .profile-info {
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #ffffff;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: left;
        }
        .btn-container {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo">
        </div>
        <div class="logout-btn">
            <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/p06_grp2/sites/student/student-dashboard.php">Home</a>
        <a href="/p06_grp2/sites/student/profile.php">Profile</a>
    </nav>

    <h1>Your Profile</h1>
    <div class="profile-info">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($phone_number); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></p>
    </div>

    <div class="btn-container">
        <a href="student-dashboard.php">
            <button>Back to Dashboard</button>
        </a>
        <a href="/p06_grp2/sites/change_password.php">
            <button>Change Password</button>
        </a>
    </div>
</body>
</html>
