<?php
// Include the database configuration file
include('../connection/db_config.php');

// Check if the file ID is passed as a GET parameter
if (isset($_GET['file_id'])) {
    $file_id = intval($_GET['file_id']);

    // Retrieve the file details from the database
    $query = "SELECT * FROM `users_data` WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $file = $result->fetch_assoc();

        $filePath = '../uploads/personal_files/' . $file['filename'];
        $fileName = $file['filename'];

        // Check if the file exists on the server
        if (file_exists($filePath)) {
            // Set headers to initiate file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Output the file content
            readfile($filePath);
            exit;
        } else {
            echo "File not found on the server.";
        }
    } else {
        echo "Invalid file ID or file does not exist.";
    }
} else {
    echo "No file ID provided.";
}
?>
