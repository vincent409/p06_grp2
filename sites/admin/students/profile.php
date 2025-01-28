<?php
session_start();

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Check if the user is an Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
    die("You do not have permission to view this page.");
}

$searchQuery = "";
$result = null;
$inputErrors = []; // Array to store validation errors

if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']); // Trim whitespace

    // Check if search query is not empty
    if ($searchQuery !== "") {
            // Use a prepared statement to execute the search for valid input
            $stmt = $connect->prepare("
                SELECT Profile.id, Profile.name, Profile.email, Profile.phone_number, Profile.department 
                FROM Profile 
                JOIN Role ON Profile.role_id = Role.id 
                WHERE Role.name = 'Student' AND 
                      (Profile.name LIKE ? OR Profile.email LIKE ? OR Profile.department LIKE ?)
            ");
            $searchParam = "%" . $searchQuery . "%";
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    } else {
        // If search query is empty, fetch all student profiles
        $stmt = $connect->prepare("SELECT Profile.id, Profile.name, Profile.email, Profile.phone_number, Profile.department 
                                   FROM Profile 
                                   JOIN Role ON Profile.role_id = Role.id 
                                   WHERE Role.name = 'Student'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    // Default query to fetch all student profiles if no search is performed
    $stmt = $connect->prepare("SELECT Profile.id, Profile.name, Profile.email, Profile.phone_number, Profile.department 
                               FROM Profile 
                               JOIN Role ON Profile.role_id = Role.id 
                               WHERE Role.name = 'Student'");
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
    <title>Manage Profiles</title>
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Loans</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="container">
    <div class="box">
        <div class="container-flex">
            <h1>Student Profiles</h1>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Display Validation Errors -->
        <?php if (!empty($inputErrors)) { ?>
            <div class="error-message">
                <?php foreach ($inputErrors as $error) {
                    echo "<p>$error</p>";
                } ?>
            </div>
        <?php } ?>

        <?php
        // Display profiles in a table
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                  </thead>";
            echo "<tbody>";

            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['phone_number']) . "</td>
                        <td>" . htmlspecialchars($row['department']) . "</td>
                        <td>
                            <form action='edit_profile.php' method='POST' style='display:inline;'>
                                <input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>
                                <button type='submit'>Edit</button>
                            </form>
                        </td>
                      </tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No student profiles found.</p>";
        }

        mysqli_close($connect);
        ?>

        <button onclick="window.location.href='add_profile.php';">Create New Profile</button>
    </div>
</div>
</body>
</html>
