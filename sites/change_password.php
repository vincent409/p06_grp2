<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

// Get the student's profile ID and "has_logged_in" status
$profile_id = $_SESSION['profile_id'];
$email = $_SESSION['email'];

$query = "SELECT has_logged_in FROM Profile WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($has_logged_in);
$stmt->fetch();
$stmt->close();

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($has_logged_in == 1) {
        // Non-first login: Validate the current password
        $current_password = $_POST['current_password'];

        $password_query = "SELECT password FROM User_Credentials WHERE profile_id = ?";
        $stmt = $connect->prepare($password_query);
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $stored_password)) {
            echo "<p style='color: red;'>Your current password is incorrect!</p>";
        } elseif ($new_password !== $confirm_password) {
            echo "<p style='color: red;'>New password and confirmation password do not match!</p>";
        } elseif (strlen($new_password) < 6) {
            echo "<p style='color: red;'>New password must be at least 6 characters long!</p>";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_password_query = "UPDATE User_Credentials SET password = ? WHERE profile_id = ?";
            $stmt_update = $connect->prepare($update_password_query);
            $stmt_update->bind_param("si", $hashed_password, $profile_id);
            $stmt_update->execute();
            $stmt_update->close();

            echo "<script>
                alert('Password changed successfully!');
                window.location.href = '/p06_grp2/sites/student/student-dashboard.php';
            </script>";
            exit();
        }
    } else {
        // First-time login
        if ($new_password !== $confirm_password) {
            echo "<p style='color: red;'>New password and confirmation password do not match!</p>";
        } elseif (strlen($new_password) < 6) {
            echo "<p style='color: red;'>New password must be at least 6 characters long!</p>";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_password_query = "UPDATE User_Credentials SET password = ? WHERE profile_id = ?";
            $stmt_update = $connect->prepare($update_password_query);
            $stmt_update->bind_param("si", $hashed_password, $profile_id);
            $stmt_update->execute();
            $stmt_update->close();

            $update_login_query = "UPDATE Profile SET has_logged_in = 1 WHERE id = ?";
            $stmt_login = $connect->prepare($update_login_query);
            $stmt_login->bind_param("i", $profile_id);
            $stmt_login->execute();
            $stmt_login->close();

            echo "<script>
                alert('Password set successfully!');
                window.location.href = '/p06_grp2/sites/student/student-dashboard.php';
            </script>";
            exit();
        }
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
</head>
<body>
    <h1>Change Password</h1>

    <form method="POST">
        <?php if ($has_logged_in == 1): ?>
            <label for="current_password">Current Password:</label><br>
            <input type="password" id="current_password" name="current_password" required><br><br>
        <?php endif; ?>

        <label for="new_password">New Password:</label><br>
        <input type="password" id="new_password" name="new_password" required><br><br>

        <label for="confirm_password">Confirm New Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <a href="/p06_grp2/sites/student/student-dashboard.php"><button type="button">Back to Dashboard</button></a>
        <button type="submit">Change Password</button>
    </form>
</body>
</html>
