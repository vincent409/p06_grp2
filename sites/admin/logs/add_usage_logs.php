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
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Initialize variables for messages
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = trim($_POST['equipment_id']);
    $log_details = trim($_POST['log_details']); // Usage or maintenance details
    $assigned_date = $_POST['assigned_date']; // Date when the equipment was assigned

    // Validate inputs
    if (empty($equipment_id) || empty($log_details) || empty($assigned_date)) {
        $error_message = "All fields are required.";
    } else {
        // Check if the equipment_id exists in the equipment table
        $check_equipment_query = "SELECT * FROM equipment WHERE id = '$equipment_id'";
        $check_equipment_result = mysqli_query($connect, $check_equipment_query);

        if (mysqli_num_rows($check_equipment_result) == 0) {
            $error_message = "Error: The provided equipment ID does not exist.";
        } else {
            // Check if the equipment_id already exists in the usage_log table
            $check_duplicate_query = "SELECT * FROM usage_log WHERE equipment_id = '$equipment_id'";
            $check_duplicate_result = mysqli_query($connect, $check_duplicate_query);

            if (mysqli_num_rows($check_duplicate_result) > 0) {
                $error_message = "Error: A usage log for this equipment ID already exists.";
            } else {
                // Insert usage log into the database
                $insert_query = "INSERT INTO usage_log (equipment_id, log_details, assigned_date) 
                                 VALUES ('$equipment_id', '$log_details', '$assigned_date')";

                if (mysqli_query($connect, $insert_query)) {
                    $success_message = "Usage log added successfully!";
                } else {
                    $error_message = "Error: " . mysqli_error($connect);
                }
            }
        }
    }
}

// Get the equipment_id from the URL (if available)
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';  // Use an empty string if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Usage Logs</title>
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
            background-color: #E53D29;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .logout-btn button:hover {
            background-color: #E03C00;
        }

        h1 {
            text-align: center;
            margin: 20px auto;
            font-size: 1.8em;
            color: black;
        }

        .container {
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            width: 90%;
            max-width: 600px;
            text-align: left;
        }

        form {
            text-align: left;
        }

        label {
            font-size: 1em;
            display: block;
            margin: 10px 0 5px;
        }

        input, textarea, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button, .view-button {
            background-color: #007BFF;
            border: none;
            cursor: pointer;
            font-size: 1em;
            padding: 12px 20px;
            margin-top: 10px;
            width: 100%;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .back-button:hover, .view-button:hover {
            background-color: #0056b3;
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
    <h1>Enter Equipment Usage Log</h1>
    <form method="POST">
        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo $equipment_id; ?>" required>

        <label for="log_details">Log Details:</label>
        <textarea id="log_details" name="log_details" rows="4" required></textarea>

        <label for="assigned_date">Assigned Date:</label>
        <input type="date" id="assigned_date" name="assigned_date" required>

        <button type="submit">Submit Usage Log</button>

        <!-- View Usage Logs Button -->
        <button type="button" class="view-button" onclick="window.location.href='edit_usage_logs.php';">View/Edit</button>

        <!-- Go back to admin.php -->
        <button type="button" class="back-button" onclick="window.location.href='/p06_grp2/sites/admin/admin-dashboard.php';">Back to Admin</button>
    </form>
</div>

<!-- Display success or error messages using JavaScript -->
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
