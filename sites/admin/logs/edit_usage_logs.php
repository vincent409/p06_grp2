<?php
// Start the session
session_start();

// Check user role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /xampp/p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check the connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize error message variable
$error_message = '';

// Handle DELETE request (only if the user is an Admin)
if (isset($_GET['delete_id']) && $_SESSION['role'] === "Admin") {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM usage_log WHERE id = '$delete_id'";

    if (mysqli_query($connect, $delete_query)) {
        echo "<div style='color:green;'>Usage log deleted successfully.</div>";
    } else {
        echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
    }
} elseif (isset($_GET['delete_id']) && $_SESSION['role'] === "Facility Manager") {
    // If the user is a Facility Manager, do not allow deletion
    echo "<div style='color:red;'>Error: You do not have permission to delete usage logs.</div>";
}

// Handle UPDATE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $new_log_details = $_POST['log_details']; // New details
    $new_assigned_date = $_POST['assigned_date'];
    $new_returned_date = $_POST['returned_date'];

    // Update the usage log details
    $update_query = "UPDATE usage_log SET log_details = '$new_log_details', assigned_date = '$new_assigned_date', returned_date = '$new_returned_date' WHERE id = '$update_id'";

    if (mysqli_query($connect, $update_query)) {
        echo "<div style='color:green;'>Usage log updated successfully!</div>";
    } else {
        echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
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
    <title>Edit Usage Logs</title>
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
            font-size: 0.9em; /* Smaller font size */
        }

        /* Button Container */
        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px; /* Add some space between buttons */
            margin-top: 10px;
        }

        /* Update Button Styling */
        .update-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%; /* Ensures both buttons have the same width */
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
            text-decoration: none;
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

    </style>
</head>
<body>
<header>
        <div class="logo">
            <img src="/xampp/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='/xampp/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/xampp/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
        <a href="/xampp/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
        <a href="/xampp/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/xampp/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/xampp/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    </nav>
    <h1>Manage Usage Logs</h1>

    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Log Details</th>
                <th>Assigned Date</th>
                <th>Returned Date</th>
                <th>Actions</th>
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
                    <!-- Update Form -->
                    <form action="edit_usage_logs.php" method="POST" style="display:inline;">
                        <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                        <label for="log_details">Log Details:</label>
                        <input type="text" name="log_details" value="<?php echo $row['log_details']; ?>" required><br>

                        <label for="assigned_date">Assigned Date:</label>
                        <input type="date" name="assigned_date" value="<?php echo $row['assigned_date']; ?>" required><br>

                        <label for="returned_date">Returned Date:</label>
                        <input type="date" name="returned_date" value="<?php echo $row['returned_date']; ?>"><br>

                        <!-- Button Container for Update and Delete -->
                        <div class="button-container">
                            <button type="submit" class="update-button">Update</button>
                            <?php if ($_SESSION['role'] === "Admin"): ?>
                                <a href="edit_usage_logs.php?delete_id=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this log?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Back button to admin.php -->
    <a href="add_usage_logs.php" class="back-button">Back</a>

</body>
</html>

<?php
// Close the database connection
mysqli_close($connect);
?>
