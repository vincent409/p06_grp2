<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';

// Get the student's profile ID from the session
$profile_id = $_SESSION['profile_id'];

// Fetch personal profile information
$query = "SELECT name, email, phone_number, department FROM Profile WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($name, $encrypted_email, $phone_number, $department);
$stmt->fetch();
$stmt->close();

$email = aes_decrypt($encrypted_email);
$name = aes_decrypt($name);
$phone_number = aes_decrypt($phone_number);
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="/p06_grp2/student.css"> <!-- Corrected Path -->
    </head>

<body>
    <header>
        <div class="logo">
            <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo">
        </div>
        <div class="dashboard-title">
            Welcome to Your Dashboard
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
