<?php
session_start();

// Include the validation and database connection files
include 'C:/xampp/htdocs/p06_grp2/validation.php';

function authenticate($admin_number, $mypassword)
{
    try {
        // Validate the admin number before proceeding
        if (empty($admin_number)) {
            throw new Exception("Admin number is required.");
        }
        if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", trim($admin_number))) {
            throw new Exception("Invalid ID format!");
        }

        // Check if password is provided
        if (empty($mypassword)) {
            throw new Exception("Password is empty!");
        }
        
        $admin_number = substr($admin_number, 0, 7) . strtoupper(substr($admin_number, -1));

        include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

        // Prepare SQL query to join Profile, User_Credentials, and Role tables
        $sql = $connect->prepare("SELECT Profile.id, Profile.admin_number, Profile.has_logged_in, 
                                  User_Credentials.password, Role.name AS role
                                  FROM User_Credentials
                                  JOIN Profile ON User_Credentials.profile_id = Profile.id
                                  JOIN Role ON Profile.role_id = Role.id
                                  WHERE Profile.admin_number = ?");

        // Bind the admin number parameter
        $sql->bind_param("s", $admin_number);

        // Execute the query
        $sql->execute();
        $sql->store_result();

        // If a result is returned
        if ($sql->num_rows > 0) {
            // Bind the result to variables
            $sql->bind_result($profile_id, $retrieved_admin_number, $has_logged_in, $stored_password, $role);

            // Fetch the result
            $sql->fetch();

            // Verify the password using password_verify
            if (password_verify($mypassword, $stored_password)) {
                // Password is correct, start session and store profile details
                $_SESSION['profile_id'] = $profile_id;
                $_SESSION['role'] = $role;
                $_SESSION['admin_number'] = $retrieved_admin_number;

                // If it's the first login for a Student
                if ($has_logged_in == 0 && $role == "Student") {
                    // Call send_reset_link.php to send the reset email
                    $_POST['admin_number'] = $retrieved_admin_number; // Pass the admin number to the script
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
                throw new Exception("Admin number and password do not match.");
            }
        } else {
            // If no matching admin number is found
            throw new Exception("No user found with that admin number.");
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

// Get admin number and password from form (use POST method)
if (isset($_POST['admin_number']) && isset($_POST['password'])) {
    $admin_number = $_POST['admin_number'];
    $mypassword = $_POST['password'];

    // Call the authentication function
    authenticate($admin_number, $mypassword);
} else {
    // If the form is not submitted properly, redirect to login page
    header("Location: sites/index.php");  // Redirect to login page
    exit();
}
?>
