<?php
// Start session (optional, depending on your application)
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

// Check if 'id' is passed in the URL
if (!isset($_GET['id'])) {
    die("No equipment ID specified.");
}
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");


// Get the equipment ID from the URL parameter
$equipment_id = $_GET['id'];

// Establish a database connection
$connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to database");

// Fetch the equipment data from the database based on the ID using prepared statement
$stmt = $connect->prepare("SELECT * FROM Equipment WHERE id = ?");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Equipment not found.");
}

// Fetch the row of equipment data
$equipment = $result->fetch_assoc();

// Check if form is submitted to update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Handle deletion of equipment
        $delete_stmt = $connect->prepare("DELETE FROM Equipment WHERE id = ?");
        $delete_stmt->bind_param("i", $equipment_id);

        if ($delete_stmt->execute()) {
            // Redirect to equipment list with a success message
            header("Location: equipment.php?deleted=1");
            exit();
        } else {
            echo "<p>Error deleting equipment: " . mysqli_error($connect) . "</p>";
        }
    } else {
        // Handle update of equipment data
        $name = $_POST['name'];
        $type = $_POST['type'];
        $purchase_date = $_POST['purchase_date'];
        $model_number = $_POST['model_number'];

        $update_stmt = $connect->prepare("UPDATE Equipment SET name = ?, type = ?, purchase_date = ?, model_number = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $name, $type, $purchase_date, $model_number, $equipment_id);

        if ($update_stmt->execute()) {
            echo "<p>Equipment updated successfully!</p>";
        } else {
            echo "<p>Error updating equipment: " . mysqli_error($connect) . "</p>";
        }
    }
}

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Equipment</title>
    <style>
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
    <h1>Update Equipment</h1>

    <form action="update-equipment.php?id=<?php echo $equipment['id']; ?>" method="POST">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required><br><br>

        <label for="type">Type:</label>
        <input type="text" name="type" value="<?php echo htmlspecialchars($equipment['type']); ?>" required><br><br>

        <label for="purchase_date">Purchase Date:</label>
        <input type="date" name="purchase_date" value="<?php echo $equipment['purchase_date']; ?>" required><br><br>

        <label for="model_number">Model Number:</label>
        <input type="text" name="model_number" value="<?php echo htmlspecialchars($equipment['model_number']); ?>" required><br><br>

        <input type="submit" value="Update Equipment">

        <?php if ($_SESSION['role'] === "Admin") { ?>
            <br><br>
            <button type="submit" name="delete" style="background-color: red; color: white;">Delete Equipment</button>
        <?php } ?>
    </form>

    <br><br>
    <a href="equipment.php">Back to Equipment List</a>
</body>
</html>
