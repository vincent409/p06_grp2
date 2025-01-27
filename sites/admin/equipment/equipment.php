<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

include 'C:/xampp/htdocs/p06_grp2/validation.php';
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$searchQuery = "";
$result = null;
$inputErrors = []; // Array to store validation errors


if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']); // Trim whitespace

    // Check if search query is not empty
    if ($searchQuery !== "") {
        // Validate the search input
        if (!preg_match($alphanumeric_pattern, $searchQuery)) {
            $inputErrors[] = "Search input must contain only alphanumeric characters and spaces.";

            // Default to fetching all records on invalid input
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            // Use a prepared statement to execute the search for valid input
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number 
                                       FROM Equipment 
                                       WHERE name LIKE ? OR type LIKE ?");
            $searchParam = "%" . $searchQuery . "%";
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        }
    } else {
        // If search query is empty, fetch all records
        $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    // Default query to fetch all equipment if no search is performed
    $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
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
    <title>View Equipment</title>
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="container">
    <div class="box">
        <!-- Title and Search Bar in a Flex Container -->
        <div class="container-flex">
            <h1>Equipment List</h1>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search equipment..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Display Validation Errors -->
        <?php if (!empty($inputErrors)) { ?>
            <div class="error-message">
                <?php foreach ($inputErrors as $error) {
                    echo "<p>$error</p>";
                } ?>
            </div>
        <?php } ?>

        <?php
        // Check if any records were returned
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Purchase Date</th>
                        <th>Model Number</th>
                        <th>Action</th> <!-- New column for Edit -->
                    </tr>
                  </thead>";
            echo "<tbody>";

            // Fetch and display each row of data
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['name'] . "</td>
                        <td>" . $row['type'] . "</td>
                        <td>" . $row['purchase_date'] . "</td>
                        <td>" . $row['model_number'] . "</td>
                        <td><a href='update-equipment.php?id=" . $row['id'] . "'>Edit</a></td>
                      </tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No equipment found in the database.</p>";
        }

        // Close the database connection
        $connect->close();
        ?>

        <button onclick="window.location.href='add-equipment.php';">Add Equipment</button>
    </div>
</div>
</body>
</html>
