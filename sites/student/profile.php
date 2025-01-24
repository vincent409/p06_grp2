<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /xampp/p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

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
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        .profile-info {
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
            width: 50%;
        }
        .back-btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Your Profile</h1>
    <div class="profile-info">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($phone_number); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></p>
    </div>
    <div class="back-btn">
        <a href="student-dashboard.php">
            <button>Back to Dashboard</button>
        </a>
    </div>
</body>
</html>
