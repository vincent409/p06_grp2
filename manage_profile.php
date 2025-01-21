<?php
session_start();
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is an Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
    die("You do not have permission to view this page.");
}

// Fetch all student profiles from the database
$sql = "SELECT id, name, email, phone_number, department FROM Profile WHERE role_id = 3"; // Assuming role_id=3 corresponds to 'Student'
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
    </style>
</head>
<body>
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

    <a href="create_profile.php">Create New Profile</a>
</body>
</html>
