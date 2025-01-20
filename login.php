<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    
    <?php
    // Check if there is an error query parameter
    if (isset($_GET['error']) && $_GET['error'] == 1) {
        echo "<p style='color: red;'>email and password do not match!</p>";
    }
    ?>

    <form name="form1" method="post" action="auth.php">
        <table>
            <tr>
                <td><strong>Member Login</strong></td>
            </tr>
            <tr>
                <td>Email</td>
                <td>:</td>
                <td><input name="email" type="email" id="email" required></td>
            </tr>

            <tr>
                <td>Password</td>
                <td>:</td>
                <td><input name="password" type="password" id="password" required></td>
            </tr>

            <tr>
                <td></td>
                <td></td>
                <td><input type="submit" name="Submit" value="Login"></td>
            </tr>
        </table>
    </form>
</body>
</html>
