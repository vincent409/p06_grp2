<?php
session_start();

// Check if the user has the Admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== "Admin" && $_SESSION['role'] !== "Facility Manager")) {
    // Redirect the user to login page or show an error message
    header("Location: login.php");
    exit(); // Stop further execution
}

// Check if the equipment_id is provided via GET
if (!isset($_GET['equipment_id']) || empty($_GET['equipment_id'])) {
    die("Equipment ID is required.");
}

// Get the equipment_id from the query string
$equipment_id = $_GET['equipment_id'];

// Establish a database connection
$connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to the database");

// Fetch the equipment details and current assignment (if any)
$sql = "
    SELECT 
        Equipment.id, 
        Equipment.name, 
        Loan.profile_id, 
        Loan.status_id
    FROM Equipment
    LEFT JOIN Loan ON Equipment.id = Loan.equipment_id
    WHERE Equipment.id = ?";
    
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $equipment_id); // Bind the equipment_id to the query
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No such equipment found.");
}

$equipment = $result->fetch_assoc(); // Fetch the equipment details

// Fetch all profiles (users) and statuses for the dropdown options
$profiles_sql = "SELECT id, email FROM Profile";
$statuses_sql = "SELECT id, name FROM Status";

$profiles_result = mysqli_query($connect, $profiles_sql);
$statuses_result = mysqli_query($connect, $statuses_sql);

// Close the database connection
mysqli_close($connect);

// Check if form was submitted to update the equipment assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated profile_id and status_id from the form
    $new_profile_id = $_POST['profile_id'];
    $new_status_id = $_POST['status_id'];

    // Re-establish connection to update the database
    $connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to the database");

    // Update the Loan table with the new profile_id and status_id
    $update_sql = "
        INSERT INTO Loan (equipment_id, profile_id, status_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE profile_id = ?, status_id = ?";
    
    $stmt = $connect->prepare($update_sql);
    $stmt->bind_param("iiiii", $equipment_id, $new_profile_id, $new_status_id, $new_profile_id, $new_status_id);
    
    if ($stmt->execute()) {
        echo "Equipment assignment updated successfully!";
    } else {
        echo "Error updating the assignment: " . $stmt->error;
    }

    // Close the connection after updating
    mysqli_close($connect);
}

// Check if the delete button was clicked
if (isset($_POST['delete_equipment'])) {
    // Re-establish connection to delete the equipment assignment
    $connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to the database");

    // Delete the equipment from the Loan table
    $delete_sql = "DELETE FROM Loan WHERE equipment_id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $equipment_id);
    
    if ($stmt->execute()) {
        echo "Equipment assignment deleted successfully!";
    } else {
        echo "Error deleting the equipment assignment: " . $stmt->error;
    }

    // Close the connection after deleting
    mysqli_close($connect);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Equipment</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Assign Equipment</h1>

    <form method="POST" action="assign-equipment.php?equipment_id=<?php echo $equipment_id; ?>">
        <table>
            <tr>
                <td><label for="equipment_name">Equipment Name:</label></td>
                <td><input type="text" id="equipment_name" name="equipment_name" value="<?php echo htmlspecialchars($equipment['name']); ?>" readonly></td>
            </tr>
            <tr>
                <td><label for="profile_id">Assign to User (Profile ID):</label></td>
                <td>
                    <select name="profile_id" id="profile_id">
                        <?php
                        // Populate the profile dropdown
                        while ($profile = mysqli_fetch_assoc($profiles_result)) {
                            $selected = ($profile['id'] == $equipment['profile_id']) ? 'selected' : '';
                            echo "<option value='{$profile['id']}' $selected>{$profile['email']}</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="status_id">Equipment Status:</label></td>
                <td>
                    <select name="status_id" id="status_id">
                        <?php
                        // Populate the status dropdown
                        while ($status = mysqli_fetch_assoc($statuses_result)) {
                            $selected = ($status['id'] == $equipment['status_id']) ? 'selected' : '';
                            echo "<option value='{$status['id']}' $selected>{$status['name']}</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><button type="submit">Update Assignment</button></td>
            </tr>
        </table>
    </form>

    <!-- Only show the delete button if the user is an admin -->
        <form method="POST" action="assign-equipment.php?equipment_id=<?php echo $equipment_id; ?>">
            <button type="submit" name="delete_equipment" onclick="return confirm('Are you sure you want to delete this equipment assignment?');">Delete Assignment</button>
        </form>


    <button onclick="window.location.href='admin.php';">Back to Assignment List</button>
</body>
</html>
