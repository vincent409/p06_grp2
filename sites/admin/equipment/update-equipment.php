<?php
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

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Get the equipment ID from the URL parameter
$equipment_id = $_GET['id'];

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

// Initialize input error messages
$inputErrors = [];

// Check if form is submitted to update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Handle deletion of equipment
        $delete_stmt = $connect->prepare("DELETE FROM Equipment WHERE id = ?");
        $delete_stmt->bind_param("i", $equipment_id);

        if ($delete_stmt->execute()) {
            // Redirect to equipment list with a success message
            header("Location: equipment.php?deleted=1");
            exit(); // Stop further execution to ensure the redirect works properly
        } else {
            $errorMessage = "Error deleting equipment: " . mysqli_error($connect);
        }
    } elseif (isset($_POST['update'])) {
        // Handle update of equipment data
        $name = $_POST['name'];
        $type = $_POST['type'];
        $purchase_date = $_POST['purchase_date'];
        $model_number = $_POST['model_number'];

        // Define regex patterns for validation
        $equipment_name_pattern = "/^[a-zA-Z0-9\s]+$/"; // Allows letters and single spaces only between words
        $equipment_type_pattern = "/^[a-zA-Z]+(?: [a-zA-Z]+)*$/"; // Allows letters and single spaces only between words
        $model_number_pattern = "/^[a-zA-Z0-9-_]+$/"; // Alphanumeric, dashes, and underscores

        // Validate the equipment name
        if (!preg_match($equipment_name_pattern, $name)) {
            $inputErrors[] = "Equipment name must contain only alphanumeric characters and spaces.";
        }

        // Validate the equipment type
        if (!preg_match($equipment_type_pattern, $type)) {
            $inputErrors[] = "Equipment type must contain only letters and spaces.";
        }

        // Validate the model number
        if (!preg_match($model_number_pattern, $model_number)) {
            $inputErrors[] = "Model number must be alphanumeric, with dashes or underscores allowed.";
        }

        // If there are no validation errors, proceed with the update
        if (empty($inputErrors)) {
            $update_stmt = $connect->prepare("UPDATE Equipment SET name = ?, type = ?, purchase_date = ?, model_number = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $name, $type, $purchase_date, $model_number, $equipment_id);

            if ($update_stmt->execute()) {
                $successMessage = "Equipment updated successfully!";
                // After updating, refetch the equipment data from the database to show the updated values
                $stmt = $connect->prepare("SELECT * FROM Equipment WHERE id = ?");
                $stmt->bind_param("i", $equipment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $equipment = $result->fetch_assoc();  // Fetch the updated data
            }
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
        body {
            background-color: #E5D9B6; /* Soft beige background for the page */
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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

        .logout-btn button:hover {
            background-color: #E03C00;
        }

        form button {
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            width: 100%;
            background-color: #007bff;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .main-container {
            background-color: #FFFFFF; /* White background for the form section */
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Styling for form inputs */
        form input[type="text"], form input[type="date"], form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        /* Delete button styling */
        button[type="submit"][name="delete"] {
            background-color: #E53D29;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            width: 100%;
        }

        button[type="submit"][name="delete"]:hover {
            background-color: #E03C00;
        }

        button[type="submit"][name="update"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            width: 100%;
        }

        button[type="submit"][name="update"]:hover {
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
</nav>

<!-- Main content container with centered form -->
<div class="main-container">
    <h1>Update Equipment</h1>

    <!-- Display success or error messages -->
    <?php if (!empty($successMessage)) { ?>
        <p style="color: green; font-weight: bold;"><?php echo $successMessage; ?></p>
    <?php } ?>

    <?php if (!empty($errorMessage)) { ?>
        <p style="color: red; font-weight: bold;"><?php echo $errorMessage; ?></p>
    <?php } ?>

    <!-- Display input validation errors -->
    <?php if (!empty($inputErrors)) { ?>
        <ul style="color: red; font-weight: bold;">
            <?php foreach ($inputErrors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form action="update-equipment.php?id=<?php echo $equipment['id']; ?>" method="POST">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required><br><br>

        <label for="type">Type:</label>
        <input type="text" name="type" value="<?php echo htmlspecialchars($equipment['type']); ?>" required><br><br>

        <label for="purchase_date">Purchase Date:</label>
        <input type="date" name="purchase_date" value="<?php echo $equipment['purchase_date']; ?>" required><br><br>

        <label for="model_number">Model Number:</label>
        <input type="text" name="model_number" value="<?php echo htmlspecialchars($equipment['model_number']); ?>" required><br><br>

        <!-- Button container -->
        <div>
            <button type="submit" name="update">Update Equipment</button>
        </div>
        <div>
            <?php if ($_SESSION['role'] === "Admin") { ?>
                <button type="submit" name="delete">Delete Equipment</button>
            <?php } ?>
        </div>
        <div>
            <button type="button" onclick="window.location.href='equipment.php';">View All Equipment</button>
        </div>

    </form>
</div>

</body>
</html>
