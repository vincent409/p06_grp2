<?php
session_start();
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to create or delete profiles.");
}

// Handle profile deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete the profile from the database
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        // Redirect to the same page after deletion
        header("Location: create_profile.php?message=deleted");
        exit;
    } else {
        echo "Error deleting profile: " . $stmt->error;
    }
}

// Handle success message after deletion
if (isset($_GET['message']) && $_GET['message'] == 'deleted') {
    echo "<p style='color: green;'>Profile deleted successfully.</p>";
}

// Handle profile creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];
    $role_id = 3; // Assuming '3' corresponds to the Student role

    // Insert the new profile into the database
    $insert_sql = "INSERT INTO Profile (name, email, phone_number, department, role_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($insert_sql);
    $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $role_id);
    $stmt->execute();

    // Redirect after successful creation
    header("Location: create_profile.php?message=created");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create or Manage Profiles</title>
</head>
<body>
    <h1>Create New Student Profile</h1>
    <form action="create_profile.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required><br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required><br><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" id="phone_number"><br><br>

        <label for="department">Department:</label>
        <input type="text" name="department" id="department" required><br><br>

        <input type="submit" value="Create Profile">
    </form>

    <h2>Manage Profiles</h2>

    <table border="1">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Department</th>
            <th>Actions</th>
        </tr>
        <?php
        // Fetch and display profiles
        $fetch_sql = "SELECT id, name, email, phone_number, department FROM Profile";
        $result = $connect->query($fetch_sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['phone_number'] . "</td>";
                echo "<td>" . $row['department'] . "</td>";
                echo "<td>
                        <a href='create_profile.php?delete_id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this profile?\");'>Delete</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No profiles found.</td></tr>";
        }
        ?>
    </table>
</body>
</html>
