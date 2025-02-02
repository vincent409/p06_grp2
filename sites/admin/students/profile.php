<?php
session_start();//start the session to manage user authentication


//include necessary files for database connection,validation,cookie,functions
include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/functions.php'; 
//manage cookie and redirect if inactivity
manageCookieAndRedirect("/p06_grp2/sites/index.php");

// Ensure user is Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager')) {
    header("Location: /p06_grp2/sites/index.php?error=No permission");
    exit();
}
//Retrieve search query if provided
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";
$filteredResults = [];

// Fetch all student profiles (no search filtering in MySQL for encrypted fields)
// Prepare sql statement using connect,prepare()function help prevent sql injection by allowing parameter binding
$stmt = $connect->prepare("
    SELECT Profile.id, Profile.name, Profile.admin_number, Profile.email, Profile.phone_number, Profile.department
    FROM Profile 
    JOIN Role ON Profile.role_id = Role.id 
    WHERE Role.name = 'Student'
");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// loop through each retrieved student profile
while ($row = mysqli_fetch_assoc($result)) {
    $decrypted_name = aes_decrypt($row['name']); // Decrypt name
    $decrypted_email = aes_decrypt($row['email']); // Decrypt email
    $decrypted_phone = aes_decrypt($row['phone_number']); // Decrypt phone number

    // If search query is empty, show all results otherwise,filter by name or admin_number
    if ($searchQuery === "" || 
        stripos($decrypted_name, $searchQuery) !== false || 
        stripos($row['admin_number'], $searchQuery) !== false) {
        
        // Store all results or matched results
        $filteredResults[] = [
            'id' => $row['id'],
            'name' => $decrypted_name,
            'admin_number' => $row['admin_number'],
            'email' => $decrypted_email,
            'phone_number' => $decrypted_phone,
            'department' => $row['department']
        ];
    }
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
    <a href="/p06_grp2/sites/admin/assignment/assignment.php">Assignments</a>
    <a href="/p06_grp2/sites/admin/students/profile.php">Students</a>
    <a href="/p06_grp2/sites/admin/logs/edit_usage_logs.php">Logs</a>
    <a href="/p06_grp2/sites/admin/status.php">Status</a>
</nav>

<div class="container">
    <div class="box">
        <div class="container-flex">
            <h1>Student Profiles</h1>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by Name or Admin Number" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    
    <div class="left-content">
        <a href="add_profile.php" class="enter-logs-button">Add New Profile</a>
    </div>

        <?php
        if (!empty($filteredResults)) {
            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>Name</th>
                        <th>Admin Number</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            //loop thorugh each filtered result that is stored and display each student in a table row
            foreach ($filteredResults as $row) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['admin_number']) . "</td>
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
            echo "<p>No student profiles found.</p>";//error message if no student profile is found
        }

        mysqli_close($connect);//close the database connection
        ?>

    </div>
</div>
</body>
</html>
