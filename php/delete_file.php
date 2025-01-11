<?php
session_start();
include '../connection/db_config.php';

if (isset($_GET['id'])) {
    $data_id = $_GET['id'];
    
    
    $delete_query = "DELETE FROM shared_files WHERE id='$data_id'";
    if ($conn->query($delete_query) === TRUE) {
        
        header("Location: ./ip_file.php");
    } else {
        echo "Error deleting data: " . $conn->error;
    }
}

if (isset($_POST['id']) && isset($_POST['file'])) {
    $fileId = intval($_POST['id']);
    $fileName = basename($_POST['file']); 
    $filePath = __DIR__ . '/../uploads/' . $fileName;

    
    if (file_exists($filePath)) {
        
        if (unlink($filePath)) {
            
            $stmt = $conn->prepare("DELETE FROM shared_files WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $fileId);
                if ($stmt->execute()) {
                    echo "File deleted successfully.";
                } else {
                    echo "Error: Unable to delete the database entry.";
                }
                $stmt->close();
            } else {
                echo "Error: Database query preparation failed.";
            }
        } else {
            echo "Error: Unable to delete the file.";
        }
    } else {
        echo "Error: File not found.";
    }
} else {
    echo "Invalid request.";
}
?>
