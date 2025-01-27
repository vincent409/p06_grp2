<?php
// Start the session
session_start();

// Check if the user is logged in as Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
manageCookieAndRedirect("/p06_grp2/logout.php");

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle DELETE request for Admins and Facility Managers
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $check_status_query = "SELECT status_id FROM loan WHERE id = '$delete_id'";
    $check_status_result = mysqli_query($connect, $check_status_query);

    if ($check_status_result && mysqli_num_rows($check_status_result) > 0) {
        $status_row = mysqli_fetch_assoc($check_status_result);
        $status_id = $status_row['status_id'];

        if ($_SESSION['role'] === "Facility Manager" && $status_id !== NULL) {
            echo "<script>
                alert('Error: Facility Managers can only delete records where the status is NULL.');
                window.location.href = 'status.php';
            </script>";
            exit();
        } else {
            $delete_query = "DELETE FROM loan WHERE id = '$delete_id'";
            if (mysqli_query($connect, $delete_query)) {
                echo "<script>
                    alert('Assignment deleted successfully.');
                    window.location.href = 'status.php';
                </script>";
                exit();
            } else {
                echo "<script>
                    alert('Error deleting assignment: " . mysqli_error($connect) . "');
                    window.location.href = 'status.php';
                </script>";
                exit();
            }
        }
    } else {
        echo "<script>
            alert('Error: The specified assignment does not exist.');
            window.location.href = 'status.php';
        </script>";
        exit();
    }
}

// Handle UPDATE request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $new_status = $_POST['status'];
    $email = $_POST['email'];
    $returned_date = $_POST['returned_date'];

    // Fetch assigned_date for validation
    $assigned_date_query = "SELECT assigned_date FROM usage_log 
                            WHERE equipment_id = (SELECT equipment_id FROM loan WHERE id = '$update_id' LIMIT 1)";
    $assigned_date_result = mysqli_query($connect, $assigned_date_query);

    if ($assigned_date_result && mysqli_num_rows($assigned_date_result) > 0) {
        $assigned_date_row = mysqli_fetch_assoc($assigned_date_result);
        $assigned_date = $assigned_date_row['assigned_date'];

        if (!empty($returned_date) && $returned_date < $assigned_date) {
            echo "<script>
                alert('Error: Returned date cannot be earlier than the assigned date ($assigned_date).');
                window.location.href = 'status.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('Error: Assigned date not found for this equipment.');
            window.location.href = 'status.php';
        </script>";
        exit();
    }

    // Check current status
    $current_status_query = "SELECT status_id, profile_id FROM loan WHERE id = '$update_id'";
    $current_status_result = mysqli_query($connect, $current_status_query);

    if ($current_status_result && mysqli_num_rows($current_status_result) > 0) {
        $current_status_row = mysqli_fetch_assoc($current_status_result);
        $current_status_id = $current_status_row['status_id'];
        $current_profile_id = $current_status_row['profile_id'];

        // Fetch profile_id from the email
        $profile_query = "SELECT id FROM profile WHERE LOWER(email) = LOWER('$email') LIMIT 1";
        $profile_result = mysqli_query($connect, $profile_query);

        if (mysqli_num_rows($profile_result) > 0) {
            $profile_row = mysqli_fetch_assoc($profile_result);
            $profile_id = $profile_row['id'];

            // Admin: Can always update
            if ($_SESSION['role'] === "Admin") {
                $update_query = "UPDATE loan 
                                 SET profile_id = '$profile_id', status_id = '$new_status' 
                                 WHERE id = '$update_id'";
            }
            // Facility Manager: Can only update email if status is NULL
            elseif ($_SESSION['role'] === "Facility Manager") {
                if ($current_status_id === NULL) {
                    $update_query = "UPDATE loan 
                                     SET profile_id = '$profile_id', status_id = '$new_status' 
                                     WHERE id = '$update_id'";
                } elseif ($profile_id == $current_profile_id) {
                    $update_query = "UPDATE loan 
                                     SET status_id = '$new_status' 
                                     WHERE id = '$update_id'";
                } else {
                    echo "<script>
                        alert('Error: Facility Managers can only update the email if the status is NULL.');
                        window.location.href = 'status.php';
                    </script>";
                    exit();
                }
            } else {
                echo "<script>
                    alert('Error: Facility Managers can only update the email if the status is NULL.');
                    window.location.href = 'status.php';
                </script>";
                exit();
            }

            // Execute the update query
            if (mysqli_query($connect, $update_query)) {
                // Update returned_date in usage_log if status is "Returned"
                if ($new_status == 3 && !empty($returned_date)) {
                    $update_usage_log_query = "UPDATE usage_log 
                                               SET returned_date = '$returned_date' 
                                               WHERE equipment_id = (SELECT equipment_id FROM loan WHERE id = '$update_id' LIMIT 1)";
                    if (!mysqli_query($connect, $update_usage_log_query)) {
                        echo "<script>
                            alert('Error updating returned date: " . mysqli_error($connect) . "');
                            window.location.href = 'status.php';
                        </script>";
                        exit();
                    }
                }
                echo "<script>
                    alert('Assignment updated successfully.');
                    window.location.href = 'status.php';
                </script>";
                exit();
            } else {
                echo "<script>
                    alert('Error updating assignment: " . mysqli_error($connect) . "');
                    window.location.href = 'status.php';
                </script>";
                exit();
            }
        } else {
            echo "<script>
                alert('Error: No profile found for the provided email.');
                window.location.href = 'status.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('Error: Record not found or invalid update ID.');
            window.location.href = 'status.php';
        </script>";
        exit();
    }
}

// Fetch all assignments from the loan table
$query = "SELECT loan.id, loan.status_id, loan.profile_id, loan.equipment_id, 
                 IFNULL(status.name, 'NULL') as status_name, profile.email as profile_email,
                 (SELECT returned_date FROM usage_log WHERE equipment_id = loan.equipment_id LIMIT 1) as returned_date,
                 (SELECT assigned_date FROM usage_log WHERE equipment_id = loan.equipment_id LIMIT 1) as assigned_date
          FROM loan
          LEFT JOIN status ON loan.status_id = status.id
          LEFT JOIN profile ON loan.profile_id = profile.id";
$result = mysqli_query($connect, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($connect));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments</title>
    <style>
        body {
            background-color: #E5D9B6; /* Beige background */
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
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

        .logout-btn button {
            padding: 8px 12px;
            background-color: #E53D29;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .logout-btn button:hover {
            background-color: #E03C00;
        }

        .container {
            background-color: #ffffff; /* White container */
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            border-radius: 8px; /* Rounded corners */
            padding: 20px; /* Space inside container */
            margin: 20px auto; /* Space outside container */
            width: 90%; /* Responsive container width */
            max-width: 1200px; /* Max width for large screens */
            text-align: left; /* Align text to the left */
        }

        .container h1 {
            margin-top: 0;
            font-size: 1.5em; /* Slightly smaller font size for the title */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #ffffff; /* Ensure table matches container */
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
            background-color: #F9F9F9; /* Light background for cells */
        }

        th {
            background-color: #F1F1F1; /* Slightly darker for header */
        }

        td input, td button, td select {
            width: 90%; /* Adjust to prevent overflow */
            max-width: 250px;
            padding: 8px;
            font-size: 0.9em;
            background-color: #ffffff; /* White input background */
            border: 1px solid #ccc; /* Subtle border for inputs */
            border-radius: 4px;
        }

        td input:focus, td select:focus {
            border-color: #007BFF; /* Highlight on focus */
            outline: none;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .update-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
            border: none;
            border-radius: 4px;
        }

        .update-button:hover {
            background-color: #0056b3;
        }

        .delete-button {
            background-color: #FF0000;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            padding: 12px 20px;
            width: 48%;
            text-align: center;
            border: none;
            border-radius: 4px;
        }

        .delete-button:hover {
            background-color: #cc0000;
        }

        .back-button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            padding: 12px 20px;
            margin-top: 20px;
            font-size: 1.2em;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        .returned-date {
            display: none;
        }

        td label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        td input,
        td select {
            width: 100%;
            max-width: 250px;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 0.9em;
            background-color: #ffffff; /* Match the table cell background */
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
    <script>
    // Function to toggle the visibility of the Returned Date field
    function toggleReturnedDate(selectElement, returnedDateId) {
        const returnedDateField = document.getElementById(returnedDateId);
        if (selectElement.value === "3") { // Show when status is "Returned"
            returnedDateField.style.display = "block";
        } else {
            returnedDateField.style.display = "none"; // Hide otherwise
        }
    }
    </script>
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
        <h1>Manage Status</h1>
        <?php if (mysqli_num_rows($result) === 0): ?>
            <h2>No records found</h2>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Email</th>
                        <th>Equipment ID</th>
                        <th>Assigned Date</th>
                        <th>Returned Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['status_name']; ?></td>
                        <td><?php echo $row['profile_email']; ?></td>
                        <td><?php echo $row['equipment_id']; ?></td>
                        <td><?php echo $row['assigned_date'] ?: 'NIL'; ?></td>
                        <td><?php echo $row['returned_date'] ?: 'NIL'; ?></td>
                        <td>
                            <form action="status.php" method="POST" style="text-align: left;">
                                <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">

                                <!-- Email Field -->
                                <label for="email_<?php echo $row['id']; ?>">Email:</label>
                                <input type="email" id="email_<?php echo $row['id']; ?>" name="email" value="<?php echo $row['profile_email']; ?>" required>

                                <!-- Status Field -->
                                <label for="status_<?php echo $row['id']; ?>">Status:</label>
                                <select id="status_<?php echo $row['id']; ?>" name="status" onchange="toggleReturnedDate(this, 'returned_date_<?php echo $row['id']; ?>')">
                                    <option value="1" <?php echo ($row['status_id'] == 1) ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="2" <?php echo ($row['status_id'] == 2) ? 'selected' : ''; ?>>In-Use</option>
                                    <option value="3" <?php echo ($row['status_id'] == 3) ? 'selected' : ''; ?>>Returned</option>
                                </select>

                                <!-- Returned Date Field -->
                                <div id="returned_date_<?php echo $row['id']; ?>" style="display: <?php echo ($row['status_id'] == 3) ? 'block' : 'none'; ?>;">
                                    <label for="returned_date_input_<?php echo $row['id']; ?>">Returned Date:</label>
                                    <input type="date" id="returned_date_input_<?php echo $row['id']; ?>" name="returned_date" value="<?php echo $row['returned_date']; ?>">
                                </div>

                                <!-- Buttons -->
                                <div class="button-container">
                                    <button type="submit" class="update-button">Update</button>
                                    <?php if ($_SESSION['role'] === "Admin" || $row['status_id'] === NULL): ?>
                                        <a href="status.php?delete_id=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_close($connect);
?>