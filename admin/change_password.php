<?php
// Include the database configuration file
include('db_config.php');

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all required fields are set
    if(isset($_POST['user_id'], $_POST['new_password']) && !empty($_POST['user_id']) && !empty($_POST['new_password'])){
        // Sanitize user ID and new password to prevent SQL injection
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
        
        // Hash the new password (you should use a stronger hashing algorithm like bcrypt)
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the user's password in the database
        $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        if(mysqli_query($conn, $sql)){
            // Password changed successfully, redirect back to the users.php page
            header("Location: users.php");
            exit();
        } else{
            echo "Error updating record: " . mysqli_error($conn);
        }
    } else {
        // If required fields are not set, redirect to the users.php page
        header("Location: users.php");
        exit();
    }
} else {
    // If form is not submitted, redirect to the users.php page
    header("Location: users.php");
    exit();
}
?>
