<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$searchQuery = "";
$result = null;
$inputErrors = [];

if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = "Equipment deleted successfully!";
}
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipment</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
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
    <div class="box">
        <div class="container-flex">
            <h1>Equipment List</h1>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search equipment..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($inputErrors)) { ?>
            <div class="error-message">
                <?php foreach ($inputErrors as $error) { echo "<p>$error</p>"; } ?>
            </div>
        <?php } ?>

        <?php if (!empty($message)) { ?>
            <div class="success-message">
                <?php echo $message;  ?>
            </div>
        <?php } ?>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Purchase Date</th>
                        <th>Model Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['purchase_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['model_number']); ?></td>
                            <td>
                                <form action="edit-equipment.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <button type="submit">Edit</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p>No equipment found in the database.</p>
        <?php } ?>
        <button onclick="window.location.href='add-equipment.php';">Add equipment</button>
    </div>
</div>
</body>
</html>
