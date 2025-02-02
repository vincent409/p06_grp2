<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] != "Student") {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

// Connect to the database
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php';

// Get the student's profile ID from the session
$profile_id = $_SESSION['profile_id'];

// Fetch the student's name if not already in session
if (!isset($_SESSION['name'])) {
    $query = "SELECT name FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();
    $_SESSION['name'] = $name;
} else {
    $name = $_SESSION['name'];
}
$name = aes_decrypt($name);
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    if ($searchQuery !== "") {
        if (!preg_match($alphanumeric_pattern, $searchQuery)) {
            $inputErrors[] = "Search input must contain only alphanumeric characters and spaces.";
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number 
                                       FROM Equipment 
                                       WHERE name LIKE ? OR type LIKE ?");
            $searchParam = "%" . $searchQuery . "%";
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        }
    } else {
        $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

// Handle return request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_equipment_id'])) {
    $equipment_id = $_POST['return_equipment_id'];
    $update_query = "UPDATE Loan SET status_id = 3 WHERE profile_id = ? AND equipment_id = ?";
    $stmt = $connect->prepare($update_query);
    $stmt->bind_param("ii", $profile_id, $equipment_id);
    if ($stmt->execute()) {
        echo "<script>alert('Equipment returned successfully!'); window.location.href = 'student-dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to return equipment. Please try again.');</script>";
    }
    $stmt->close();
}

// Fetch inventory records
$inventory_records = [];
$inventory_query = "SELECT Equipment.id AS equipment_id, Equipment.name, Equipment.model_number, Equipment.purchase_date, Status.name AS status
                    FROM Loan
                    JOIN Equipment ON Loan.equipment_id = Equipment.id
                    JOIN Status ON Loan.status_id = Status.id
                    WHERE Loan.profile_id = ?";
$stmt = $connect->prepare($inventory_query);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $inventory_records[] = $row;
}
$stmt->close();

mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="/p06_grp2/student.css">
    </head>


    <script>
        function confirmReturn(equipmentId) {
            if (confirm("Are you sure you want to return this equipment?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "";
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "return_equipment_id";
                input.value = equipmentId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleDetails(equipmentId) {
            const detailsRow = document.getElementById(`details-${equipmentId}`);
            detailsRow.style.display = detailsRow.style.display === "table-row" ? "none" : "table-row";
        }
    </script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="/p06_grp2/img/TP-logo.png">
        </div>
        <div class="dashboard-title">
            Welcome to Your Dashboard
        </div>
        <div class="logout-btn">
            <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
        </div>
    </header>

    <nav>
        <a href="/p06_grp2/sites/student/student-dashboard.php">Home</a>
        <a href="/p06_grp2/sites/student/profile.php">Profile</a>
    </nav>

    <div class="inventory-section">
        <h2>Your Inventory Records</h2>
        <?php if (count($inventory_records) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Model Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo htmlspecialchars($record['model_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                            <td class="actions">
                                <button class="details-btn" onclick="toggleDetails(<?php echo $record['equipment_id']; ?>)">More Details</button>
                                <?php if ($record['status'] !== 'Returned'): ?>
                                    <button class="return-btn" onclick="confirmReturn(<?php echo $record['equipment_id']; ?>)">Return</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $record['equipment_id']; ?>" class="details-section">
                            <td colspan="4">
                                <strong>Equipment Name:</strong> <?php echo htmlspecialchars($record['name']); ?><br>
                                <strong>Model Number:</strong> <?php echo htmlspecialchars($record['model_number']); ?><br>
                                <strong>Purchase Date:</strong> <?php echo htmlspecialchars($record['purchase_date']); ?><br>
                                <strong>Status:</strong> <?php echo htmlspecialchars($record['status']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No inventory records found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
