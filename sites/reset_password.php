<?php
session_start();
// Check if the token is passed as a query parameter
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $connect = mysqli_connect("localhost", "root", "", "amc");

    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }

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
                        $delete_token_sql = "DELETE FROM PasswordReset WHERE profile_id = ?";
                        $delete_token_stmt = $connect->prepare($delete_token_sql);
                        $delete_token_stmt->bind_param("i", $profile_id);
                        $delete_token_stmt->execute();

                        // Redirect to the login page
                        header("Location: login.php?message=success");
                        exit();
                    } else {
                        echo "There was an error resetting your password. Please try again.";
                    }

                    $update_stmt->close();
                    $delete_token_stmt->close();
                } else {
                    echo "The passwords do not match. Please try again.";
                }
            }
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Reset Password</title>
            </head>
            <body>
                <h1>Reset Your Password</h1>
                <form action="reset_password.php?token=<?php echo $token; ?>" method="POST">
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" required><br><br>

                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required><br><br>

                    <input type="submit" value="Reset Password">
                </form>
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
