<?php
$connect = mysqli_connect("localhost", "root", "", "amc") or die("Cannot connect to database");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}
?>