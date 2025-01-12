<?php
include('../connection/db_config.php');

if (isset($_GET['link'])) {
    $shared_link = $_GET['link'];

    $query = "SELECT sl.file_id, ud.filename, ud.email
              FROM shared_links sl
              INNER JOIN users_data ud ON sl.file_id = ud.id
              WHERE sl.shared_link = '$shared_link' 
              AND sl.expiration_time > NOW()";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $filename = $row['filename'];
        $email = $row['email'];

        // File path
        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
        $filePath = "../uploads/personal_files/$sanitizedEmail/$filename";

        if (file_exists($filePath)) {
            // Allow download or display
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            readfile($filePath);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid or expired link.";
    }
} else {
    echo "No link provided.";
}
?>
