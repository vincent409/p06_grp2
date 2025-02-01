<?php
session_start();

// Include validation and database connection files
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';

function authenticate($admin_number, $mypassword) {
    try {
        // Validate admin number
        if (empty($admin_number)) {
            throw new Exception("Admin number is required!");
        }

        // Ensure valid format: 7 digits + 1 letter
        if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", trim($admin_number))) {
            throw new Exception("Invalid ID format!");
        }

        // Convert last character to uppercase
        $admin_number = substr($admin_number, 0, 7) . strtoupper(substr($admin_number, -1));

        // Check if password is provided
        if (empty($mypassword)) {
            throw new Exception("Password is empty!");
        }

        include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';

        // Prepare SQL query to join Profile, User_Credentials, and Role tables and retrieve email
        $sql = $connect->prepare("
            SELECT Profile.id, Profile.admin_number, Profile.has_logged_in, 
                   Profile.email, User_Credentials.password, Role.name AS role
            FROM User_Credentials
            JOIN Profile ON User_Credentials.profile_id = Profile.id
            JOIN Role ON Profile.role_id = Role.id
            WHERE UPPER(Profile.admin_number) = UPPER(?)");

        if (!$sql) {
            throw new Exception("SQL Error: " . $connect->error);
        }

        // Bind the admin_number parameter
        $sql->bind_param("s", $admin_number);

        // Execute the query
        if (!$sql->execute()) {
            throw new Exception("SQL Execution Failed: " . $sql->error);
        }

        $sql->store_result();

        // If a result is returned
        if ($sql->num_rows > 0) {
            // Bind the result to variables
            $sql->bind_result($profile_id, $retrieved_admin_number, $has_logged_in, $email, $stored_password, $role);

            // Fetch the result
            $sql->fetch();

            // Verify the password using password_verify
            if (password_verify($mypassword, $stored_password)) {
                // Start session and store user details
                $_SESSION['profile_id'] = $profile_id;
                $_SESSION['role'] = $role;
                $_SESSION['admin_number'] = $admin_number;
                $email = aes_decrypt($email);
                $_SESSION['email'] = $email;

                // If it's the first login
                if ($has_logged_in == 0 && $role == "Student") {
                    $_POST['email'] = $_SESSION['email'];
                    session_destroy();
                    include 'C:/xampp/htdocs/p06_grp2/send_reset_link.php';

                    // Show alert and redirect back to login page
                    echo "<script>
                        alert('A password reset link has been sent to your email. Please reset your password.');
                        window.location.href = '/p06_grp2/logout.php';
                    </script>";
                    exit();
                }

                // Redirect based on role
                if ($role == "Student") {
                    header("Location: sites/student/student-dashboard.php");
                    exit();
                } else if ($role == "Facility Manager" || $role == "Admin") {
                    header("Location: sites/admin/admin-dashboard.php");
                    exit();
                }
            } else {
                throw new Exception("Admin number and password do not match.");
            }
        }
        else {
            throw new Exception("Admin number and password do not match.");
        }
        // Close the statement
        $sql->close();
        mysqli_close($connect);
    } catch (Exception $e) {
        // Catch any exception and pass the error message
        header("Location: sites/index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Get admin number and password from form
if (isset($_POST['admin_number']) && isset($_POST['password'])) {
    $admin_number = trim($_POST['admin_number']);
    $mypassword = trim($_POST['password']);

    // Call authentication function
    authenticate($admin_number, $mypassword);
} else {
    // Redirect if form is not submitted properly
    header("Location: sites/index.php");
    exit();
}
?>
