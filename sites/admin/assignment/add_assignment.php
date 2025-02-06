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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script>
                alert('CSRF validation failed. Redirecting to login page.');
                window.location.href='/p06_grp2/sites/index.php';
              </script>";
        exit();
    }
// Default assignment date (today's date)
$assignment_date = date('Y-m-d');

    // Collect form data
    $admin_number = trim($_POST['admin_number']);
    $equipment_id = trim($_POST['equipment_id']);

    // Validate admin number (should be 7 digits followed by 1 letter like "1234567A")
    if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", $admin_number)) {
        $inputErrors[] = "Admin number must be 7 digits followed by 1 letter (e.g., 1234567A).";
    }

    // Validate equipment ID (should contain only numbers)
    if (!preg_match("/^[0-9]+$/", $equipment_id)) {
        $inputErrors[] = "Equipment ID must contain only numbers.";
    }

    // Check if admin number exists
    $check_admin_sql = "SELECT id FROM Profile WHERE admin_number = ?";
    $stmt = $connect->prepare($check_admin_sql);
    $stmt->bind_param("s", $admin_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $inputErrors[] = "Admin Number does not exist.";
    } else {
        $admin_row = $result->fetch_assoc();
        $profile_id = $admin_row['id']; // Fetch profile ID for loan assignment
    }
    $stmt->close();

    // Check if equipment ID exists
    $check_equipment_sql = "SELECT id FROM Equipment WHERE id = ?";
    $stmt = $connect->prepare($check_equipment_sql);
    $stmt->bind_param("s", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $inputErrors[] = "Equipment ID does not exist.";
    }
    $stmt->close();

    // Check if the equipment is already assigned
    $check_equipment_query = "SELECT * FROM Loan WHERE equipment_id = ? AND status_id IS NOT NULL";
    $stmt = $connect->prepare($check_equipment_query);
    $stmt->bind_param("s", $equipment_id);
    $stmt->execute();
    $check_equipment_result = $stmt->get_result();

    if ($check_equipment_result->num_rows > 0) {
        $inputErrors[] = "This equipment ID is already assigned to another user.";
    }
    $stmt->close();

    // Check for duplicate assignment
    $check_duplicate_query = "SELECT * FROM Loan WHERE profile_id = ? AND equipment_id = ?";
    $stmt = $connect->prepare($check_duplicate_query);
    $stmt->bind_param("ss", $profile_id, $equipment_id);
    $stmt->execute();
    $check_duplicate_result = $stmt->get_result();

    if ($check_duplicate_result->num_rows > 0) {
        $inputErrors[] = "This profile already has this equipment assigned.";
    }
    $stmt->close();

    // If no errors, insert into the database
    if (empty($inputErrors)) {
        $status_id = 1; // Default status_id for "Assigned"

        $insert_query = "INSERT INTO Loan (profile_id, equipment_id, status_id) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($insert_query);
        $stmt->bind_param("ssi", $profile_id, $equipment_id, $status_id);

        if ($stmt->execute()) {
            $assignment_created = true;
        } else {
            $inputErrors[] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
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
