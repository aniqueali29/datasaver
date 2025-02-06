<?php
include('../connection/db_config.php');
session_start();

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

if (!isset($_SESSION['file_tokens'][$token])) {
    die("Unauthorized access.");
}

$secureFileName = $_SESSION['file_tokens'][$token];
$email = $_SESSION['email']; 

$sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
$filePath = "../uploads/personal_files/$sanitizedEmail/$secureFileName";

if (!file_exists($filePath)) {
    die("File not found.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($secureFileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

flush();
readfile($filePath);

unset($_SESSION['file_tokens'][$token]);

exit;
?>
