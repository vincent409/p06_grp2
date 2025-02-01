<?php
session_start();

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$assignment_created = false; // Variable to track if the assignment was created
$error_message = ''; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Regenerate CSRF token after validation to prevent replay attacks
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Form data
    $admin_number = trim($_POST['admin_number']); // Get admin number from the form
    $equipment_id = trim($_POST['equipment_id']); // Hidden field for equipment_id

// Check if the equipment is already assigned (Modify status_id condition)
    $check_equipment_query = "SELECT * FROM loan WHERE equipment_id = ? AND status_id IS NOT NULL";
    $stmt = $connect->prepare($check_equipment_query);
    $stmt->bind_param("s", $equipment_id);
    $stmt->execute();
    $check_equipment_result = $stmt->get_result();

    if ($check_equipment_result->num_rows > 0) {
        $error_message = "Error: This equipment ID is already assigned to another user.";
    } else {

    $profile_query = "SELECT id, admin_number FROM profile WHERE admin_number = ? LIMIT 1";
    $stmt = $connect->prepare($profile_query);
    $stmt->bind_param("s", $admin_number);
    $stmt->execute();
    $profile_result = $stmt->get_result();

    if ($profile_result->num_rows > 0) {
        $profile_row = $profile_result->fetch_assoc();
        $profile_id = $profile_row['id']; // ✅ Get profile_id
        $admin_number = $profile_row['admin_number']; // ✅ Fetch Admin Number Correctly
    } else {
        $error_message = "Error: No profile found for the provided Admin Number.";
    }


    // Check if the same profile has already been assigned the same equipment
    $check_duplicate_query = "SELECT * FROM loan WHERE profile_id = ? AND equipment_id = ?";
    $stmt = $connect->prepare($check_duplicate_query);
    $stmt->bind_param("ss", $profile_id, $equipment_id);
    $stmt->execute();
    $check_duplicate_result = $stmt->get_result();

    if ($check_duplicate_result->num_rows > 0) {
        $error_message = "Error: This profile already has this equipment assigned.";
    } else {
        $status_id = 1; // Default status_id for "Assigned"

        // Insert into loan table
        $insert_query = "INSERT INTO loan (profile_id, equipment_id, status_id) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($insert_query);
        $stmt->bind_param("ssi", $profile_id, $equipment_id, $status_id);

        if ($stmt->execute()) {
            $assignment_created = true;
        } else {
            $error_message = "Error: " . $stmt->error;
            }
        }
    }
}
// Get the equipment_id from the URL or initialize it
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Assignment</title>
    <style>
        body {
            background-color: #E5D9B6; /* Beige background */
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
            text-align: center; /* Center-aligns content */
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

        .logout-btn button {
            padding: 8px 12px;
            background-color: #E53D29;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
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

        input, button {
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Assignments</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="container">
    <h1>Add New Assignment</h1>
    <form method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <!-- Hidden Equipment ID -->
        <input type="hidden" id="equipment_id" name="equipment_id" value="<?php echo htmlspecialchars($equipment_id); ?>">

        <label for="admin_number">Admin Number:</label>
        <input type="text" id="admin_number" name="admin_number" required>

        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo $equipment_id; ?>" required>
        <button type="button" class="view-button" onclick="window.location.href='edit_assignment.php';">View Assignments</button>

        <!-- Go back to admin.php -->
        <button type="button" class="back-button" onclick="window.location.href='assignment.php';">Back to Equipment</button>

        <button type="submit">Create Assignment</button>
    </form>
</div>

<?php if ($assignment_created): ?>
<script>
    alert("Assignment has been created successfully!");
</script>
<?php elseif (!empty($error_message)): ?>
<script>
    alert("<?php echo $error_message; ?>");
</script>
<?php endif; ?>
</body>
</html>

