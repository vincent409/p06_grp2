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

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script>
            alert('CSRF validation failed. Redirecting to login page.');
            window.location.href='/p06_grp2/sites/index.php';
        </script>";
        exit();
    }

    $delete_id = intval($_POST['delete_id']);

    if ($delete_id > 0) {
        // Fetch the equipment_id linked to this loan record before deleting the assignment
        $fetch_equipment_query = "SELECT equipment_id FROM loan WHERE id = ?";
        $stmt = $connect->prepare($fetch_equipment_query);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipment_id = null;

        if ($row = $result->fetch_assoc()) {
            $equipment_id = $row['equipment_id'];
        }
        $stmt->close();

        // If equipment_id exists, delete the corresponding usage log first
        if ($equipment_id !== null) {
            $delete_log_query = "DELETE FROM usage_log WHERE equipment_id = ?";
            $stmt_log = $connect->prepare($delete_log_query);
            $stmt_log->bind_param("i", $equipment_id);
            $stmt_log->execute();
            $stmt_log->close();
        }

        // Delete the assignment from loan table
        $delete_query = "DELETE FROM loan WHERE id = ?";
        $stmt = $connect->prepare($delete_query);
        $stmt->bind_param("i", $delete_id);

        if ($stmt->execute()) {
            echo "<script>
                alert('Assignment and related usage log deleted successfully.');
                window.location.href = 'edit_assignment.php';
            </script>";
            exit();
        } else {
            echo "<script>
                alert('Error: Unable to delete assignment.');
                window.location.href = 'edit_assignment.php';
            </script>";
            exit();
        }
    }
}

// Handle UPDATE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script>
            alert('CSRF validation failed. Redirecting to login page.');
            window.location.href='/p06_grp2/sites/index.php';
        </script>";
        exit();
    }

    $update_id = intval($_POST['update_id']);
    $new_admin_number = trim($_POST['admin_number']);
    $new_equipment_id = intval($_POST['equipment_id']); // Convert to integer for safety
    $new_assignment_date = $_POST['assignment_date'];  // Get the new assignment date from the form

    // Validate admin number format
    if (!preg_match("/^[0-9]{7}[a-zA-Z]$/", $new_admin_number)) {
        echo "<script>
            alert('Error: Admin number must be 7 digits followed by 1 letter (e.g., 1234567A).');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    }

    // Validate equipment ID format
    if (!preg_match("/^[0-9]+$/", $new_equipment_id)) {
        echo "<script>
            alert('Error: Equipment ID must contain only numbers.');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    }

    // Check if admin number exists
    $check_admin_query = "SELECT id FROM profile WHERE admin_number = ?";
    $stmt = $connect->prepare($check_admin_query);
    $stmt->bind_param("s", $new_admin_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "<script>
            alert('Error: The entered admin number does not exist.');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    } else {
        $profile_row = $result->fetch_assoc();
        $profile_id = $profile_row['id'];
    }
    $stmt->close();

    // Check if equipment ID exists
    $check_equipment_query = "SELECT id FROM equipment WHERE id = ?";
    $stmt = $connect->prepare($check_equipment_query);
    $stmt->bind_param("i", $new_equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "<script>
            alert('Error: The entered equipment ID does not exist.');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    }
    $stmt->close();

    // Fetch the current equipment_id before updating
    $fetch_old_equipment_query = "SELECT equipment_id FROM loan WHERE id = ?";
    $stmt = $connect->prepare($fetch_old_equipment_query);
    $stmt->bind_param("i", $update_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_equipment_id = null;

    if ($row = $result->fetch_assoc()) {
        $old_equipment_id = $row['equipment_id'];
    }
    $stmt->close();

    // Proceed with updating the loan table
    $update_query = "UPDATE loan SET profile_id = ?, equipment_id = ? WHERE id = ?";
    $stmt = $connect->prepare($update_query);
    $stmt->bind_param("iii", $profile_id, $new_equipment_id, $update_id);

    if ($stmt->execute()) {
        // ✅ Delete the old usage log if equipment_id has changed
        if ($old_equipment_id !== null && $old_equipment_id !== $new_equipment_id) {
            $delete_log_query = "DELETE FROM usage_log WHERE equipment_id = ?";
            $stmt_log = $connect->prepare($delete_log_query);
            $stmt_log->bind_param("i", $old_equipment_id);
            $stmt_log->execute();
            $stmt_log->close();

            // ✅ Insert a new usage log entry for the updated equipment_id
            $insert_log_query = "INSERT INTO usage_log (equipment_id, assigned_date, log_details) VALUES (?, NOW(), 'Updated assignment')";
            $stmt_log = $connect->prepare($insert_log_query);
            $stmt_log->bind_param("i", $new_equipment_id);
            $stmt_log->execute();
            $stmt_log->close();
        }

        echo "<script>
            alert('Assignment updated successfully.');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Error: Unable to update assignment.');
            window.location.href = 'edit_assignment.php';
        </script>";
        exit();
    }
}

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Start base query
$query = "SELECT loan.id, loan.status_id, loan.profile_id, loan.equipment_id, 
                 usage_log.assigned_date, status.name AS status_name, profile.admin_number
          FROM loan
          LEFT JOIN usage_log ON loan.equipment_id = usage_log.equipment_id
          LEFT JOIN status ON loan.status_id = status.id
          LEFT JOIN profile ON loan.profile_id = profile.id";

// If searching, filter by `equipment_id`
if (!empty($searchQuery)) {
    $query .= " WHERE loan.equipment_id = ?";
}

// Prepare and execute query
$stmt = $connect->prepare($query);

if (!empty($searchQuery)) {
    $stmt->bind_param("i", $searchQuery);
}

$stmt->execute();
$result = $stmt->get_result();
$assignments_exist = mysqli_num_rows($result) > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignments</title>
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

<div class="container">
    <div class="header-container">
        <div>
            <h1>Edit Assignments</h1>

            <!-- Back Button Below H1 -->
            <div class="centered-button">
                <a href="assignment.php" class="back-button">Back</a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by Equipment ID..." 
                    value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" 
                    pattern="[0-9]*" 
                    title="Only numbers are allowed"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <?php if (!$assignments_exist): ?>
        <p>No assignments found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Admin Number</th>
                    <th>Equipment ID</th>
                    <th>Assignment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['status_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['admin_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['equipment_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['assigned_date']); ?></td>
                    <td>
                        <form method="POST" class="form-container">
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <label for="admin_number">Admin Number:</label>
                            <input type="text" name="admin_number" value="<?php echo htmlspecialchars($row['admin_number']); ?>" required>

                            <label for="equipment_id">Equipment ID:</label>
                            <input type="text" name="equipment_id" value="<?php echo htmlspecialchars($row['equipment_id']); ?>" required>

                            <div class="btn-group">
                                <button type="submit" name="update" class="btn-update">Update</button>
                                <button type="submit" name="delete_id" value="<?php echo $row['id']; ?>" 
                                        onclick="return confirm('Are you sure you want to delete this assignment?');" 
                                        class="btn-delete">
                                    Delete
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($success_message)): ?>
<script>alert("<?php echo $success_message; ?>");</script>
<?php endif; ?>
</body>
</html>


