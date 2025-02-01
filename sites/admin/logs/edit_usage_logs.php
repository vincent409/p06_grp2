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
include 'C:/xampp/htdocs/p06_grp2/functions.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

generateCsrfToken();
$inputErrors = [];
$success_message = '';

// Handle DELETE request (only if the user is an Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id']) && $_SESSION['role'] === "Admin") {
    $delete_id = $_POST['delete_id'];
    $delete_query = "DELETE FROM usage_log WHERE id = '$delete_id'";

    if (mysqli_query($connect, $delete_query)) {
        $success_message = "Usage log deleted successfully.";
    } else {
        $inputErrors[] = "Error: " . mysqli_error($connect);
    }
}

// Handle UPDATE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    validateCsrfToken($_POST['csrf_token'], 'equipment.php');
    $update_id = $_POST['update_id'];
    $new_log_details = trim($_POST['log_details']);
    $new_assigned_date = $_POST['assigned_date'];
    $new_returned_date = $_POST['returned_date'];

    // Validation
    if (!preg_match("/^[a-zA-Z0-9 ]+$/", $new_log_details)) {
        $inputErrors[] = "Error: Log details must only contain letters and numbers.";
    }
    if (!empty($new_returned_date) && $new_returned_date < $new_assigned_date) {
        $inputErrors[] = "Error: Returned date cannot be earlier than assigned date.";
    }

    // Only proceed if no validation errors
    if (empty($inputErrors)) {
        $update_query = empty($new_returned_date) 
            ? "UPDATE usage_log SET log_details = '$new_log_details', assigned_date = '$new_assigned_date', returned_date = NULL WHERE id = '$update_id'"
            : "UPDATE usage_log SET log_details = '$new_log_details', assigned_date = '$new_assigned_date', returned_date = '$new_returned_date' WHERE id = '$update_id'";

        if (mysqli_query($connect, $update_query)) {
            $success_message = "Usage log updated successfully!";
        } else {
            $inputErrors[] = "Error updating usage log: " . mysqli_error($connect);
        }
    }
}

// Fetch all usage logs
$sql = "SELECT * FROM usage_log ORDER BY equipment_id ASC";
$result = mysqli_query($connect, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}

// Handle SEARCH request
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    $stmt = empty($searchQuery) 
        ? $connect->prepare("SELECT id, equipment_id, log_details, assigned_date, returned_date FROM usage_log ORDER BY equipment_id ASC")
        : $connect->prepare("SELECT id, equipment_id, log_details, assigned_date, returned_date FROM usage_log WHERE equipment_id = ? ORDER BY equipment_id ASC");
    
    if (!empty($searchQuery)) {
        $stmt->bind_param("i", $searchQuery);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Usage Logs</title>
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

<div class="container">
    <div class="box">
        <div class="container-flex">
            <h1>Manage Usage Logs</h1>
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
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search Equipment ID" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    
    <div class="left-content">
        <a href="add_usage_logs.php" class="enter-logs-button">Add Usage Logs</a>
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
                        <form action="edit_usage_logs.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                            <label for="log_details">Log Details:</label>
                            <input type="text" name="log_details" value="<?php echo $row['log_details']; ?>" required><br>

                            <label for="assigned_date">Assigned Date:</label>
                            <input type="date" name="assigned_date" value="<?php echo $row['assigned_date']; ?>" required><br>

                            <label for="returned_date">Returned Date:</label>
                            <input type="date" name="returned_date" value="<?php echo $row['returned_date']; ?>"><br>

                            <div class="button-container">
                                <button type="submit" name="update-button" class="update-button">Update</button>
                                <?php if ($_SESSION['role'] === "Admin"): ?>
                                    <button type="submit" name="delete_id" value="<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this log?')">Delete</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($connect);
?>
