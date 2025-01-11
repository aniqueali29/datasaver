<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials
$servername = "localhost";
$username = "datasaver";
$password = "Anique0ali@";
$dbname = "datasaver_datasaver";

// Ensure MySQLi extension is enabled
if (!function_exists('mysqli_connect')) {
    die("MySQLi extension is not enabled.");
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>
