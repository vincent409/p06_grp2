<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /xampp/p06_grp2/sites/index.php");
    exit(); // Stop further execution
}
// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// SQL query to fetch equipment data with status and email
$sql = "
SELECT 
    Equipment.id AS equipment_id,
    Equipment.name AS equipment_name,
    Status.name AS equipment_status,
    Profile.email AS email
FROM Equipment
LEFT JOIN Loan
    ON Loan.equipment_id = Equipment.id
LEFT JOIN Status
    ON Loan.status_id = Status.id
LEFT JOIN Profile
    ON Loan.profile_id = Profile.id;
";

// Execute the query
$result = mysqli_query($connect, $sql);

// Check if query executed successfully
if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Data</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        a {
            text-decoration: none;
            color: #007BFF;
        }
        a:hover {
            text-decoration: underline;
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
            <img src="/xampp/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='/xampp/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/xampp/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
        <a href="/xampp/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
        <a href="/xampp/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/xampp/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/xampp/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    </nav>
    <h1>Equipment Details</h1>
    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Equipment Name</th>
                <th>Equipment Status</th>
                <th>Email</th>
                <th>Assign Equipment</th> <!-- New Column for Assign -->
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch and display data in a table
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Create the link for assigning the equipment
                    $assign_link = "add_assignment.php?equipment_id=" . $row['equipment_id'];
                    echo "<tr>
                            <td>{$row['equipment_id']}</td>
                            <td>{$row['equipment_name']}</td>
                            <td>" . ($row['equipment_status'] ? $row['equipment_status'] : "N/A") . "</td>
                            <td>" . ($row['email'] ? $row['email'] : "N/A") . "</td>
                            <td><a href='$assign_link'>Assign</a></td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }

            // Close the database connection
            mysqli_close($connect);
            ?>
        </tbody>
    </table>
</body>
</html>
