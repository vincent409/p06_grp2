<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the user's profile ID from the session
$profile_id = $_SESSION['profile_id'];

// Check if the student needs to reset their password
$query = "SELECT has_logged_in FROM Profile WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($has_logged_in);
$stmt->fetch();

// If it's not the first login, redirect to dashboard
if ($has_logged_in == 1) {
    header("Location: /p06_grp2/sites/student/student-dashboard.php");
    exit();
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get new password
    $new_password = $_POST['new_password'];

    // Check password length (minimum 6 characters)
    if (strlen($new_password) >= 6) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the User_Credentials table
        $update_password_query = "UPDATE User_Credentials SET password = ? WHERE profile_id = ?";
        $stmt_update = $connect->prepare($update_password_query);
        $stmt_update->bind_param("si", $hashed_password, $profile_id);
        $stmt_update->execute();

        // Set has_logged_in to 1 in the Profile table
        $update_login_query = "UPDATE Profile SET has_logged_in = 1 WHERE id = ?";
        $stmt_update_login = $connect->prepare($update_login_query);
        $stmt_update_login->bind_param("i", $profile_id);
        $stmt_update_login->execute();

        // Redirect to the student dashboard
        echo "<p>Password reset successful!</p>";
        header("refresh:2;url=student-dashboard.php");  // Redirect after 2 seconds
        exit();
    } else {
        echo "<p>Password must be at least 6 characters long!</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body>
    <h1>Reset Your Password</h1>
    <form method="POST" action="student.php">
        <label for="new_password">New Password:</label><br>
        <input type="password" id="new_password" name="new_password" required><br><br>

        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
