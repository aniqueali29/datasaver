<?php
session_start();
include '../db_config.php';

if (isset($_GET['id'])) {
    $data_id = $_GET['id'];
    
    
    $delete_query = "DELETE FROM users_data WHERE id='$data_id'";
    if ($conn->query($delete_query) === TRUE) {
        
        header("Location: ./dashboard.php");
    } else {
        echo "Error deleting data: " . $conn->error;
    }
}
?>
