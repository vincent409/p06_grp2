<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/p06_grp2/styles.css">
</head>
<body>

    <div class="login-container">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="325" height="120">
        <h1>Forgot Password</h1>

        <?php
        // Display error message if email is not found
        if (isset($_GET['error']) && $_GET['error'] == 1) {
            echo "<p style='color: red;'>Error: No account found with that email address!</p>";
        }
        ?>

        <form action="/p06_grp2/send_reset_link.php" method="POST">
            <input type="email" name="email" id="email" class="input-field" placeholder="Enter your email" required><br>
            <input type="submit" name="Submit" value="Send Reset Link" class="btn">
        </form>

        <a href="index.php" class="forgot-password">Back to Login</a>
    </div>

</body>
</html>
