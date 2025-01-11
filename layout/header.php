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

// Define maximum file size in bytes (100MB)
define('MAX_FILE_SIZE', 200 * 2048 * 2048); // 100MB in bytes

// Upload file and text
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file']) && isset($_POST['text'])) {
    $response = array('success' => false, 'message' => '');

    // Check for file upload errors
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

        // Move uploaded file to uploads directory
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

// Update Data both Files & Text
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_data'])) {
    $id = $_POST['id'];
    $text = $_POST['text'];
    $response = array('success' => false, 'message' => '');

    // Check if a new file is uploaded
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            $response['message'] = "File size exceeds the 100MB limit.";
        } else {
            $filename = $_FILES['file']['name'];
            $tmp_name = $_FILES['file']['tmp_name'];
            $filetype = $_FILES['file']['type'];
            $filesize = $_FILES['file']['size'];
            $upload_datetime = date('Y-m-d h:i:s');

            // Move uploaded file to uploads directory
            if (move_uploaded_file($tmp_name, "uploads/" . $filename)) {
                // Update file info and text in the database
                $sql = "UPDATE users_data SET filename='$filename', filetype='$filetype', filesize=$filesize, upload_datetime='$upload_datetime', text_data='$text' WHERE id=$id";
            } else {
                $response['message'] = "Failed to move uploaded file.";
            }
        }
    } else {
        // Update only text data if no new file is uploaded
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
    <link rel="stylesheet" href="path/to/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    

  </head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-blue">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>Dashboard</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../php/view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="../php/ip_file.php">IP Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="../php/dashboard.php">Personal Files</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://via.placeholder.com/30" alt="User Avatar" class="rounded-circle me-1">
                            <!-- Username -->
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <!-- <li><a class="dropdown-item" href="#">Logout</a></li> -->
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo '<li class="nav-but"><a href="logout.php" class="dropdown-item">Logout</a></li>';
                            } else {
                                // User is not logged in, show the Signup & Login buttons
                                echo '<li class="nav-but"><a href="signup.php" class="dropdown-item">Sign Up</a></li>';
                                echo '<li class="nav-but"><a href="login.php" class="dropdown-item">Login</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
