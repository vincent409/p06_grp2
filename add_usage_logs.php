<?php
// Start the session
session_start();

// Check user role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: login.php");
    exit(); // Stop further execution
}

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check the connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST['equipment_id'];
    $log_details = $_POST['log_details']; // Usage or maintenance details
    $assigned_date = $_POST['assigned_date']; // Date when the equipment was assigned
    $returned_date = $_POST['returned_date']; // Date when the equipment was returned

    // Insert usage log into the database
    $insert_query = "INSERT INTO usage_log (equipment_id, log_details, assigned_date, returned_date) 
                     VALUES ('$equipment_id', '$log_details', '$assigned_date', '$returned_date')";

    if (mysqli_query($connect, $insert_query)) {
        echo "<div style='color:green;'>Usage log added successfully!</div>";
    } else {
        echo "<div style='color:red;'>Error: " . mysqli_error($connect) . "</div>";
    }
}

// Get the equipment_id from the URL (if available)
$equipment_id = isset($_GET['equipment_id']) ? $_GET['equipment_id'] : '';  // Use an empty string if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Usage Logs</title>
    <style>
        body {
            background-color: white; /* Page background is white */
            font-family: Arial, sans-serif;
            color: black; /* Text color is black */
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h1 {
            color: black; /* Make the "Enter Usage Log" text black */
            background-color: white; /* The background color around the text is white */
            padding: 20px;
            margin: 0;
            font-size: 2em;
        }

        form {
            display: inline-block;
            text-align: left;
            background-color: white; /* White background for the form */
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            width: 80%; /* Adjust width to prevent buttons from being stretched */
            max-width: 400px; /* Max width to keep the form centered */
        }

        label {
            font-size: 1em;
            display: block;
            margin: 10px 0 5px;
        }

        input, textarea, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 1.2em;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            background-color: #007BFF; /* Set all buttons to blue */
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .back-button, .view-button {
            background-color: #007BFF; /* Blue background for the buttons */
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            padding: 12px 20px;
            margin-top: 10px;
            width: 100%; /* Ensure these buttons are not stretched and match the width of the form */
        }

        .back-button:hover, .view-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
    </style>
</head>
<body>

    <h1>Enter Equipment Usage Log</h1>

    <form method="POST">
        <label for="equipment_id">Equipment ID:</label>
        <input type="text" id="equipment_id" name="equipment_id" value="<?php echo $equipment_id; ?>" required>

        <label for="log_details">Log Details:</label>
        <textarea id="log_details" name="log_details" rows="4" required></textarea>

        <label for="assigned_date">Assigned Date:</label>
        <input type="date" id="assigned_date" name="assigned_date" required>

        <label for="returned_date">Returned Date:</label>
        <input type="date" id="returned_date" name="returned_date">

        <button type="submit">Submit Usage Log</button>

        <!-- View Usage Logs Button -->
        <button class="view-button" onclick="window.location.href='edit_usage_logs.php';">View/Edit</button>

        <!-- Go back to admin.php -->
        <button class="back-button" onclick="window.location.href='assignment.php';">Back to Admin</button>
    </form>

</body>
</html>
