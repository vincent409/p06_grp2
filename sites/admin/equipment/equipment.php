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

// SQL query to fetch all equipment data from the Equipment table
$sql = "SELECT id, name, type, purchase_date, model_number FROM Equipment";

// Execute the query
$result = mysqli_query($connect, $sql);

// Check if query execution was successful
if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipment</title>
    <style>
        body {
            background-color: #E5D9B6; /* Soft beige background for the page */
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .box {
            background-color: #FFFFFF;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }

        th, td {
            background-color: white;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .success-message {
            color: green;
            margin-bottom: 20px;
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
            background-color: #FF6347;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .logout-btn button:hover {
            background-color: #FF4500;
        }

        button {
            padding: 10px 20px;
            background-color: #007BFF; /* Dark Blue background */
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        h1 {
            margin-left: 5px;
        }

        /* Make the table responsive on smaller screens */
        @media screen and (max-width: 600px) {
            table, th, td {
                width: 100%;
                font-size: 14px;
            }
            th, td {
                padding: 5px;
            }
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

<div class="container">
    <div class="box">
        <h1>Equipment List</h1>

        <!-- Success message -->
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1) { ?>
            <p class="success-message">Equipment successfully deleted!</p>
        <?php } ?>

        <?php
        // Check if any records were returned
        if (mysqli_num_rows($result) > 0) {
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
            while ($row = mysqli_fetch_assoc($result)) {
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
        mysqli_close($connect);
        ?>

        <button onclick="window.location.href='add-equipment.php';">Add Equipment</button>
    </div>
</div>
</body>
</html>
