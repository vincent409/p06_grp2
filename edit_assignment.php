<?php
// Include database connection
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize error message variable
$error_message = '';

// Handle DELETE request
if (isset($_GET['delete_id'])) {
    // Sanitize delete_id to ensure it's an integer
    $delete_id = intval($_GET['delete_id']);
    
    // Ensure delete_id is valid (greater than 0)
    if ($delete_id > 0) {
        $delete_query = "DELETE FROM loan WHERE id = '$delete_id'";

        // Perform the delete operation
        if (mysqli_query($connect, $delete_query)) {
            echo "Assignment deleted successfully.";
        } else {
            echo "Error: " . $delete_query . "<br>" . mysqli_error($connect);
        }
    } else {
        echo "Invalid delete request.";
    }
}

// Handle UPDATE request
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id']; // The update_id comes from the hidden input
    $new_email = $_POST['email'];  // Get the email from the form
    $new_equipment_id = $_POST['equipment_id'];

    // Check if the email exists in the Profile table
    $check_email_query = "SELECT * FROM Profile WHERE LOWER(email) = LOWER('$new_email')";
    $check_email_result = mysqli_query($connect, $check_email_query);
    
    if (mysqli_num_rows($check_email_result) == 0) {
        // If the email doesn't exist, set the error message
        $error_message = "Error: The entered email does not exist.";
    } else {
        // Check if the equipment_id exists in the Equipment table
        $check_equipment_query = "SELECT * FROM Equipment WHERE id = '$new_equipment_id'";
        $check_equipment_result = mysqli_query($connect, $check_equipment_query);
        
        if (mysqli_num_rows($check_equipment_result) == 0) {
            // If the equipment_id does not exist, set the error message
            $error_message = "Error: The entered equipment ID does not exist.";
        } else {
            // Check if the equipment_id is already assigned to another user
            $check_equipment_query = "SELECT * FROM loan WHERE equipment_id = '$new_equipment_id' AND id != '$update_id'";
            $check_equipment_result = mysqli_query($connect, $check_equipment_query);
            
            if (mysqli_num_rows($check_equipment_result) > 0) {
                // If the equipment_id is already assigned, set error message
                $error_message = "Error: This equipment ID is already assigned to another user.";
            } else {
                // Retrieve the profile_id from the result
                $profile_row = mysqli_fetch_assoc($check_email_result);
                $profile_id = $profile_row['id'];

                // Update the loan table (profile_id and equipment_id)
                $update_query = "UPDATE loan SET profile_id = '$profile_id', equipment_id = '$new_equipment_id' WHERE id = '$update_id'";

                if (mysqli_query($connect, $update_query)) {
                    echo "Assignment updated successfully.";
                } else {
                    echo "Error: " . $update_query . "<br>" . mysqli_error($connect);
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
if (mysqli_num_rows($result) > 0):
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignments</title>
    <style>
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h1 {
            color: black;
            background-color: white;
            padding: 20px;
            margin: 0;
            font-size: 2em;
        }

        form, table {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            box-sizing: border-box;
        }

        label {
            font-size: 1em;
            display: block;
            margin: 10px 0 5px;
        }

        input, button {
            padding: 12px;
            margin: 10px 0;
            font-size: 1.2em;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            max-width: 300px; /* Ensures inputs are not too wide */
            box-sizing: border-box; /* Prevents padding from increasing the width */
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            width: auto;
            padding: 12px 20px;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
        }

        th {
            background-color: #f2f2f2;
        }

        td input, td button {
            width: 100%;
            max-width: 250px; /* Make inputs inside the actions column fit */
            padding: 12px;
            font-size: 1em;
        }

        /* Action Buttons Styling */
        .update-button, .delete-button {
            font-size: 1em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
            cursor: pointer;
            display: inline-block;
        }

        .update-button {
            background-color: #007BFF;
            color: white;
        }

        .update-button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #FF0000;
            color: white;
        }

        .delete-button:hover {
            background-color: #cc0000;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        td a {
            text-decoration: none;
        }

        /* Back Button Styling */
        .back-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            padding: 15px 20px;
            margin-top: 20px;
            font-size: 1.2em;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        /* Button Styling for No Inventory */
        .no-inventory-button {
            background-color: #007BFF; /* Blue color */
            color: white;
            cursor: pointer;
            padding: 15px 20px;
            margin-top: 20px;
            font-size: 1.2em;
            text-decoration: none; /* Remove underline */
            display: inline-block; /* Ensures the link behaves like a block-level button */
            border-radius: 5px; /* Rounded corners to match button style */
            width: auto;
        }

        .no-inventory-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .error-message {
            color: red;
            font-size: 1.2em;
            margin: 20px;
        }
    </style>
    <script>
        // Show the error message in a pop-up
        <?php if (!empty($error_message)): ?>
            alert("<?php echo $error_message; ?>");
        <?php endif; ?>
    </script>
</head>
<body>

    <h1>Manage Inventory Assignments</h1>

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Display the assignments in a table -->
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
                    <form action="edit_assignment.php" method="POST" style="display:inline;">
                        <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                        <div class="input-container">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo $row['profile_email']; ?>" required>
                        </div>

                        <div class="input-container">
                            <label>Equipment ID:</label>
                            <input type="text" name="equipment_id" value="<?php echo $row['equipment_id']; ?>" required>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="update-button">Update</button>
                            <button type="button" class="delete-button" onclick="if (confirm('Are you sure you want to delete this assignment?')) { window.location.href='edit_assignment.php?delete_id=<?php echo $row['id']; ?>'; }">Delete</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Back button to add_assignment.php -->
    <a href="add_assignment.php" class="back-button">Back</a>

    <?php else: ?>

    <h2 style="text-align:center;">No inventory has been assigned.</h2>
    <a href="add_assignment.php" class="no-inventory-button">Back</a>

    <?php endif; ?>

</body>
</html>

<?php
// Close the connection
mysqli_close($connect);
?>
