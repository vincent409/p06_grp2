<?php
// Start session (optional, depending on your application)
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: login.php");
    exit(); // Stop further execution
}
// Establish a database connection
$connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to database");

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
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Equipment List</h1>

    <?php
    // Check if any records were returned
    if (mysqli_num_rows($result) > 0) {
        echo "<table>";
        echo "<thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Purchase Date</th>
                    <th>Model Number</th>
                </tr>
              </thead>";
        echo "<tbody>";

        // Fetch and display each row of data
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . $row['id'] . "</td>
                    <td>" . $row['name'] . "</td>
                    <td>" . $row['type'] . "</td>
                    <td>" . $row['purchase_date'] . "</td>
                    <td>" . $row['model_number'] . "</td>
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
</body>
</html>
