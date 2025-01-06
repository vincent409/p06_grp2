<?php
// STILL IN PROGRESS; Can fix if you want 🙂
#$connect=mysqli_connect("localhost","root","","Assignment");
if (isset($_POST['login-button'])) {
    $username=$_POST["username"];
    $password=$_POST["password"];
    $hashed_password=hash("sha256", $password);
    $query = $connect->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $query->bind_param("ss", $username, $hashed_password);

    $query->execute();

    $result = $query->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <title>Login</title>
</head>
<body>
    <div class="top-center">
        <h1>Login Page</h1>
    </div>
    <form method="post" action="index.php">
        <table align="center" border="0">
            <tr>
                <td>Username:</td>
                <td><input type="text" name="username" placeholder="Username" required></td>
            </tr>
            <tr>
                <td>Password:</td>
                <td><input type="text" name="password" placeholder="Password" required></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <button type="submit" name="login-button">Log In</button>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
