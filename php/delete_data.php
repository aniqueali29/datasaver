<?php 
session_start();
include '../connection/db_config.php';

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$email = $_SESSION['email']; // Retrieve user email from session

// Handle file deletion
if (isset($_GET['id'])) {
    $fileId = intval($_GET['id']); // Ensure ID is an integer to prevent SQL injection

    // Fetch file details from DB
    $fileQuery = "SELECT * FROM `users_data` WHERE id='$fileId' AND email='$email'";
    $fileResult = mysqli_query($conn, $fileQuery);

    if ($fileRow = mysqli_fetch_assoc($fileResult)) {
        $secureFileName = $fileRow['filename'];
        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
        $filePath = "../uploads/personal_files/$sanitizedEmail/$secureFileName";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from database
        $deleteQuery = "DELETE FROM `users_data` WHERE id='$fileId' AND email='$email'";
        if (mysqli_query($conn, $deleteQuery)) {
            // echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
            header("location: ./dashboard.php");
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting file from database.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
    }
    exit;
}
?>
