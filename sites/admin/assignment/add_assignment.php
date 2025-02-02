<?php
session_start();
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    header("Location: /p06_grp2/sites/index.php?error=No permission");
    exit();
}

include 'C:/xampp/htdocs/p06_grp2/functions.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$inputErrors = [];
$assignment_created = false;

// Get equipment_id from URL
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';

// Default assignment date (today's date)
$assignment_date = date('Y-m-d');


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Assignment</title>
    <link rel="stylesheet" href="/p06_grp2/assign.css">
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

<div class="add-container">
    <h2>Add New Assignment</h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <label for="admin_number">Admin Number:</label>
        <input type="text" id="admin_number" name="admin_number" required>
        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo htmlspecialchars($equipment_id); ?>" required>
        <label for="assignment_date">Assignment Date:</label>
        <input type="date" id="assignment_date" name="assignment_date" value="<?php echo htmlspecialchars($assignment_date); ?>" required>
        <button type="submit">Create Assignment</button>

        <button onclick="window.location.href='assignment.php';">Back to Equipment</button>
        <button onclick="window.location.href='edit_assignment.php';">View Assignments</button>
    </form>
</div>

<?php if ($assignment_created): ?>
<script>alert("Successfully added a new assignment!");</script>
<?php elseif (!empty($inputErrors)): ?>
<script>alert("<?php echo implode('\n', $inputErrors); ?>");</script>
<?php endif; ?>
</body>
</html>
