<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/p06_grp2/styles.css">
</head>
<body>

    <div class="login-container">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="325" height="120">
        <h1>AMC Login</h1>

        <?php
        // Check if there is an error query parameter
        if (isset($_GET['error'])) {
            echo "<p style='color: red;'>" . htmlspecialchars($_GET['error']) . "</p>";
        }
        ?>

        <form name="form1" method="post" action="../auth.php">
            <input name="email" type="email" id="email" class="input-field" placeholder="Email" required><br>
            <input name="password" type="password" id="password" class="input-field" placeholder="Password" required><br>
            <input type="submit" name="Submit" value="Login" class="btn">
        </form>

        <!-- Update the href to point to forgot_password.php -->
        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
    </div>

</body>
</html>
