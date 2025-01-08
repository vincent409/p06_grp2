<?php
// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if (isset($_POST['login-button'])) {
    // Retrieve form inputs
    $equipment_name = $_POST['equipment_name'];
    $equipment_type = $_POST['equipment_type'];
    $status_id = $_POST['status_id'];
    $purchase_date = $_POST['purchase_date'];
    $model_number = $_POST['model_number'];

    // Insert new equipment into the database
    $query = $connect->prepare("INSERT INTO equipment (name, type, status_id, purchase_date, model_number) VALUES (?, ?, ?, ?, ?)");

    // Bind parameters: "ssiss" means:
    // s = string, s = string, i = integer, s = string, s = string
    $query->bind_param("ssiss", $equipment_name, $equipment_type, $status_id, $purchase_date, $model_number);

    // Execute the query
    if ($query->execute()) {
        echo "New equipment added successfully!";
    } else {
        echo "Error: " . $query->error;
    }

    // Close the statement
    $query->close();
}

// Fetch statuses for the dropdown
$status_result = mysqli_query($connect, "SELECT id, name FROM Status");
if (!$status_result) {
    die("Failed to fetch statuses: " . mysqli_error($connect));
}

// Close the database connection
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
</head>
<body>
    <h1>Add New Equipment</h1>
    <form method="POST" action="add-equipment.php">
        <label for="equipment_name">Equipment Name:</label><br>
        <input type="text" id="equipment_name" name="equipment_name" required><br><br>

        <label for="equipment_type">Equipment Type:</label><br>
        <input type="text" id="equipment_type" name="equipment_type" required><br><br>

        <label for="status_id">Status:</label><br>
        <select id="status_id" name="status_id" required>
            <option value="">Select Status</option>
            <?php
            // Populate the dropdown with statuses
            while ($row = mysqli_fetch_assoc($status_result)) {
                echo "<option value='{$row['id']}'>{$row['name']}</option>";
            }
            ?>
        </select><br><br>

        <label for="purchase_date">Purchase Date:</label><br>
        <input type="date" id="purchase_date" name="purchase_date" required><br><br>

        <label for="model_number">Model Number:</label><br>
        <input type="text" id="model_number" name="model_number" required><br><br>

        <button type="submit" name="login-button">Add Equipment</button>
    </form>
</body>
</html>
