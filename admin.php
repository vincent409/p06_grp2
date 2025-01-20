<?php
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
    </style>
</head>
<body>
    <h1>Equipment Details</h1>
    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Equipment Name</th>
                <th>Equipment Status</th>
                <th>Admin Email</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch and display data in a table
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                            <td>{$row['equipment_id']}</td>
                            <td>{$row['equipment_name']}</td>
                            <td>" . ($row['equipment_status'] ? $row['equipment_status'] : "N/A") . "</td>
                            <td>" . ($row['email'] ? $row['email'] : "N/A") . "</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No data available</td></tr>";
            }

            // Close the database connection
            mysqli_close($connect);
            ?>
        </tbody>
    </table>
</body>
</html>
