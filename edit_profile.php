<?php
session_start();
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to edit or delete profiles.");
}

// Fetch the profile to edit
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT id, name, email, phone_number, department FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $email, $phone_number, $department);
    $stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];

    $update_sql = "UPDATE Profile SET name = ?, email = ?, phone_number = ?, department = ? WHERE id = ?";
    $stmt = $connect->prepare($update_sql);
    $stmt->bind_param("ssssi", $name, $email, $phone_number, $department, $id);
    $stmt->execute();
    
    // Redirect after successful update
    header("Location: manage_profile.php?message=updated");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Redirect after successful deletion
        header("Location: manage_profile.php?message=deleted");
        exit;
    } else {
        echo "Error deleting profile: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
</head>
<body>
    <h1>Edit Profile</h1>
    <form action="edit_profile.php" method="POST">
        <!-- Pass the ID as a hidden field -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo $name; ?>" required><br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo $email; ?>" required><br><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" value="<?php echo $phone_number; ?>"><br><br>

        <label for="department">Department:</label>
        <input type="text" name="department" value="<?php echo $department; ?>" required><br><br>

        <input type="submit" name="update" value="Update Profile">
    </form>

    <form action="edit_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this profile?');">
        <!-- Pass the ID as a hidden field -->
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="delete" value="1">
        <input type="submit" value="Delete Profile" style="background-color: red; color: white;">
    </form>
</body>
</html>
