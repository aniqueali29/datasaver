<?php
session_start();
include('../connection/db_config.php'); 

if (!isset($_SESSION['email'])) {
    header("location: ../php/login.php");
    exit;
}

$email = $_SESSION['email'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
} else {
    header("location: ../php/login.php");
    exit;
}

define('MAX_FILE_SIZE', 200 * 2048 * 2048);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file']) && isset($_POST['text'])) {
    $response = array('success' => false, 'message' => '');

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = "File upload failed with error code: " . $_FILES['file']['error'];
    } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
        $response['message'] = "File size exceeds the 200MB limit.";
    } else {
        $filename = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $filetype = $_FILES['file']['type'];
        $filesize = $_FILES['file']['size'];
        $upload_datetime = date('Y-m-d h:i:s A');
        $text = $_POST['text'];

        if (move_uploaded_file($tmp_name, "../uploads/" . $filename)) {
            // Insert file info and text into database along with upload date and time
            $sql = "INSERT INTO users_data (email, filename, filetype, filesize, upload_datetime, text_data) VALUES ('$email', '$filename', '$filetype', $filesize, '$upload_datetime', '$text')";
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = "File uploaded successfully.";
            } else {
                $response['message'] = "Database insert failed: " . $conn->error;
            }
        } else {
            $response['message'] = "Failed to move uploaded file.";
        }
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_data'])) {
    $id = $_POST['id'];
    $text = $_POST['text'];
    $response = array('success' => false, 'message' => '');

    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            $response['message'] = "File size exceeds the 100MB limit.";
        } else {
            $filename = $_FILES['file']['name'];
            $tmp_name = $_FILES['file']['tmp_name'];
            $filetype = $_FILES['file']['type'];
            $filesize = $_FILES['file']['size'];
            $upload_datetime = date('Y-m-d h:i:s');

            if (move_uploaded_file($tmp_name, "../uploads/" . $filename)) {
                $sql = "UPDATE users_data SET filename='$filename', filetype='$filetype', filesize=$filesize, upload_datetime='$upload_datetime', text_data='$text' WHERE id=$id";
            } else {
                $response['message'] = "Failed to move uploaded file.";
            }
        }
    } else {
        $sql = "UPDATE users_data SET text_data='$text' WHERE id=$id";
    }

    if (!isset($response['message']) && $conn->query($sql)) {
        $response['success'] = true;
        $response['message'] = "Data updated successfully.";
    } elseif (!isset($response['message'])) {
        $response['message'] = "Database update failed: " . $conn->error;
    }

    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
        <!-- <link rel="stylesheet" href="../datasaver/style.css"> -->
    <link rel="stylesheet" href="path/to/sweetalert2.min.css">

    <style>
        :root {
    --body-color: #FFFFFF;
    --nav-background: #4D7D28;
    /* --nav-background:#F7F7F7; */
    --nav-text-color: #FFFFFF;
    /* use with --nav-background:#F7F7F7 */
    /* --nav-text-color:#333333; */
    --button-color: #629F33;
    --secondary-color: #F5F5DC;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background-color: var(--body-color);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    height: 100vh;
}



nav {
    background-color: var(--nav-background);
    font-size: 16px !important;
}



/* .logo img{
    max-width: 100px;
} */

nav a {
    color: var(--nav-text-color) !important;
    /* padding: 14px 16px; */
    display: block;
    font-weight: 600 !important;
}

.nav-link {
    padding: 5px 0px !important;
    position: relative;
}

.nav-link::after {
    content: "";
    position: absolute;
    left: 0px;
    bottom: -7px;
    width: 0%;
    height: 5px;
    background-color: var(--nav-text-color);
    transition: all .2s ease-in-out;
}

.nav-link:hover::after {
    width: 100%;
}

.nav_button {
    width: 150px !important;
    border-radius: 20px !important;
    background-color: #629F33;
    color: #FFFFFF;
    border: 1px solid #FFFFFF;
    padding: 8px 16px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.4s ease, transform 0.3s ease;
}

.nav_button:hover {
    background-color: #4A7A28;
    transform: scale(1.05);
}

@media (max-width: 998px) {
    .nav-link {
        display: inline-block !important;
        margin-bottom: 10px;
    }
}
    </style>
    

  </head>

<body>
<!-- nav start -->

<nav class="navbar navbar-expand-lg ">
    <div class="container-fluid">
      <a class="navbar-brand" href="./index.html">DataSaver <!-- insert logo here:--> <!--  <figure class="logo d-flex align-items-center gap-3"><img src="./logo.png" alt=""></figure> --></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="mx-lg-3 nav-link active" aria-current="page" href="../index.html">Home</a>
          </li>
          <li class="nav-item">
            <a class="mx-lg-3 nav-link active" aria-current="page" href="../php/ip_file.php">IP Share</a>
          </li>
          <li class="nav-item">
            <a class="mx-lg-3 nav-link active" aria-current="page" href="../php/dashboard.php">Personal Files</a>
          </li>
          <li class="nav-item">
            <a class="mx-lg-3 nav-link active" aria-current="page" href="#">How It Works</a>
          </li>
          <li class="nav-item">
            <a class="mx-lg-3 nav-link active" aria-current="page" href="#">About Us</a>
          </li>
          <li class="nav-item d-none">
            <a class="mx-lg-2 nav-link active" aria-current="page" href="#">Dashboard</a>
          </li>
        </ul>
        <form class="d-flex" role="search">
            <?php
            if (isset($_SESSION['email'])) {
                echo '<li class="nav_button"><a href="logout.php" class="dropdown-item">Logout</a></li>';
            } else {
                echo '<button class="nav_button btn btn-success" type="submit">Login / Sign Up</button>';
            }
            ?>
        </form>
      </div>
    </div>
  </nav>

  <!-- nav end -->




