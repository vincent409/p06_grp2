<?php
session_start();
// Check if the token is passed as a query parameter
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

    // Query the PasswordReset table for the token and its timestamp
    $sql = "SELECT reset_token_time, profile_id FROM PasswordReset WHERE reset_token = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Fetch the stored reset_token_time and profile_id from the PasswordReset table
        $stmt->bind_result($reset_token_time, $profile_id);
        $stmt->fetch();

        $current_time = new DateTime();
        $token_time = new DateTime($reset_token_time);

        // Check if the token has expired (e.g., 1 hour expiry)
        $expiry_time = $token_time->modify('+1 hour'); // Set the expiry time to 1 hour after the token creation

        if ($current_time > $expiry_time) {
            echo "This password reset link has expired.";

            // Optionally, delete the expired token
            $delete_sql = "DELETE FROM PasswordReset WHERE reset_token = ?";
            $delete_stmt = $connect->prepare($delete_sql);
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            $delete_stmt->close();
        } else {
            // Token is valid, show the password reset form
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                // Get the new and confirm passwords from the form
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Validate the new password
                if ($new_password === $confirm_password) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the password in the user_credentials table using profile_id
                    $update_sql = "UPDATE user_credentials SET password = ? WHERE profile_id = ?";
                    $update_stmt = $connect->prepare($update_sql);
                    $update_stmt->bind_param("si", $hashed_password, $profile_id);
                    if ($update_stmt->execute()) {
                        // Delete the token from the PasswordReset table after password reset
                        $connect->query("UPDATE Profile SET has_logged_in = 1 WHERE id = $profile_id");
                        $delete_token_sql = "DELETE FROM PasswordReset WHERE profile_id = ?";
                        $delete_token_stmt = $connect->prepare($delete_token_sql);
                        $delete_token_stmt->bind_param("i", $profile_id);
                        $delete_token_stmt->execute();

                        // Redirect to the login page
                        header("Location: /p06_grp2/sites/index.php?message=success");
                        exit();
                    } else {
                        echo "<script>
                        alert('There was an error resetting your password. Please try again.');
                        window.history.back();
                        </script>";
                        exit();
                    }

                    $update_stmt->close();
                    $delete_token_stmt->close();
                } else {
                    echo "<script>
                    alert('The passwords do not match. Please try again.');
                    window.history.back();
                    </script>";
                    exit();
                }
            }
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Reset Password</title>
                <link rel="stylesheet" href="/p06_grp2/styles.css">
            </head>
            <body>

                <div class="login-container">
                    <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="325" height="120">
                    <h1>Reset Your Password</h1>
                    <form action="reset_password.php?token=<?php echo $token; ?>" method="POST">
                        <input type="password" name="new_password" id="new_password" class="input-field" placeholder="New Password" required><br>
                        <input type="password" name="confirm_password" id="confirm_password" class="input-field" placeholder="Confirm Password" required><br>
                        <input type="submit" value="Reset Password" class="btn">
                    </form>

                    <a href="/p06_grp2/sites/index.php" class="forgot-password">Return to Login</a>
                </div>

            </body>
            </html>
            <?php
        }
    } else {
        echo "Invalid token.";
    }

    $stmt->close();
    mysqli_close($connect);
} else {
    echo "No token provided.";
}
?>
