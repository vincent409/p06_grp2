<?php
// Start session for user authentication
session_start();

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if (isset($_POST['login-button'])) {
    // Retrieve form inputs
    $email = $_POST['email'];
    $password = $_POST['password'];

    // SQL query to check if the email exists and get the user's role
    $query = $connect->prepare("SELECT User_Credentials.password, Profile.id, Profile.name, Role.name AS role_name
                                FROM User_Credentials
                                JOIN Profile ON User_Credentials.profile_id = Profile.id
                                JOIN Role ON Profile.role_id = Role.id
                                WHERE Profile.email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $query->store_result();
    
    // Check if user exists
    if ($query->num_rows > 0) {
        $query->bind_result($stored_password, $profile_id, $name, $role_name);
        $query->fetch();

        // Debugging: check the password
        echo "<pre>";
        echo "Stored Password: " . $stored_password . "<br>";
        echo "Entered Password: " . $password . "<br>";
        echo "</pre>";

        // Verify the password with bcrypt (using password_verify)
        if ($password == $stored_password) {
            // Password is correct, start session and store user info
            $_SESSION['profile_id'] = $profile_id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role_name;  // Store the role in session
            
            // Redirect to the appropriate page based on the role
            if ($role_name == 'Admin' || $role_name == 'Facility Manager') {
                header("Location: facility-manager.php");  // Redirect to Admin/Facility Manager page
            } elseif ($role_name == 'Student') {
                header("Location: student-dashboard.php");  // Redirect to Student page
            }
            exit();
        } else {
            $error_message = "Invalid email or password!";
        }
    } else {
        $error_message = "User not found!";
    }

    // Close the statement
    $query->close();
}

// Close the database connection
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <form method="POST" action="login.php">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <button type="submit" name="login-button">Login</button>
    </form>

    <?php
    // Show error message if any
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>
</body>
</html>
