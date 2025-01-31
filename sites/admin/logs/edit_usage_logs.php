<?php
// Start the session
session_start();

// Check user role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Initialize variables for messages
$error_message = '';
$success_message = '';

// Handle DELETE request (only if the user is an Admin)
if (isset($_POST['delete_id']) && $_SESSION['role'] === "Admin") {
    $delete_id = $_POST['delete_id'];
    $delete_query = "DELETE FROM usage_log WHERE id = '$delete_id'";

    if (mysqli_query($connect, $delete_query)) {
        $success_message = "Usage log deleted successfully.";
    } else {
        $error_message = "Error: " . mysqli_error($connect);
    }
} elseif (isset($_GET['delete_id']) && $_SESSION['role'] === "Facility Manager") {
    // If the user is a Facility Manager, do not allow deletion
    $error_message = "Error: You do not have permission to delete usage logs.";
}


// Handle UPDATE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $new_log_details = trim($_POST['log_details']); // Trim extra spaces
    $new_assigned_date = $_POST['assigned_date'];
    $new_returned_date = $_POST['returned_date'];

    // **Log Details Validation** (Ensure only alphanumeric and spaces)
    if (!preg_match($alphanumeric_pattern, $new_log_details)) {
        $error_message = "Error: Log details must only contain letters and numbers.";
    }

    // **Ensure returned date is not before assigned date**
    if (!empty($new_returned_date) && $new_returned_date < $new_assigned_date) {
        $error_message = "Error: Returned date cannot be earlier than assigned date.";
    }

    // **Only proceed if no validation errors**
    if (empty($error_message)) {
        if (empty($new_returned_date)) {
            // Handle NULL returned_date
            $update_query = "UPDATE usage_log SET log_details = ?, assigned_date = ?, returned_date = NULL WHERE id = ?";
            $stmt = $connect->prepare($update_query);
            $stmt->bind_param("ssi", $new_log_details, $new_assigned_date, $update_id);
        } else {
            $update_query = "UPDATE usage_log SET log_details = ?, assigned_date = ?, returned_date = ? WHERE id = ?";
            $stmt = $connect->prepare($update_query);
            $stmt->bind_param("sssi", $new_log_details, $new_assigned_date, $new_returned_date, $update_id);
        }

        if ($stmt->execute()) {
            $success_message = "Usage log updated successfully!";
        } else {
            $error_message = "Error updating usage log: " . $stmt->error;
        }
        $stmt->close();
    }
}


// Fetch all usage logs from the database
$sql = "SELECT id, equipment_id, log_details, assigned_date, returned_date FROM usage_log";
$result = mysqli_query($connect, $sql);

// Check if query executed successfully
if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Usage Logs</title>
    <style>
        body {
            background-color: #E5D9B6; /* Beige background */
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

        .logout-btn button {
            padding: 8px 12px;
            background-color: #E53D29; /* Red button */
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .logout-btn button:hover {
            background-color: #E03C00; /* Darker red on hover */
        }

        .container {
            background-color: white; /* White container */
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            border-radius: 8px; /* Rounded corners */
            padding: 20px; /* Space inside container */
            margin: 20px auto; /* Space outside container */
            width: 90%; /* Responsive container width */
            max-width: 1200px; /* Max width for large screens */
            position: relative; /* To position child elements */
        }

        .container-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .container-header h1 {
            font-size: 1.8em;
            margin: 0;
            text-align: left; /* Align Manage Usage Logs to the left */
        }

        .enter-logs-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            padding: 10px 20px;
            font-size: 1em;
            text-decoration: none;
            border-radius: 5px;
        }

        .enter-logs-button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
            background-color: #F9F9F9; /* Light background for cells */
        }

        th {
            background-color: #F1F1F1; /* Slightly darker for header */
        }

        td input {
            width: 100%;
            max-width: 250px;
            padding: 8px;
            font-size: 0.9em;
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        td input:focus {
            border-color: #007BFF; /* Highlight on focus */
            outline: none;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .update-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
            border: none;
            border-radius: 4px;
        }

        .update-button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #FF0000;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
        }

        .delete-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
    </div>
    <div class="dashboard-title">Dashboard</div>
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
    <div class="container-header">
        <h1>Manage Usage Logs</h1>
        <a href="add_usage_logs.php" class="enter-logs-button">Enter Usage Logs</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Log Details</th>
                <th>Assigned Date</th>
                <th>Returned Date</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['equipment_id']; ?></td>
                <td><?php echo $row['log_details']; ?></td>
                <td><?php echo $row['assigned_date']; ?></td>
                <td><?php echo $row['returned_date']; ?></td>
                <td>
                    <form action="edit_usage_logs.php" method="POST" style="display:inline;">
                        <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                        <label for="log_details">Log Details:</label>
                        <input type="text" name="log_details" value="<?php echo $row['log_details']; ?>" required><br>

                        <label for="assigned_date">Assigned Date:</label>
                        <input type="date" name="assigned_date" value="<?php echo $row['assigned_date']; ?>" required><br>

                        <label for="returned_date">Returned Date:</label>
                        <input type="date" name="returned_date" value="<?php echo $row['returned_date']; ?>"><br>

                        <form action="edit_usage_logs.php" method="POST" class="button-container">
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="update-button">Update</button>

                            <?php if ($_SESSION['role'] === "Admin"): ?>
                                <button type="submit" name="delete_id" value="<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this log?')"> Delete</button>
                            <?php endif; ?>
                        </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>


<!-- Display success or error messages as popups -->
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

<?php
// Close the database connection
mysqli_close($connect);
?>
