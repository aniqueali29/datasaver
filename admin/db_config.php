<?php
$servername = "localhost";
$username = "datasaver";
$password = "Anique0ali@";
$dbname = "datasaver_datasaver";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
