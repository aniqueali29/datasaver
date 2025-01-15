<?php
session_start();
include('../connection/db_config.php');

date_default_timezone_set('Asia/Karachi');


error_reporting(0);


if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $user_ip = $_SERVER['REMOTE_ADDR'];
}


$query = "SELECT * FROM `shared_files` WHERE user_ip='$user_ip' ORDER BY id DESC LIMIT 10"; 
$result = mysqli_query($conn, $query);

$response = array('success' => false, 'data' => array());

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $response['data'][] = array(
            'id' => $row['id'],
            'filename' => $row['filename'],
            'filesize' => $row['filesize'],
            'upload_datetime' => date("Y-m-d H:i:s A", strtotime($row['upload_datetime'])),
            'text_data' => $row['text_data'],
            'expired' => $row['expired']
        );
    }
    $response['success'] = true;
} else {
    $response['message'] = 'No files found';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
