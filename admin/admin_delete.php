<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['admin'])) {
    header("location: admin_login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch file info to delete from uploads directory
    $fetch_sql = "SELECT * FROM users_data WHERE id=$id";
    $fetch_result = $conn->query($fetch_sql);
    $fetch_row = $fetch_result->fetch_assoc();
    $filename_to_delete = $fetch_row['filename'];

    // Delete file from uploads directory
    if ($filename_to_delete) {
        unlink("uploads/" . $filename_to_delete);
    }

    // Delete data from database
    $delete_sql = "DELETE FROM users_data WHERE id=$id";
    $conn->query($delete_sql);
    header("location: admin_portal.php");
}
?>
