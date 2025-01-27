<?php
session_start();
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to edit or delete profiles.");
}
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Fetch the profile to edit
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT id, name, email, phone_number, department FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $email, $phone_number, $department);
    $stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];

    $update_sql = "UPDATE Profile SET name = ?, email = ?, phone_number = ?, department = ? WHERE id = ?";
    $stmt = $connect->prepare($update_sql);
    $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $id);
    $stmt->execute();
    
    // Redirect after successful update
    header("Location: manage_profile.php?message=updated");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    // Ensure only Admins can delete profiles
    if ($_SESSION['role'] != 'Admin') {
        die("You do not have permission to delete profiles.");
    }

    $id = $_POST['id'];
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Redirect after successful deletion
        header("Location: manage_profile.php?message=deleted");
        exit;
    } else {
        echo "Error deleting profile: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        .btn-delete {
            background-color: red;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }
        .btn-delete:hover {
            background-color: darkred;
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
    </nav>
    <h1>Edit Profile</h1>
    <form action="edit_profile.php" method="POST">
        <!-- Pass the ID as a hidden field -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required><br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>"><br><br>

        <label for="department">Department:</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" required><br><br>

        <input type="submit" name="update" value="Update Profile">
    </form>

    <!-- Only show the delete button if the user is an Admin -->
    <?php if ($_SESSION['role'] == 'Admin') { ?>
    <form action="edit_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this profile?');">
        <!-- Pass the ID as a hidden field -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="delete" value="1">
        <input type="submit" value="Delete Profile" class="btn-delete">
    </form>
    <?php } ?>
</body>
</html>

