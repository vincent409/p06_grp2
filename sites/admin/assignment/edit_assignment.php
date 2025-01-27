<?php
// Include database connection
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$error_message = ''; // Initialize error message variable
$success_message = ''; // Initialize success message variable

// Handle DELETE request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']); // Sanitize delete_id to ensure it's an integer

    if ($delete_id > 0) {
        $delete_query = "DELETE FROM loan WHERE id = '$delete_id'";
        if (mysqli_query($connect, $delete_query)) {
            $success_message = "Assignment deleted successfully.";
        } else {
            $error_message = "Error: Unable to delete assignment.";
        }
    } else {
        $error_message = "Invalid delete request.";
    }
}

// Handle UPDATE request
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id']; // The update_id comes from the hidden input
    $new_email = $_POST['email'];  // Get the email from the form
    $new_equipment_id = $_POST['equipment_id'];

    // Check if the email exists in the Profile table
    $check_email_query = "SELECT * FROM profile WHERE LOWER(email) = LOWER('$new_email')";
    $check_email_result = mysqli_query($connect, $check_email_query);

    if (mysqli_num_rows($check_email_result) == 0) {
        $error_message = "Error: The entered email does not exist.";
    } else {
        // Check if the equipment_id exists in the Equipment table
        $check_equipment_query = "SELECT * FROM equipment WHERE id = '$new_equipment_id'";
        $check_equipment_result = mysqli_query($connect, $check_equipment_query);

        if (mysqli_num_rows($check_equipment_result) == 0) {
            $error_message = "Error: The entered equipment ID does not exist.";
        } else {
            // Check if the equipment_id is already assigned to another user
            $check_assignment_query = "SELECT * FROM loan WHERE equipment_id = '$new_equipment_id' AND id != '$update_id'";
            $check_assignment_result = mysqli_query($connect, $check_assignment_query);

            if (mysqli_num_rows($check_assignment_result) > 0) {
                $error_message = "Error: This equipment ID is already assigned to another user.";
            } else {
                $profile_row = mysqli_fetch_assoc($check_email_result);
                $profile_id = $profile_row['id'];

                // Update the loan table (profile_id and equipment_id)
                $update_query = "UPDATE loan SET profile_id = '$profile_id', equipment_id = '$new_equipment_id' WHERE id = '$update_id'";

                if (mysqli_query($connect, $update_query)) {
                    $success_message = "Assignment updated successfully.";
                } else {
                    $error_message = "Error: Unable to update assignment.";
                }
            }
        }
    }
}

// Fetch all assignments from the 'loan' table and get the assigned_date from 'usage_log' and status name
$query = "SELECT loan.id, loan.status_id, loan.profile_id, loan.equipment_id, usage_log.assigned_date, status.name as status_name, profile.email as profile_email
          FROM loan
          LEFT JOIN usage_log ON loan.equipment_id = usage_log.equipment_id
          LEFT JOIN status ON loan.status_id = status.id
          LEFT JOIN profile ON loan.profile_id = profile.id
          ORDER BY loan.profile_id ASC";
$result = mysqli_query($connect, $query);

// Check if there are any records
$assignments_exist = mysqli_num_rows($result) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignments</title>
    <style>
        body {
            background-color: #E5D9B6;
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            color: black;
            padding: 10px 20px;
        }

        .logout-btn button {
            background-color: #E53D29; /* Red button */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        .logout-btn button:hover {
            background-color: #CC3221; /* Darker red */
        }

        nav {
            display: flex;
            gap: 15px;
            background-color: #f4f4f4;
            padding: 10px 20px;
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        h1 {
            text-align: center; /* Centralize the heading */
            margin: 20px 0; /* Add spacing above and below */
            font-size: 2em;
            color: black;
        }

        .container {
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            width: 90%;
            max-width: 1200px;
            text-align: center; /* Center content inside the container */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
            background-color: #F9F9F9;
        }

        th {
            background-color: #F1F1F1;
        }

        td input, td button {
            width: 90%;
            max-width: 250px;
            padding: 8px;
            font-size: 0.9em;
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        td input:focus {
            border-color: #007BFF;
            outline: none;
        }

        .back-button {
            background-color: #007BFF;
            color: white;
            padding: 12px 20px;
            margin-top: 20px;
            font-size: 1.2em;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
    </div>
    <div class="logout-btn">
        <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
    </div>
</header>

<nav>
    <a href="/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
    <a href="/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="container">
    <h1>Manage Inventory Assignments</h1>

    <?php if (!$assignments_exist): ?>
        <a href="add_assignment.php" class="back-button">Back to Add Assignment</a>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Email</th>
                    <th>Equipment ID</th>
                    <th>Assignment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['status_name']; ?></td>
                    <td><?php echo $row['profile_email']; ?></td>
                    <td><?php echo $row['equipment_id']; ?></td>
                    <td><?php echo $row['assigned_date']; ?></td>
                    <td>
                        <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Hidden input to hold the update ID -->
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                            <!-- Input for email -->
                            <label for="email" style="font-size: 0.9em; margin-bottom: 5px;">Email:</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo $row['profile_email']; ?>" 
                                required 
                                style="width: 100%; max-width: none;">

                            <!-- Input for equipment ID -->
                            <label for="equipment_id" style="font-size: 0.9em; margin-bottom: 5px;">Equipment ID:</label>
                            <input 
                                type="text" 
                                name="equipment_id" 
                                value="<?php echo $row['equipment_id']; ?>" 
                                required 
                                style="width: 100%; max-width: none;">

                            <!-- Update and Delete buttons -->
                            <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 10px;">
                                <button 
                                    type="submit" 
                                    style="background-color: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">
                                    Update
                                </button>
                                <button 
                                    type="button" 
                                    onclick="if (confirm('Are you sure you want to delete this assignment?')) window.location.href='edit_assignment.php?delete_id=<?php echo $row['id']; ?>';" 
                                    style="background-color: #FF0000; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">
                                    Delete
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($success_message)): ?>
<script>
    alert("<?php echo $success_message; ?>");
</script>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<script>
    alert("<?php echo $error_message; ?>");
</script>
<?php endif; ?>

</body>
</html>
