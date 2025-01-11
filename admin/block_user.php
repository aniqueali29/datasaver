<?php
// Include the database configuration file
include('db_config.php');

// Check if the user ID parameter is set in the URL
if(isset($_GET['id']) && !empty($_GET['id'])){
    // Sanitize the user ID to prevent SQL injection
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Update the 'blocked' field for the user in the database to block them
    $sql = "UPDATE users SET blocked = 1 WHERE id = $user_id";
    if(mysqli_query($conn, $sql)){
        // User blocked successfully, redirect back to the users.php page
        header("Location: users.php");
        exit();
    } else{
        echo "Error updating record: " . mysqli_error($conn);
    }
} else{
    // If the user ID parameter is not provided in the URL, redirect to the users.php page
    header("Location: users.php");
    exit();
}
?>
