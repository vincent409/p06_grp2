<?php
session_start();
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: login.php");
    exit(); // Stop further execution
}

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
    <style>
        body {
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
        .main {
            display: flex;
            justify-content: space-between;
            padding: 30px;
            gap: 20px;
            flex-wrap: wrap;
        }
        .statistics {
            flex: 2;
            max-width: 600px;
        }
        .quick-stats {
            flex: 1;
            max-width: 300px;
            padding: 10px;
            background-color: #f4f4f4;
            border-radius: 8px;
            text-align: left;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .quick-stats button {
            padding: 5px 10px;
            margin-top: 5px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .quick-stats button:hover {
            background-color: #0056b3;
        }
        .welcome {
            margin-bottom: 20px;
            font-size: 18px;
        }
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
        .color-box-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
            padding-top: 40px; /* Adjusted to bring legend down */
        }
        .color-box {
            width: 20px;  /* Reduced box size */
            height: 20px;  /* Reduced box size */
            border-radius: 50%;  /* Makes the boxes circular */
            text-align: center;
            color: white;
            font-weight: bold;
            display: inline-block;
            margin-right: 10px;  /* Spacing between color box and text */
        }
        .status-text {
            font-size: 16px;
        }
        .color-box-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
            justify-content: flex-start;
            margin-top: 20px;  /* To align the legend with the pie chart */
        }
        .color-box-container div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 {
            margin-bottom: 10px;  /* Reduced gap between Welcome User and Equipment Status */
        }

        /* Styling for the More Details buttons */
        .color-box-container button {
            padding: 5px 10px;
            margin-top: 5px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .color-box-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="admin-dashboard.php">Home</a>
        <a href="equipment.php">Equipment</a>
        <a href="assignment.php">Loans</a>
        <a href="#">Students</a>
        <a herf="#">Logs</a>
    </nav>
    
    <h1>Welcome, User!</h1>
    <div class="main">
        <div class="statistics">
            <h1>Equipment status</h1>
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
            <button onclick="window.location.href='total_students_details.php';">More Details</button>
        </div>

        <div class="quick-stats">
            <h3>Quick Stats</h3>
            <p><strong>Total Equipment:</strong> <?php echo $totalEquipmentCount; ?></p>
            <button onclick="window.location.href='total_equipment_details.php';">More Details</button>
            <p><strong>Total Students:</strong> <?php echo $totalStudents; ?></p>
            <button onclick="window.location.href='total_students_details.php';">More Details</button>
        </div>
    </div>
</body>
</html>
