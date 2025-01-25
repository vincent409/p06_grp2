<?php
// Creates the database and necessary tables

require_once "database.connection.php";  // Ensure config.php exists with $db_hostname, $db_username, $db_password, and $db_database variables

function printerror($message, $con) {
    echo "<pre>";
    echo "$message<br>";
    if ($con) echo "FAILED: " . mysqli_error($con) . "<br>";
    echo "</pre>";
}

function printok($message) {
    echo "<pre>";
    echo "$message<br>";
    echo "OK<br>";
    echo "</pre>";
}

try {
    $con = mysqli_connect($db_hostname, $db_username, $db_password);
} catch (Exception $e) {
    printerror($e->getMessage(), $con);
}

if (!$con) {
    printerror("Connecting to $db_hostname", $con);
    die();
}

// Drop and create the database
$query = "DROP DATABASE IF EXISTS " . $db_database;
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

$query = "CREATE DATABASE " . $db_database;
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

$result = mysqli_select_db($con, $db_database);
if (!$result) {
    printerror("Selecting $db_database", $con);
    die();
} else {
    printok("Selecting $db_database");
}

// Create the User table
$query = "
CREATE TABLE IF NOT EXISTS User (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    password VARCHAR(255) NOT NULL,
    user_id BIGINT UNIQUE
);";
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

// Create the Profile table
$query = "
CREATE TABLE IF NOT EXISTS Profile (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone_number NUMERIC,
    department VARCHAR(100),
    user_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES User(id)
);";
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

// Create the Equipment table
$query = "
CREATE TABLE IF NOT EXISTS Equipment (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    purchase_date TIMESTAMP,
    status VARCHAR(50),
    model_number VARCHAR(100)
);";
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

// Create the Loan table
$query = "
CREATE TABLE IF NOT EXISTS Loan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    equipment_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES Profile(id),
    FOREIGN KEY (equipment_id) REFERENCES Equipment(id)
);";
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

// Create the Usage_Log table with fixed returned_date issue
$query = "
CREATE TABLE IF NOT EXISTS Usage_Log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    equipment_id BIGINT,
    log_details VARCHAR(1000),
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_date TIMESTAMP NULL,
    FOREIGN KEY (equipment_id) REFERENCES Equipment(id)
);";
$result = mysqli_query($con, $query);
if (!$result) {
    printerror($query, $con);
    die();
} else {
    printok($query);
}

mysqli_close($con);
printok("Closing connection");

?>