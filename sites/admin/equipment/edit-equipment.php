<?php
session_start();
#checking for the user's role and sending them to the home page if they are not an admin or facility manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}
#displays warning message if id is not set
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    die("No equipment ID provided.");
}
#inculding funtions from other files
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");
#generating CSRF token
generateCsrfToken();
#takes the hidden field of id in form and stores it as equipment id
$equipment_id = $_POST['id'];

#calling all the equipment's infomation from database
$stmt = $connect->prepare("SELECT * FROM Equipment WHERE id = ?");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Equipment not found.");
}
#setting varibles
$equipment = $result->fetch_assoc();
$inputErrors = [];
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    #creating delete feature
    if (isset($_POST['delete'])) {
        validateCsrfToken($_POST['csrf_token']);
        try {
            #ensures only admins can use this feature
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== "Admin") {
                throw new Exception("You do not have permissons to perform this action");
            }
            #check if equipment is currently assigned
            $checkAssignmentStmt = $connect->prepare("SELECT COUNT(*) AS count FROM Loan WHERE equipment_id = ? AND status_id = (SELECT id FROM Status WHERE name = 'Assigned')");
            $checkAssignmentStmt->bind_param("i", $equipment_id);
            $checkAssignmentStmt->execute();
            $assignmentResult = $checkAssignmentStmt->get_result();
            $assignmentData = $assignmentResult->fetch_assoc();
        
            #prevents deletion is assigned
            if ($assignmentData['count'] > 0) {
                throw new Exception("Equipment cannot be deleted because it is currently assigned.");
            }

            #Proceed with deletion
            $deleteStmt = $connect->prepare("DELETE FROM Equipment WHERE id = ?");
            $deleteStmt->bind_param("i", $equipment_id);
            $deleteStmt->execute();
            #redirect user to equipments page with success message
            header("Location: equipment.php?deleted=1");
            exit();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    #creating update feature
    } elseif (isset($_POST['update'])) {
        #vaildatation for all user inputs
        validateCsrfToken($_POST['csrf_token']);
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $purchase_date = trim($_POST['purchase_date']);
        $model_number = trim($_POST['model_number']);

        if (!preg_match($alphanumeric_pattern, $name)) {
            $inputErrors[] = "Equipment name must contain only alphanumeric characters and spaces.";
        }

        if (!preg_match($alphabet_pattern, $type)) {
            $inputErrors[] = "Equipment type must contain only letters and spaces.";
        }

        if (!preg_match($model_number_pattern, $model_number)) {
            $inputErrors[] = "Model number must be alphanumeric, with dashes or underscores allowed.";
        }

        if (validateDate($purchase_date) !== true) {
            $inputErrors[] = validateDate($purchase_date);
        }

        if (empty($inputErrors)) {
            $updateStmt = $connect->prepare("UPDATE Equipment SET name = ?, type = ?, purchase_date = ?, model_number = ? WHERE id = ?");
            $updateStmt->bind_param("ssssi", $name, $type, $purchase_date, $model_number, $equipment_id);
            $updateStmt->execute();

            $successMessage = "Equipment updated successfully!";
            $stmt = $connect->prepare("SELECT * FROM Equipment WHERE id = ?");
            $stmt->bind_param("i", $equipment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $equipment = $result->fetch_assoc();
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
    <link rel="stylesheet" href="/p06_grp2/admin.css">
</head>
<body>
<!--Default header for all admin pages-->
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
    <h1>Update Equipment</h1>
    
    <?php if (!empty($successMessage)) { ?>
        <p style="color: green; font-weight: bold;"><?php echo $successMessage; ?></p>
    <?php } ?>

    <?php if (!empty($errorMessage)) { ?>
        <p style="color: red; font-weight: bold;"><?php echo $errorMessage; ?></p>
    <?php } ?>

    <?php if (!empty($inputErrors)) { ?>
        <ul style="color: red; font-weight: bold;">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>
    <!--Dynamically calls all equipment details into their respective fields-->
    <form action="edit-equipment.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipment['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required><br><br>

        <label for="type">Type:</label>
        <input type="text" name="type" value="<?php echo htmlspecialchars($equipment['type']); ?>" required><br><br>

        <label for="purchase_date">Purchase Date:</label>
        <input type="date" name="purchase_date" value="<?php echo $equipment['purchase_date']; ?>" required><br><br>

        <label for="model_number">Model Number:</label>
        <input type="text" name="model_number" value="<?php echo htmlspecialchars($equipment['model_number']); ?>" required><br><br>

        <div>
            <button type="submit" name="update">Update Equipment</button>
        </div>
        <div>
            <!--checks if user is an admin before displaying delete button-->
            <?php if ($_SESSION['role'] === "Admin") { ?>
                <button type="submit" name="delete">Delete Equipment</button>
            <?php } ?>
        </div>
        <div>
            <!--return button-->
            <button type="button" onclick="window.location.href='equipment.php';">View All Equipment</button>
        </div>
    </form>
</div>

</body>
</html>
