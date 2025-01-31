<?php
session_start();

// Include the validation and database connection files
include 'C:/xampp/htdocs/p06_grp2/validation.php';

function authenticate($myemail, $mypassword)
{
    try {
        // Validate the email before proceeding
        $emailValidationResult = validateEmail($myemail);
        if ($emailValidationResult !== true) {
            throw new Exception("Invalid email format.");
        }
        
        // Check if password is provided
        if (empty($mypassword)) {
            throw new Exception("Password is empty!");
        }

        include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

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
            
                // If it's the first login
                if ($has_logged_in == 0 && $role == 3) {
                    // Call send_reset_link.php to send the reset email
                    $_POST['email'] = $myemail; // Pass the email to the script
                    session_destroy();
                    include 'C:/xampp/htdocs/p06_grp2/send_reset_link.php';
                    // Show alert and redirect back to login page
                    echo "<script>
                        alert('A password reset link has been sent to your email. Please reset your password.');
                        window.location.href = '/p06_grp2/logout.php';
                    </script>";
                    exit();
                }
            
                // Redirect based on the role
                if ($role == "Student") {
                    header("Location: sites/student/student-dashboard.php");  // Redirect to student dashboard
                    exit();
                } else if ($role == "Facility Manager" || $role == "Admin") {
                    header("Location: sites/admin/admin-dashboard.php");  // Redirect to admin page
                    exit();
                }
            } else {
                // Password does not match
                throw new Exception("Email and password do not match.");
            }
            
        } else {
            // If no matching email is found
            throw new Exception("No user found with that email.");
        }

        // Close the statement
        $sql->close();
        mysqli_close($connect);

    } catch (Exception $e) {
        // Catch any exception and pass the error message to the calling function
        header("Location: sites/index.php?error=" . urlencode($e->getMessage()));  // Redirect back with the error message
        exit();
    }
}

// Get email and password from form (use POST method)
if (isset($_POST['email']) && isset($_POST['password'])) {
    $myemail = $_POST['email'];
    $mypassword = $_POST['password'];

    // Call the authentication function
    authenticate($myemail, $mypassword);
} else {
    // If the form is not submitted properly, redirect to login page
    header("Location: sites/index.php");  // Redirect to login page
    exit();
}
?>
