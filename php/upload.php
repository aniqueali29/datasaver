<?php
include('../connection/db_config.php');
session_start();

$response = array('success' => false, 'data' => array(), 'message' => '');

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $textData = $_POST['text'];
    $email = $_SESSION['email']; 

    // Sanitize email for directory name (replace special characters)
    $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);

    $filename = basename($file['name']);
    $filetype = $file['type'];
    $filesize = $file['size'];
    $uploadDatetime = date('Y-m-d H:i:s');
    
    // Create a user-specific directory
    $targetDir = "../uploads/personal_files/$sanitizedEmail/";
    $targetFile = $targetDir . $filename;

    // Check if the directory exists, if not, create it
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);  // Create the directory with proper permissions
    }

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $query = "INSERT INTO users_data (email, filename, filetype, filesize, upload_datetime, text_data)
                  VALUES ('$email', '$filename', '$filetype', '$filesize', '$uploadDatetime', '$textData')";

        if (mysqli_query($conn, $query)) {
            $response['success'] = true;
            $response['data'] = array(
                'id' => mysqli_insert_id($conn),
                'filename' => $filename,
                'filetype' => $filetype,
                'filesize' => formatFileSize($filesize),
                'upload_datetime' => $uploadDatetime,
                'text_data' => $textData,
                'directory' => $targetDir // Return the directory info
            );
        } else {
            $response['message'] = "Database error: " . mysqli_error($conn);
        }
    } else {
        $response['message'] = "Error uploading file.";
    }
} else {
    $response['message'] = "No file uploaded.";
}

echo json_encode($response);

function formatFileSize($size) {
    if ($size >= 1024 * 1024) {
        return round($size / (1024 * 1024), 2) . ' MB';
    } else {
        return round($size / 1024, 2) . ' KB';
    }
}
?>
