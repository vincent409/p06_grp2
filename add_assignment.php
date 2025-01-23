<?php
// Include database connection
$connect = mysqli_connect("localhost", "root", "", "amc");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // Get the email from the form, removing any leading/trailing spaces
    $equipment_id = $_POST['equipment_id'];

    // Check if the equipment_id is already assigned to someone else
    $check_equipment_query = "SELECT * FROM loan WHERE equipment_id = '$equipment_id' AND status_id = 1"; // Assuming 1 represents 'Assigned' status
    $check_equipment_result = mysqli_query($connect, $check_equipment_query);

    if (mysqli_num_rows($check_equipment_result) > 0) {
        // If the equipment is already assigned, show an error
        echo "<div style='color:red;'>Error: This equipment ID is already assigned to another user.</div>";
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
                // If the profile already has this equipment assigned, show an error
                echo "<div style='color:red;'>Error: This profile already has this equipment assigned.</div>";
            } else {
                // Fetch the status ID for the new loan (this might need adjustment based on logic)
                $status_id = 1;  // Default status_id for "Assigned"

                // Now insert the assignment into the loan table
                $insert_query = "INSERT INTO loan (profile_id, equipment_id, status_id) 
                                 VALUES ('$profile_id', '$equipment_id', '$status_id')";

                if (mysqli_query($connect, $insert_query)) {
                    echo "<div style='color:green;'>Assignment added successfully!</div>";
                } else {
                    echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
                }
            }
        } else {
            echo "<div style='color:red;'>Error: No profile found for the provided email.</div>";
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

        form {
            display: inline-block;
            text-align: left;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;  /* Ensures padding does not exceed container */
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
            font-size: 1.2em;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box; /* Ensures the input elements fit within the container */
        }

        button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button, .view-button {
            background-color: #007BFF;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            padding: 12px 20px;
            margin-top: 10px;
            width: 100%; /* Ensure buttons are not stretched out */
            box-sizing: border-box;
        }

        .back-button:hover, .view-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>Add New Assignment</h1>

    <form method="POST">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo $equipment_id; ?>" required>

        <button type="submit">Create Assignment</button>

        <!-- View Assignments Button -->
        <button class="view-button" onclick="window.location.href='edit_assignment.php';">View Assignments</button>

        <!-- Go back to admin.php -->
        <button class="back-button" onclick="window.location.href='assignment.php';">Back to Admin</button>
    </form>

</body>
</html>
