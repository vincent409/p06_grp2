<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Search functionality
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

// SQL query to fetch equipment data with search filtering
$sql = "
SELECT 
    Equipment.id AS equipment_id,
    Equipment.name AS equipment_name,
    Status.name AS equipment_status,
    Profile.admin_number AS admin_number
FROM Equipment
LEFT JOIN Loan ON Loan.equipment_id = Equipment.id
LEFT JOIN Status ON Loan.status_id = Status.id
LEFT JOIN Profile ON Loan.profile_id = Profile.id
WHERE Equipment.id LIKE ?";

$stmt = $connect->prepare($sql);
$searchParam = "%{$searchQuery}%";
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Assignments</title>
    <style>
        body {
            background-color: #E5D9B6;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: black;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
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

        nav a:hover {
            text-decoration: underline;
        }

        .logout-btn button {
            padding: 8px 12px;
            background-color: #E53D29;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        .logout-btn button:hover {
            background-color: #E03C00;
        }

        .container {
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            width: 90%;
            max-width: 1200px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-container input {
            width: 250px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-container button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-left: 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
            background-color: #F9F9F9;
        }

        th {
            background-color: #F1F1F1;
        }

        a {
            text-decoration: none;
            color: #007BFF;
        }

        a:hover {
            text-decoration: underline;
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
    <div class="header-container">
        <h1>Equipment Assignments</h1>

        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by Equipment ID..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Equipment Name</th>
                <th>Equipment Status</th>
                <th>Admin Number</th>
                <th>Assign Equipment</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $assign_link = "add_assignment.php?equipment_id=" . $row['equipment_id'];
                    $admin_number = !empty($row['admin_number']) ? htmlspecialchars($row['admin_number']) : "N/A";
                    echo "<tr>
                        <td>{$row['equipment_id']}</td>
                        <td>{$row['equipment_name']}</td>
                        <td>" . ($row['equipment_status'] ? $row['equipment_status'] : "N/A") . "</td>
                        <td>{$admin_number}</td>
                        <td><a href='$assign_link'>Assign</a></td>
                    </tr>";                
                }
            } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }

            mysqli_close($connect);
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
