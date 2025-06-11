<?php
$servername = "localhost"; // Or your db host
$username = "root";        // Your db username
$password = "root";            // Your db password
$dbname = "medicare_db";   // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>