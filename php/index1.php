<?php

session_start();

if (isset($_SESSION['email'])) {
    header("location: ./dashboard.php");
    exit;
} else {
    header("location: ./login.php");
    exit;
}
?>
