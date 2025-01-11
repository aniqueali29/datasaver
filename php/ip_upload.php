<?php
include('../connection/db_config.php');

header('Content-Type: application/json');

date_default_timezone_set('Asia/Karachi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $text_data = $_POST['text'] ?? '';

    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $original_filename = basename($file['name']); 
        $filename = uniqid() . "_" . basename($file['name']);
        $filetype = $file['type'];
        $filesize = $file['size'];
        $upload_datetime = date('Y-m-d H:i:s');
        $expired = 0;

        $delete_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
date_default_timezone_set('Asia/Karachi');


        if ($filesize <= 200 * 1024 * 1024) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
                exit;
            }

            $file_path = $upload_dir . $filename . $original_filename;
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("INSERT INTO shared_files (user_ip, original_filename, filename, filetype, filesize, text_data, upload_datetime, expired, delete_at) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssis", $user_ip, $original_filename, $filename, $filetype, $filesize, $text_data, $upload_datetime, $expired, $delete_at);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File size exceeds the maximum limit of 200MB.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file selected.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
