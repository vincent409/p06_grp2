<?php
// Start session (optional, depending on your application)
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Handle search query
$searchQuery = "";
$result = null;

if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];

    // Use a prepared statement to execute the search
    $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number 
                               FROM Equipment 
                               WHERE name LIKE ? OR type LIKE ?");
    
    // Add wildcards for the LIKE clause
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param("ss", $searchParam, $searchParam);

    // Execute the query
    $stmt->execute();

    // Get the result set
    $result = $stmt->get_result();

    // Close the statement
    $stmt->close();
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
    <style>
        .container-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .container-flex h1 {
            margin: 0;
            padding-left: 5px; /* Add padding to the left of the H1 */
        }

        .container-flex form {
            display: flex;
            align-items: center;
        }

        .container-flex form input[type="text"] {
            width: 250px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 10px;
        }

        .container-flex form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .container-flex form button:hover {
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

        <!-- Success message -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1) { ?>
            <p class="success-message">Equipment successfully deleted!</p>
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
