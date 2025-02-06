<?php
include('../connection/db_config.php');

// Set timezone
mysqli_query($conn, "SET time_zone = '+05:00';");
date_default_timezone_set('Asia/Karachi');

// Query to select expired files
$selectExpiredQuery = "SELECT id, filename, original_filename FROM `shared_files` WHERE expired = 1";
$result = mysqli_query($conn, $selectExpiredQuery);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $fileId = intval($row['id']);
        $fileName = basename($row['filename']);
        $original_filename = basename($row['original_filename']);
        
        // Debugging: Print the file name
        echo "Database filename: $fileName\n";

        // Build the full file path
        $filePath = __DIR__ . '//../uploads/' . $fileName . $original_filename;

        // Debugging: Print the full file path
        echo "Looking for file at: $filePath\n";

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
                echo "Error deleting file '$fileName'. Check permissions.\n";
            }
        } else {
            // Debugging: If the file doesn't exist, print the full path for troubleshooting
            echo "File '$fileName' not found at: $filePath\n";
        }
    }
} else {
    echo "Error fetching expired files: " . mysqli_error($conn) . "\n";
}

// Close the database connection
mysqli_close($conn);
?>
