<?php
// Start the session at the very beginning of the script
session_start();

// Check if the student is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: login.php");
    exit();
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the student's profile ID from the session
$profile_id = $_SESSION['profile_id'];
$email = $_SESSION['email'];

// Initialize inventory records as an empty array
$inventory_records = [];

try {
    // Fetch inventory records
    $inventory_query = "SELECT Equipment.id AS equipment_id, Equipment.name, Equipment.model_number, Equipment.purchase_date, Status.name AS status
                        FROM Loan
                        JOIN Equipment ON Loan.equipment_id = Equipment.id
                        JOIN Status ON Loan.status_id = Status.id
                        WHERE Loan.profile_id = ?";
    $stmt = $connect->prepare($inventory_query);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch data and populate the $inventory_records array
    while ($row = $result->fetch_assoc()) {
        $inventory_records[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    // Handle any query or connection errors
    error_log("Error fetching inventory records: " . $e->getMessage());
}

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f9f9f9;
            position: relative;
        }
        .logout-btn {
            position: absolute;
            top: 10px;
            right: 20px;
        }
        .logout-btn form {
            margin: 0;
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
        .change-password-btn {
            margin-top: 20px;
        }
        .change-password-btn button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }
        .change-password-btn button:hover {
            background-color: #0056b3;
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        button {
            padding: 8px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #45a049;
        }
        .profile-btn {
            margin-bottom: 20px;
        }
        .details-section {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f4f4f4;
        }
        .hidden {
            display: none;
        }
    </style>
    <script>
        // Function to toggle the visibility of details
        function toggleDetails(id) {
            const detailsRow = document.getElementById(`details-${id}`);
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
            } else {
                detailsRow.classList.add('hidden');
            }
        }
    </script>
</head>
<body>
    <div class="logout-btn">
        <button onclick="window.location.href='logout.php';">Logout</button>
    </div>

    <h1>Welcome to Your Dashboard, <?php echo htmlspecialchars($email); ?>!</h1>

    <!-- Profile Button -->
    <div class="profile-btn">
        <a href="profile.php">
            <button>View Your Profile</button>
        </a>
    </div>

    <!-- Change Password Button -->
    <div class="change-password-btn">
        <a href="change_password.php">
            <button>Change Password</button>
        </a>
    </div>

    <!-- Inventory Section -->
    <div class="inventory-section">
        <h2>Your Inventory Records</h2>
        <?php if (count($inventory_records) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Model Number</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo htmlspecialchars($record['model_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                            <td>
                                <button onclick="toggleDetails(<?php echo $record['equipment_id']; ?>)">View Details</button>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $record['equipment_id']; ?>" class="hidden">
                            <td colspan="4">
                                <div class="details-section">
                                    <p><strong>Equipment Name:</strong> <?php echo htmlspecialchars($record['name']); ?></p>
                                    <p><strong>Model Number:</strong> <?php echo htmlspecialchars($record['model_number']); ?></p>
                                    <p><strong>Purchase Date:</strong> <?php echo htmlspecialchars($record['purchase_date']); ?></p>
                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($record['status']); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no inventory records assigned.</p>
        <?php endif; ?>
    </div>
</body>
</html>
