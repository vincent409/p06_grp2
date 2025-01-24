<?php
// Start the session
session_start();

// Check if user is logged in as Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect to login page if user is not authorized
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check the connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle DELETE request for Admin users
if (isset($_GET['delete_id']) && $_SESSION['role'] === "Admin") {
    $delete_id = $_GET['delete_id'];

    // Check if the assignment exists and the deletion is possible
    $check_status_query = "SELECT status_id FROM loan WHERE id = '$delete_id'";
    $check_status_result = mysqli_query($connect, $check_status_query);
    if ($check_status_result) {
        $status_row = mysqli_fetch_assoc($check_status_result);
        $status_id = $status_row['status_id'];

        $delete_query = "DELETE FROM loan WHERE id = '$delete_id'";
        if (mysqli_query($connect, $delete_query)) {
            echo "<div style='color:green;'>Assignment deleted successfully.</div>";
        } else {
            echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
        }
    } else {
        echo "<div style='color:red;'>Error: The assignment with the specified ID does not exist.</div>";
    }
}

// Handle UPDATE request for Admin and Facility Managers
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $new_status = $_POST['status']; // New status (Assigned, In-Use, Returned)

    // For Facility Managers, check if the status is allowed to be updated
    if ($_SESSION['role'] === "Facility Manager") {
        // Fetch current status of the equipment being updated
        $status_check_query = "SELECT status_id FROM loan WHERE id = '$update_id'";
        $status_check_result = mysqli_query($connect, $status_check_query);
        $current_status = mysqli_fetch_assoc($status_check_result)['status_id'];

        // Only allow Facility Managers to update if the current status is not "Assigned", "In-Use", or "Returned"
        if (in_array($current_status, [1, 2, 3])) {
            echo "<div style='color:red;'>Error: Cannot reassign or update status. Equipment is already 'Assigned', 'In-Use', or 'Returned'.</div>";
        } else {
            // Update the equipment status
            $update_query = "UPDATE loan SET status_id = '$new_status' WHERE id = '$update_id'";

            if (mysqli_query($connect, $update_query)) {
                echo "<div style='color:green;'>Status updated successfully.</div>";
            } else {
                echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
            }
        }
    } else {
        // If Admin, just update the status
        $update_query = "UPDATE loan SET status_id = '$new_status' WHERE id = '$update_id'";

        if (mysqli_query($connect, $update_query)) {
            echo "<div style='color:green;'>Status updated successfully.</div>";
        } else {
            echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
        }
    }
}

// Fetch all assignments from the 'loan' table, including null values for status
$query = "SELECT loan.id, loan.status_id, loan.profile_id, loan.equipment_id, 
                 IFNULL(status.name, 'NULL') as status_name, profile.email as profile_email
          FROM loan
          LEFT JOIN status ON loan.status_id = status.id
          LEFT JOIN profile ON loan.profile_id = profile.id";
$result = mysqli_query($connect, $query);

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

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
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
            max-width: 250px;
            padding: 8px;
            font-size: 0.9em;
        }

        /* Button Container */
        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        /* Update Button Styling */
        .update-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
        }

        .update-button:hover {
            background-color: #0056b3;
        }

        /* Delete Button Styling */
        .delete-button {
            background-color: #FF0000;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
        }

        .delete-button:hover {
            background-color: #cc0000;
        }

        .back-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            padding: 12px 20px;
            margin-top: 20px;
            font-size: 1.2em;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>

    <h1>Manage Inventory Assignments</h1>

    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Email</th>
                <th>Equipment ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['status_name']; ?></td>
                <td><?php echo $row['profile_email']; ?></td>
                <td><?php echo $row['equipment_id']; ?></td>
                <td>
                    <form action="status.php" method="POST" style="display:inline;">
                        <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                        <label for="status">Status:</label>
                        <select name="status" required>
                            <option value="1" <?php echo ($row['status_id'] == 1) ? 'selected' : ''; ?>>Assigned</option>
                            <option value="2" <?php echo ($row['status_id'] == 2) ? 'selected' : ''; ?>>In-Use</option>
                            <option value="3" <?php echo ($row['status_id'] == 3) ? 'selected' : ''; ?>>Returned</option>
                        </select><br>

                        <div class="button-container">
                            <button type="submit" class="update-button">Update</button>
                            <?php if ($_SESSION['role'] === "Admin"): ?>
                                <a href="status.php?delete_id=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this assignment?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="/p06_grp2/sites/admin/admin-dashboard.php" class="back-button">Back</a>

</body>
</html>

<?php endif; ?>

<?php
// Close the database connection
mysqli_close($connect);
?>
