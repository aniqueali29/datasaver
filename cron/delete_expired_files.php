<?php
include('../connection/db_config.php');

// Set timezone
mysqli_query($conn, "SET time_zone = '+05:00';");
date_default_timezone_set('Asia/Karachi');

// Query to select expired files
$selectExpiredQuery = "SELECT id, file_name FROM `shared_files` WHERE expired = 1";
$result = mysqli_query($conn, $selectExpiredQuery);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $fileId = intval($row['id']);
        $fileName = basename($row['file_name']);
        $filePath = __DIR__ . '/../uploads/' . $fileName;

        // Check if the file exists
        if (file_exists($filePath)) {
            // Attempt to delete the file
            if (unlink($filePath)) {
                echo "File '$fileName' deleted successfully.\n";

                // Delete the database entry
                $deleteQuery = $conn->prepare("DELETE FROM `shared_files` WHERE id = ?");
                if ($deleteQuery) {
                    $deleteQuery->bind_param('i', $fileId);
                    if ($deleteQuery->execute()) {
                        echo "Database entry for '$fileName' deleted successfully.\n";
                    } else {
                        echo "Error deleting database entry for '$fileName': " . $deleteQuery->error . "\n";
                    }
                    $deleteQuery->close();
                } else {
                    echo "Error preparing database query for '$fileName'.\n";
                }
            } else {
                echo "Error deleting file '$fileName'.\n";
            }
        } else {
            echo "File '$fileName' not found.\n";
        }
    }
} else {
    echo "Error fetching expired files: " . mysqli_error($conn) . "\n";
}

// Close the database connection
mysqli_close($conn);
?>
