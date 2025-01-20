<?php
session_start();

function authenticate($myemail, $mypassword)
{
    if (empty($myemail) || empty($mypassword)) {
        die("Email or password is empty!");
    }

    // Establish a database connection
    $connect = mysqli_connect("localhost", "root", "", "amc") or die("cannot connect");

    // Prepare SQL query to join Profile, User_Credentials, and Role tables
    $sql = $connect->prepare("SELECT Profile.email, User_Credentials.password, Role.name AS role 
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
        $sql->bind_result($email, $stored_password, $role);

        // Fetch the result
        $sql->fetch();

        // Verify the password using password_verify
        if (password_verify($mypassword, $stored_password)) {
            // Password is correct, start session and register the role and email
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $myemail;
            
            // Redirect based on the role
            if ($_SESSION['role'] == "Student") {
                header("location: student.php");  // Redirect to student page
            } else if ($_SESSION['role'] == "Facility Manager" || $_SESSION['role'] == "Admin") {
                header("location: admin.php");  // Redirect to admin page
            }
        } else {
            // Password does not match
            echo "Invalid email or password!";
        }
    } else {
        // If no matching email is found
        echo "Invalid email or password!";
    }

    // Close the statement
    $sql->close();
}

// Get email and password from form
$myemail = $_REQUEST['email'];
$mypassword = $_REQUEST['password'];

// Call the authentication function
authenticate($myemail, $mypassword);
?>
