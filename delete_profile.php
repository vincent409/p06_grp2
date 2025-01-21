<?php
session_start();

$connect = mysqli_connect("localhost", "root", "", "amc");

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    die("You do not have permission to delete profiles.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the profile
    $delete_sql = "DELETE FROM Profile WHERE id = ?";
    $stmt = $connect->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Redirect after successful deletion
    header("Location: manage_profile.php");
    exit;
}
?>
