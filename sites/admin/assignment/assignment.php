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

// Search functionality with validation
$searchQuery = "";
$errorMsg = "";

if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);

    // Validate search input to allow only numbers, or allow empty input
    if (!preg_match('/^[0-9]*$/', $searchQuery)) {
        $errorMsg = "Invalid input. Only numbers are allowed.";
        $searchQuery = ""; // Reset search query if invalid
    }
}

// SQL query to fetch equipment data with or without filtering
$sql = "
SELECT 
    Equipment.id AS equipment_id,
    Equipment.name AS equipment_name,
    Status.name AS equipment_status,
    Profile.admin_number AS admin_number
FROM Equipment
LEFT JOIN Loan ON Loan.equipment_id = Equipment.id
LEFT JOIN Status ON Loan.status_id = Status.id
LEFT JOIN Profile ON Loan.profile_id = Profile.id";

// If a valid numeric search is provided, filter by Equipment ID
if ($searchQuery !== "") {
    $sql .= " WHERE Equipment.id LIKE ?";
}

$stmt = $connect->prepare($sql);

if ($searchQuery !== "") {
    $searchParam = "%{$searchQuery}%";
    $stmt->bind_param("s", $searchParam);
}

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
    <link rel="stylesheet" href="/p06_grp2/assign.css">
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
                <input type="text" name="search" placeholder="Search by Equipment ID..." 
                    value="<?php echo htmlspecialchars($searchQuery); ?>" 
                    pattern="[0-9]*" 
                    title="Only numbers are allowed"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                    $assign_link = "add_assignment.php?equipment_id=" . urlencode($row['equipment_id']);
                    $admin_number = !empty($row['admin_number']) ? htmlspecialchars($row['admin_number']) : "N/A";
                    echo "<tr>
                        <td>{$row['equipment_id']}</td>
                        <td>{$row['equipment_name']}</td>
                        <td>" . ($row['equipment_status'] ? $row['equipment_status'] : "N/A") . "</td>
                        <td>{$admin_number}</td>
                        <td>
                            <a href='{$assign_link}'>Assign</a>
                        </td>
                    </tr>";                
                }
            }
            else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }

            mysqli_close($connect);
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
