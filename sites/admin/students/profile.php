<?php
session_start();
include 'cookie.php';
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is an Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
    die("You do not have permission to view this page.");
}

manageCookieAndRedirect(
    "index.php",
    "You have been idle for 5 seconds. Click OK to stay logged in.",
    "You have been idle for 10 seconds. Logging you out."
);




// Fetch only student profiles from the database
$sql = "
    SELECT Profile.id, Profile.name, Profile.email, Profile.phone_number, Profile.department 
    FROM Profile 
    JOIN Role ON Profile.role_id = Role.id 
    WHERE Role.name = 'Student';
";
$result = mysqli_query($connect, $sql);

if (!$result) {
    die("Error retrieving profiles: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profiles</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-edit:hover {
            background-color: #45a049;
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
            <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
        </div>
        <div class="dashboard-title">Dashboard</div>
        <div class="logout-btn">
            <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/xampp/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
        <a href="/xampp/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
        <a href="/xampp/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
        <a href="/xampp/p06_grp2/sites/admin/students/profile.php">Students</a>
        <a href="/xampp/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    </nav>

    <h1>Manage Student Profiles</h1>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Department</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
            <td><?php echo htmlspecialchars($row['department']); ?></td>
            <td>
                <form action="edit_profile.php" method="GET" style="display: inline;">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-edit">Edit</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>

    <a href="add_profile.php">Create New Profile</a>
</body>
</html>
