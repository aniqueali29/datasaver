<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['admin'])) {
    header("location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
        $filename = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $filetype = $_FILES['file']['type'];
        $filesize = $_FILES['file']['size'];

        // Move uploaded file to uploads directory
        if (move_uploaded_file($tmp_name, "uploads/" . $filename)) {
            // Update file info in database
            $update_sql = "UPDATE users_data SET filename='$filename', filetype='$filetype', filesize=$filesize WHERE id=$id";
            $conn->query($update_sql);
        }
    }

    if (isset($_POST['text'])) {
        $text = $_POST['text'];

        // Update text in database
        $update_sql = "UPDATE users_data SET text_data='$text' WHERE id=$id";
        $conn->query($update_sql);
    }

    header("location: admin_portal.php");
    exit;
}
?>
<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['admin'])) {
    header("location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
        $filename = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $filetype = $_FILES['file']['type'];
        $filesize = $_FILES['file']['size'];

        // Move uploaded file to uploads directory
        if (move_uploaded_file($tmp_name, "uploads/" . $filename)) {
            // Update file info in database
            $update_sql = "UPDATE users_data SET filename='$filename', filetype='$filetype', filesize=$filesize WHERE id=$id";
            $conn->query($update_sql);
        }
    }

    if (isset($_POST['text'])) {
        $text = $_POST['text'];

        // Update text in database
        $update_sql = "UPDATE users_data SET text_data='$text' WHERE id=$id";
        $conn->query($update_sql);
    }

    header("location: admin_portal.php");
    exit;
}
?>
<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['admin'])) {
    header("location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
        $filename = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $filetype = $_FILES['file']['type'];
        $filesize = $_FILES['file']['size'];

        // Move uploaded file to uploads directory
        if (move_uploaded_file($tmp_name, "uploads/" . $filename)) {
            // Update file info in database
            $update_sql = "UPDATE users_data SET filename='$filename', filetype='$filetype', filesize=$filesize WHERE id=$id";
            $conn->query($update_sql);
        }
    }

    if (isset($_POST['text'])) {
        $text = $_POST['text'];

        // Update text in database
        $update_sql = "UPDATE users_data SET text_data='$text' WHERE id=$id";
        $conn->query($update_sql);
    }

    header("location: admin_portal.php");
    exit;
}
?>
