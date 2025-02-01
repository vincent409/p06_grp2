<?php
// Start the session
session_start();

// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to add usage logs.");
}

include 'C:/xampp/htdocs/p06_grp2/functions.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];
$success_message = '';

$csrf_token = generateCsrfToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validateCsrfToken($_POST['csrf_token']);

    $equipment_id = trim($_POST['equipment_id']);
    $log_details = trim($_POST['log_details']); // Usage or maintenance details
    $assigned_date = $_POST['assigned_date']; // Date when the equipment was assigned

    // Validate inputs
    if (empty($equipment_id) || empty($log_details) || empty($assigned_date)) {
        $inputErrors[] = "All fields are required.";
    } elseif (!preg_match("/^[a-zA-Z0-9 ]+$/", $log_details)) {
        $inputErrors[] = "Error: Log details must only contain letters and numbers.";
    } else {
        // Check if the equipment_id exists in the equipment table
        $stmt = $connect->prepare("SELECT id FROM equipment WHERE id = ?");
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $inputErrors[] = "Error: The provided Equipment ID does not exist.";
        } else {
            // Insert usage log into the database
            $insert_stmt = $connect->prepare("INSERT INTO usage_log (equipment_id, log_details, assigned_date) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iss", $equipment_id, $log_details, $assigned_date);

            if ($insert_stmt->execute()) {
                $success_message = "Usage log added successfully!";
            } else {
                $inputErrors[] = "Error: " . $connect->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// Get the equipment_id from the URL (if available)
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Usage Log</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Assignments</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="main-container">
    <h1>Add Equipment Usage Log</h1>

    <?php if (!empty($success_message)) { ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
    <ul class="error-message">
        <?php foreach ($inputErrors as $error) { ?>
            <li><?php echo $error; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>

    <form method="POST" action="add_usage_logs.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo htmlspecialchars($equipment_id); ?>" required>

        <label for="log_details">Log Details:</label>
        <textarea id="log_details" name="log_details" rows="4" required></textarea>

        <label for="assigned_date">Assigned Date:</label>
        <input type="date" id="assigned_date" name="assigned_date" required>

        <button type="submit">Submit Usage Log</button>
        <button type="button" onclick="window.location.href='edit_usage_logs.php';">View Usage Logs</button>
    </form>
</div>

</body>
</html>
