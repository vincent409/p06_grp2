<?php
session_start();
$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}
// Check if the user is an Admin or Facility Manager
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Facility Manager') {
    die("You do not have permission to create profiles.");
}
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
    header("Location: manage_profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Profile</title>
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
</body>
</html>
