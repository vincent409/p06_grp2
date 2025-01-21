<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: login.php");
    exit();
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

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

$message = ""; // To store success or error messages

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // If not first login, verify the current password
    if ($has_logged_in == 1) {
        $current_password = $_POST['current_password'];

        // Fetch the current password hash from the database
        $password_query = "SELECT password FROM User_Credentials WHERE profile_id = ?";
        $stmt = $connect->prepare($password_query);
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        $stmt->close();

        // Validate the current password
        if (!password_verify($current_password, $stored_password)) {
            $message = "<p style='color: red;'>Your current password is incorrect!</p>";
        } elseif ($new_password !== $confirm_password) {
            $message = "<p style='color: red;'>New password and confirmation password do not match!</p>";
        } elseif (strlen($new_password) < 6) {
            $message = "<p style='color: red;'>New password must be at least 6 characters long!</p>";
        } else {
            // Update the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_query = "UPDATE User_Credentials SET password = ? WHERE profile_id = ?";
            $stmt_update = $connect->prepare($update_password_query);
            $stmt_update->bind_param("si", $hashed_password, $profile_id);

            if ($stmt_update->execute()) {
                $message = "<p style='color: green;'>Password changed successfully!</p>";
            } else {
                $message = "<p style='color: red;'>Error updating password. Please try again.</p>";
            }

            $stmt_update->close();
        }
    } else {
        // First-time login: Validate only the new password
        if ($new_password !== $confirm_password) {
            $message = "<p style='color: red;'>New password and confirmation password do not match!</p>";
        } elseif (strlen($new_password) < 6) {
            $message = "<p style='color: red;'>New password must be at least 6 characters long!</p>";
        } else {
            // Update the new password and mark as logged in
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
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

            // Password successfully updated, redirect to dashboard
            $message = "<p style='color: green;'>Password set successfully! Redirecting to the dashboard...</p>";
            header("refresh:2;url=student-dashboard.php"); // Redirect after 2 seconds
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
    <?php echo $message; ?>

    <form method="POST">
        <?php if ($has_logged_in == 1): ?>
            <!-- Show current password field only for non-first-time logins -->
            <label for="current_password">Current Password:</label><br>
            <input type="password" id="current_password" name="current_password" required><br><br>
        <?php endif; ?>

        <label for="new_password">New Password:</label><br>
        <input type="password" id="new_password" name="new_password" required><br><br>

        <label for="confirm_password">Confirm New Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <button type="submit">Change Password</button>
    </form>

    <?php if ($has_logged_in == 1): ?>
        <!-- Show "Back to Dashboard" button only if not first-time login -->
        <br>
        <a href="student-dashboard.php">Back to Dashboard</a>
    <?php endif; ?>
</body>
</html>
