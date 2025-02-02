<?php
session_start();
#checking for the user's role and sending them to the home page if they are not an admin or facility manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

#including file that contain functions that are used on this webpage
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

#setting varibles
$searchQuery = "";
$result = null;
$inputErrors = [];

#checks url for the deleted query and stores an message accordingly
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = "Equipment deleted successfully!";
}
#checks for the search query
if (isset($_GET['search'])) {
    #removes whitespaces from the start and end of the search
    $searchQuery = trim($_GET['search']);
    #checks that the search is not blank
    if ($searchQuery !== "") {
        #check if search is alphanumeric and displays an error but displays all equipment
        if (!preg_match($alphanumeric_pattern, $searchQuery)) {
            $inputErrors[] = "Search input must contain only alphanumeric characters and spaces.";
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else { #searches for equipment with the search term in its name or type
            $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number 
                                       FROM Equipment 
                                       WHERE name LIKE ? OR type LIKE ?");
            $searchParam = "%" . $searchQuery . "%";
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        }
    } else { #displays everything if no search term is inputed
        $stmt = $connect->prepare("SELECT id, name, type, purchase_date, model_number FROM Equipment");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else { #displays everything if no search query
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
    <!-- Adjust to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Equipment</title>
    <link rel="stylesheet" href="/p06_grp2/admin.css">
</head>
<body>
<!-- creating the header that is on all admin webpages -->
<header>
    <div class="logo">
        <img src="/p06_grp2/img/TP-logo.png" alt="TP Logo" width="135" height="50">
    </div>
    <div class="dashboard-title">Dashboard</div>
    <div class="logout-btn">
        <button onclick="window.location.href='/p06_grp2/logout.php';">Logout</button>
    </div>
</header>
<!-- creating the navigation bar that is on all admin webpages -->
<nav>
    <a href="/p06_grp2/sites/admin/admin-dashboard.php">Home</a>
    <a href="/p06_grp2/sites/admin/equipment/equipment.php">Equipment</a>
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Assignments</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>
<!-- creating the a container where all the equipment infomation is displayed -->
<div class="container">
    <div class="box">
        <div class="container-flex">
            <h1>Equipment List</h1>
            <!-- creating search feature -->
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search equipment..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        <!-- displays invaild search queries -->
        <?php if (!empty($inputErrors)) { ?>
            <div class="error-message">
                <?php foreach ($inputErrors as $error) { echo "<p>$error</p>"; } ?>
            </div>
        <?php } ?>
        <!-- displays successful messages like equipment deletion -->
        <?php if (!empty($message)) { ?>
            <div class="success-message">
                <?php echo $message;  ?>
            </div>
        <?php } ?>
        <!-- add equipment button -->
        <button onclick="window.location.href='add-equipment.php';">Add Equipment</button>
        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <!-- table column headers -->
                        <th>Name</th>
                        <th>Type</th>
                        <th>Purchase Date</th>
                        <th>Model Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- dynamically displaying equipment details -->
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['purchase_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['model_number']); ?></td>
                            <td>
                                <!-- directing user to each equipment respective edit page -->
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
            <!-- message for when no items match search query -->
            <p>No equipment found in the database.</p>
        <?php } ?>
    </div>
</div>
</body>
</html>
