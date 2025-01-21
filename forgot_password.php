<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>
    <h1>Forgot Password</h1>
    <form action="send_reset_link.php" method="POST">
        <label for="email">Enter your email address:</label>
        <input type="email" name="email" id="email" required>
        <input type="submit" value="Send Reset Link">
    </form>
</body>
</html>
