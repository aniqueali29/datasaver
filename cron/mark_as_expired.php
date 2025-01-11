<?php

session_start();


include('../connection/db_config.php');

mysqli_query($conn, "SET time_zone = '+05:00';");

date_default_timezone_set('Asia/Karachi');


define('EXPIRATION_LIMIT_MINUTES', 15);


$query = "
    UPDATE `shared_files` 
    SET `expired` = 1 
    WHERE `delete_at` <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL '+5:00' HOUR_MINUTE) 
    AND `expired` = 0
";


if (mysqli_query($conn, $query)) {
    
    file_put_contents('cron_success.log', date('Y-m-d H:i:s') . " - Expired files flagged successfully.\n", FILE_APPEND);
} else {
    
    file_put_contents('cron_error.log', date('Y-m-d H:i:s') . ' - Error: ' . mysqli_error($conn) . "\n", FILE_APPEND);
}


mysqli_close($conn);


?>