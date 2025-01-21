<?php
session_start();

function authenticate($myemail, $mypassword)
{
    // Check if email and password are provided
    if (empty($myemail) || empty($mypassword)) {
        die("Email or password is empty!");
    }

    // Establish a database connection
    $connect = mysqli_connect("localhost", "root", "", "amc");

    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Prepare SQL query to join Profile, User_Credentials, and Role tables
    $sql = $connect->prepare("SELECT Profile.id, Profile.email, Profile.has_logged_in, 
                              User_Credentials.password, Role.name AS role
                              FROM User_Credentials
                              JOIN Profile ON User_Credentials.profile_id = Profile.id
                              JOIN Role ON Profile.role_id = Role.id
                              WHERE Profile.email = ?");
    
    // Bind the email parameter
    $sql->bind_param("s", $myemail);

    // Execute the query
    $sql->execute();
    $sql->store_result();

    // If a result is returned
    if ($sql->num_rows > 0) {
        // Bind the result to variables
        $sql->bind_result($profile_id, $email, $has_logged_in, $stored_password, $role);

        // Fetch the result
        $sql->fetch();

        // Verify the password using password_verify
        if (password_verify($mypassword, $stored_password)) {
            // Password is correct, start session and register the role, email, and profile id
            $_SESSION['profile_id'] = $profile_id;
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $myemail;

            // If it's the first login, redirect to student.php for password reset
            if ($has_logged_in == 0 && $role == "Student") {
                // Redirect to the student password reset page
                header("Location: student.php");
                exit();
            }

            // Redirect based on the role
            if ($role == "Student") {
                header("Location: student-dashboard.php");  // Redirect to student dashboard
                exit();
            } else if ($role == "Facility Manager" || $role == "Admin") {
                header("Location: admin.php");  // Redirect to admin page
                exit();
            }
        } else {
            // Password does not match
            header("Location: login.php?error=1");  // Redirect back with error message
            exit();
        }
    } else {
        // If no matching email is found
        header("Location: login.php?error=1");  // Redirect back with error message
        exit();
    }

    // Close the statement
    $sql->close();
    mysqli_close($connect);
}

// Get email and password from form (use POST method)
if (isset($_POST['email']) && isset($_POST['password'])) {
    $myemail = $_POST['email'];
    $mypassword = $_POST['password'];

    // Call the authentication function
    authenticate($myemail, $mypassword);
} else {
    // If the form is not submitted properly, redirect to login page
    header("Location: login.php");  // Redirect to login page
    exit();
}
?>
