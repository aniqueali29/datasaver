<?php
include('../connection/db_config.php');
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
    $file_id = intval($_POST['file_id']);
    $expiration_time = date('Y-m-d H:i:s', strtotime('+30 minutes')); 

    
    $unique_link = bin2hex(random_bytes(16)); 

    
    $email = $_SESSION['email'];
    $query = "SELECT * FROM users_data WHERE id = $file_id AND email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        
        $insertQuery = "INSERT INTO shared_links (file_id, shared_link, expiration_time)
                        VALUES ($file_id, '$unique_link', '$expiration_time')";
        
        if (mysqli_query($conn, $insertQuery)) {
            $shared_url = "https://datasaver.online/php/view_file.php?link=$unique_link";
            echo json_encode(['success' => true, 'url' => $shared_url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error generating link.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file or permission denied.']);
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['link'])) {
    $sharedLink = $_GET['link'];

    $query = "SELECT ud.filename, ud.email FROM users_data ud 
              JOIN shared_links sl ON sl.file_id = ud.id 
              WHERE sl.shared_link = '$sharedLink' 
              AND sl.expiration_time > NOW()";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $filename = $row['filename'];
        $email = $row['email'];
        $filePath = "../uploads/personal_files/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email) . "/" . $filename;

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            readfile($filePath);
            exit;
        } else {
            die('Error: File not found.');
        }
    } else {
        die('Error: Invalid or expired link.');
    }
}



echo json_encode(['success' => false, 'message' => 'Invalid request.']);
?>
