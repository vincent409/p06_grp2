<?php
// Start the session
session_start();

// Check if the user is logged in as Admin or Facility Manager
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    header("Location: /p06_grp2/sites/index.php");
    exit();
}

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
            background-color: white;
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h1 {
            color: black;
            background-color: white;
            padding: 20px;
            margin: 0;
            font-size: 2em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-size: 1em;
        }

        th {
            background-color: #f2f2f2;
        }

        td input, td button, td select {
            width: 100%;
            max-width: 250px;
            padding: 8px;
            font-size: 0.9em;
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
        }

        td .update-button,
        td .delete-button {
            display: inline-block;
            padding: 10px 15px;
            font-size: 0.9em;
            cursor: pointer;
            text-align: center;
            border: none;
            border-radius: 5px;
        }

        td .update-button {
            background-color: #007BFF;
            color: white;
        }

        td .update-button:hover {
            background-color: #0056b3;
        }

        td .delete-button {
            background-color: #FF0000;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            font-size: 0.9em;
            border-radius: 5px;
        }

        td .delete-button:hover {
            background-color: #cc0000;
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
    <h1>Manage Status</h1>

    <?php
    if (mysqli_num_rows($result) === 0): ?>
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
                            <div style="margin-top: 10px; display: flex; gap: 10px;">
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
    <a href="/p06_grp2/sites/admin/admin-dashboard.php" class="back-button">Back</a>
</body>
</html>

<?php
mysqli_close($connect);
?>