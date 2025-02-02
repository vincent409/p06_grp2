<?php
session_start();

// Ensure the user is logged in and has a valid role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: /p06_grp2/sites/index.php");
    exit(); // Stop further execution
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include_once 'C:/xampp/htdocs/p06_grp2/functions.php';
manageCookieAndRedirect("/p06_grp2/logout.php");


// Fetch the logged-in user's name
// Fetch the logged-in user's name
$user_id = $_SESSION['profile_id']; // Assuming this session variable holds the logged-in user's profile ID

$sql_user = "SELECT name FROM Profile WHERE id = ?";
$stmt = $connect->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$encrypted_user_name = $user_data['name']; // Get the encrypted name
$user_name = aes_decrypt($encrypted_user_name); // Decrypt it before displaying


// SQL query to get the counts of equipment in each status
$sql = "
    SELECT 
        SUM(CASE WHEN Status.name = 'Assigned' THEN 1 ELSE 0 END) AS assigned_count,
        SUM(CASE WHEN Status.name = 'In-Use' THEN 1 ELSE 0 END) AS in_use_count,
        SUM(CASE WHEN Status.name = 'Returned' THEN 1 ELSE 0 END) AS returned_count,
        COUNT(DISTINCT Equipment.id) AS total_equipment_count,  -- Counting distinct equipment
        COUNT(DISTINCT CASE WHEN Loan.equipment_id IS NULL THEN Equipment.id ELSE NULL END) AS unassigned_count  -- Handling unassigned count
    FROM 
        Equipment  -- Start with the Equipment table to ensure we count all equipment
    LEFT JOIN 
        Loan ON Equipment.id = Loan.equipment_id  -- Left join Loan table to count loaned equipment
    LEFT JOIN 
        Status ON Loan.status_id = Status.id  -- Left join Status table to get status for each loan
";

$result = mysqli_query($connect, $sql);

if ($result) {
    $data = mysqli_fetch_assoc($result);
    $assignedCount = $data['assigned_count'];
    $inUseCount = $data['in_use_count'];
    $returnedCount = $data['returned_count'];
    $totalEquipmentCount = $data['total_equipment_count'];

    // Calculate unassigned count
    $unassignedCount = $totalEquipmentCount - ($assignedCount + $inUseCount + $returnedCount);
} else {
    echo "Error: " . mysqli_error($connect);
}

// Get total number of students (assuming there is a Profile table for students)
$student_sql = "SELECT COUNT(*) AS total_students FROM Profile where role_id = '3'";
$student_result = mysqli_query($connect, $student_sql);
$totalStudents = mysqli_fetch_assoc($student_result)['total_students'];

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
    <style>
        .pie-chart {
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: conic-gradient(
                #4CAF50 0% <?php echo $assignedCount / $totalEquipmentCount * 100; ?>%, 
                #FF9800 <?php echo $assignedCount / $totalEquipmentCount * 100; ?>% <?php echo ($assignedCount + $inUseCount) / $totalEquipmentCount * 100; ?>%,   
                #3F51B5 <?php echo ($assignedCount + $inUseCount) / $totalEquipmentCount * 100; ?>% <?php echo ($assignedCount + $inUseCount + $returnedCount) / $totalEquipmentCount * 100; ?>%,   
                #9E9E9E <?php echo ($assignedCount + $inUseCount + $returnedCount) / $totalEquipmentCount * 100; ?>% 100%  
            );
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
    
    <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1> <!-- Display the user's name -->
    <div class="main">
        <div class="statistics">
            <h2>Equipment status</h2>
            <div class="pie-chart"></div>
        </div>
        <div class="color-box-container">
            <div style="display: flex; align-items: center;">
                <div class="color-box assigned" style="background-color: #4CAF50;"></div>
                <div>
                    <div class="status-text">Assigned: <?php echo $assignedCount; ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <div class="color-box in-use" style="background-color: #FF9800;"></div>
                <div>
                    <div class="status-text">In Use: <?php echo $inUseCount; ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <div class="color-box returned" style="background-color: #3F51B5;"></div>
                <div>
                    <div class="status-text">Returned: <?php echo $returnedCount; ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <div class="color-box unassigned" style="background-color: #9E9E9E;"></div>
                <div>
                    <div class="status-text">Unassigned: <?php echo $unassignedCount; ?></div>
                </div>
            </div>
            <button onclick="window.location.href='assignment/assignment.php';">More Details</button>
        </div>

        <div class="quick-stats">
            <h2>Quick Stats</h2>
            <p><strong>Total Equipment:</strong> <?php echo $totalEquipmentCount; ?></p>
            <button onclick="window.location.href='/p06_grp2/sites/admin/equipment/equipment.php';">More Details</button>
            <p><strong>Total Students:</strong> <?php echo $totalStudents; ?></p>
            <button onclick="window.location.href='/p06_grp2/sites/admin/students/profile.php';">More Details</button>
        </div>
    </div>
</body>
</html>
