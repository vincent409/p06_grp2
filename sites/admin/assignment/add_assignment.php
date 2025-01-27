<?php

include_once 'C:/xampp/htdocs/p06_grp2/connect-db.php';
include 'C:/xampp/htdocs/p06_grp2/cookie.php';
include 'C:/xampp/htdocs/p06_grp2/validation.php';
manageCookieAndRedirect("/p06_grp2/sites/index.php");

$assignment_created = false; // Variable to track if the assignment was created
$error_message = ''; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // Get the email from the form, removing any leading/trailing spaces
    $equipment_id = $_POST['equipment_id'];

    // Check if the equipment_id is already assigned to someone else
    $check_equipment_query = "SELECT * FROM loan WHERE equipment_id = '$equipment_id' AND status_id = 1"; // Assuming 1 represents 'Assigned' status
    $check_equipment_result = mysqli_query($connect, $check_equipment_query);

    if (mysqli_num_rows($check_equipment_result) > 0) {
        $error_message = "Error: This equipment ID is already assigned to another user.";
    } else {
        // Fetch the profile_id from the Profile table using the provided email
        $profile_query = "SELECT id FROM profile WHERE LOWER(email) = LOWER('$email') LIMIT 1"; // Case-insensitive comparison
        $profile_result = mysqli_query($connect, $profile_query);

        // Check if the profile exists
        if (mysqli_num_rows($profile_result) > 0) {
            // Retrieve the profile_id from the result
            $profile_row = mysqli_fetch_assoc($profile_result);
            $profile_id = $profile_row['id'];

            // Check if the profile already has an existing assignment for this equipment_id
            $check_duplicate_query = "SELECT * FROM loan WHERE profile_id = '$profile_id' AND equipment_id = '$equipment_id'";
            $check_duplicate_result = mysqli_query($connect, $check_duplicate_query);

            if (mysqli_num_rows($check_duplicate_result) > 0) {
                $error_message = "Error: This profile already has this equipment assigned.";
            } else {
                $status_id = 1;  // Default status_id for "Assigned"

                // Insert the assignment into the loan table
                $insert_query = "INSERT INTO loan (profile_id, equipment_id, status_id) 
                                 VALUES ('$profile_id', '$equipment_id', '$status_id')";

                if (mysqli_query($connect, $insert_query)) {
                    $assignment_created = true; // Set the flag to true when an assignment is created
                } else {
                    $error_message = "Error: " . mysqli_error($connect);
                }
            }
        } else {
            $error_message = "Error: No profile found for the provided email.";
        }
    }
}

// Get the equipment_id from the URL
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';  // Use an empty string if not set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Assignment</title>
    <style>
        body {
            background-color: #E5D9B6; /* Beige background */
            font-family: Arial, sans-serif;
            color: black;
            margin: 0;
            padding: 0;
            text-align: center; /* Center-aligns content */
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

        nav a:hover {
            text-decoration: underline;
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

        h1 {
            text-align: center; /* Center-aligns the heading */
            margin: 20px auto;
            font-size: 1.8em;
            color: black;
        }

        .container {
            background-color: white; /* White container */
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            border-radius: 8px; /* Rounded corners */
            padding: 20px; /* Space inside container */
            margin: 20px auto; /* Space outside container */
            width: 90%; /* Responsive container width */
            max-width: 600px; /* Max width for large screens */
            text-align: left; /* Align text within the container */
        }

        form {
            text-align: left;
        }

        label {
            font-size: 1em;
            display: block;
            margin: 10px 0 5px;
        }

        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box; /* Ensures padding does not exceed container */
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button, .view-button {
            background-color: #007BFF;
            border: none;
            cursor: pointer;
            font-size: 1em;
            padding: 12px 20px;
            margin-top: 10px;
            width: 100%; /* Ensure buttons are not stretched */
            border-radius: 5px;
            box-sizing: border-box;
        }

        .back-button:hover, .view-button:hover {
            background-color: #0056b3;
        }
    </style>
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
    <h1>Add New Assignment</h1>
    <form method="POST">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo $equipment_id; ?>" required>

        <button type="submit">Create Assignment</button>

        <!-- View Assignments Button -->
        <button type="button" class="view-button" onclick="window.location.href='edit_assignment.php';">View Assignments</button>

        <!-- Go back to admin.php -->
        <button type="button" class="back-button" onclick="window.location.href='assignment.php';">Back to Admin</button>
    </form>
</div>

<!-- JavaScript to handle pop-ups -->
<?php if ($assignment_created): ?>
<script>
    alert("Assignment has been created successfully!");
</script>
<?php elseif (!empty($error_message)): ?>
<script>
    alert("<?php echo $error_message; ?>");
</script>
<?php endif; ?>
</body>
</html>
