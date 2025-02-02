<?php
// Start the session
session_start();

// Check if the user has the required role which is Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect unauthorized users to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

// Include files for database connection, cookie, validation, CSRF
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';

// Manage user session cookies
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Manage user session cookies
generateCsrfToken();

// Initialize error messages and success message
$inputErrors = [];
$success_message = '';

// Handle DELETE request (only if the user is an Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id']) && $_SESSION['role'] === "Admin") {
    validateCsrfToken($_POST['csrf_token']); // Validate CSRF token for security
    $delete_id = intval($_POST['delete_id']); // Sanitize input

    if ($delete_id > 0) {
        // Prepare and execute DELTE query
        $stmt = $connect->prepare("DELETE FROM usage_log WHERE id = ?");
        $stmt->bind_param("i", $delete_id);

        $stmt->execute(); 
        $stmt->close();

        // Redirect after deletion to prevent resubmission issues
        header("Location: edit_usage_logs.php");
        exit;
    } else {
        $_SESSION['inputErrors'] = "Invalid usage log ID.";
    }
}


// Handle UPDATE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']); // Sanitize input
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
        validateCsrfToken($_POST['csrf_token']);
        // Encrypt the log details before updating
        $encrypted_log_details = aes_encrypt($new_log_details);

        // Prepared statements to prevent SQL injection
        if (empty($new_returned_date)) {
            $stmt = $connect->prepare("UPDATE usage_log SET log_details = ?, assigned_date = ?, returned_date = NULL WHERE id = ?");
            $stmt->bind_param("ssi", $encrypted_log_details, $new_assigned_date, $update_id);
        } else {
            $stmt = $connect->prepare("UPDATE usage_log SET log_details = ?, assigned_date = ?, returned_date = ? WHERE id = ?");
            $stmt->bind_param("sssi", $encrypted_log_details, $new_assigned_date, $new_returned_date, $update_id);
        }

        // Execute the query
        if ($stmt->execute()) {
            $success_message = "Usage log updated successfully!";
        } else {
            $inputErrors[] = "Error updating usage log: " . $stmt->error;
        }

        $stmt->close();
    }
}


// Fetch all usage logs, ordered by equipment_id ascending
$sql = "SELECT * FROM usage_log ORDER BY equipment_id ASC";
$result = mysqli_query($connect, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}


// Handle SEARCH request for filtering by equipment ID
$searchQuery = "";
if (isset($_GET['search'])) { // Check if the 'search' parameter is set in the GET request
    $searchQuery = trim($_GET['search']); // Retrieve and trim whitespace
    // Fix: Ensure '0' is treated as a valid input by checking explicitly for an empty string
    if ($searchQuery !== "") {
        $stmt = $connect->prepare("SELECT id, equipment_id, log_details, assigned_date, returned_date FROM usage_log WHERE equipment_id = ? ORDER BY equipment_id ASC");
        $stmt->bind_param("i", $searchQuery);
    } else {
        $stmt = $connect->prepare("SELECT id, equipment_id, log_details, assigned_date, returned_date FROM usage_log ORDER BY equipment_id ASC");
    }

    $stmt->execute(); // Execute the SQL query
    $result = $stmt->get_result(); // Retrive the result set
    $stmt->close(); // Close the prepared statement
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

        <?php if (mysqli_num_rows($result) > 0) { ?>  <!-- ✅ Check if records exist -->
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
                            <td><?php echo aes_decrypt($row['log_details']); ?></td> <!-- Decrypt log_details -->
                            <td><?php echo $row['assigned_date']; ?></td>
                            <td><?php echo $row['returned_date']; ?></td>
                            <td>
                                <form action="edit_usage_logs.php" method="POST">
                                    <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                                    <label for="log_details">Log Details:</label>
                                    <input type="text" name="log_details" value="<?php echo aes_decrypt($row['log_details']); ?>" required><br> <!-- Decrypt log_details -->

                                    <label for="assigned_date">Assigned Date:</label>
                                    <input type="date" name="assigned_date" value="<?php echo $row['assigned_date']; ?>" required><br>

                                    <label for="returned_date">Returned Date:</label>
                                    <input type="date" name="returned_date" value="<?php echo $row['returned_date']; ?>"><br>

                                    <div class="button-container">
                                        <button type="submit" name="update-button" class="update-button">Update</button>
                                        <?php if ($_SESSION['role'] === "Admin"): ?>
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <button type="submit" name="delete_id" value="<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this log?')">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php } else { ?> 
            <p>No usage logs found.</p>
        <?php } ?>
    </div>
</div>


<?php
mysqli_close($connect);
?>
