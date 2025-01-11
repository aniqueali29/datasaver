<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Cronitor RUM -->
<script async src="https:
<script>
    window.cronitor = window.cronitor || function() { (window.cronitor.q = window.cronitor.q || []).push(arguments); };
    cronitor('config', { clientKey: '2cbf69d98369b4bdb21fb6458376fb8d' });
</script>


</head>

<body>

<?php


date_default_timezone_set('Asia/Karachi');


include '../connection/db_config.php';  
mysqli_query($conn, "SET time_zone = '+05:00';");


$current_date = new DateTime('now', new DateTimeZone('Asia/Karachi'));


$current_date_string = $current_date->format('Y-m-d H:i:s');


$sql_messages = "DELETE FROM messages WHERE delete_at IS NOT NULL AND delete_at <= '$current_date_string'";


$sql_ip_messages = "DELETE FROM ip_messages WHERE delete_at IS NOT NULL AND delete_at <= '$current_date_string'";


if ($conn->query($sql_messages) === TRUE) {
    
    
} else {
    
    
}


if ($conn->query($sql_ip_messages) === TRUE) {
    
    
} else {
    
    
}


$conn->close();


?>

</body>
</html>
