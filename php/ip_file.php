<?php

session_start();
include('../connection/db_config.php');

set_time_limit(300); // 5 minutes

ini_set('memory_limit', '256M');

date_default_timezone_set('Asia/Karachi');
$name = "Join Us";

define('EXPIRATION_LIMIT', 15 * 60);

$query = "
    UPDATE `shared_files` 
    SET `expired` = 1 
    WHERE `upload_datetime` < NOW() - INTERVAL " . EXPIRATION_LIMIT . " SECOND 
    AND `expired` = 0
";

if (!mysqli_query($conn, $query)) {
    file_put_contents('cron_error.log', date('Y-m-d H:i:s') . ' - Error: ' . mysqli_error($conn) . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $response = array('success' => false, 'message' => '');

    $file = $_FILES['file'];
    $original_filename = basename($file['name']);
    $filename = basename($file['name']);
    $filetype = $file['type'];
    $filesize = $file['size'];
    $tmp_name = $file['tmp_name'];
    $text_data = isset($_POST['text']) ? $_POST['text'] : '';
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $created_at = date("Y-m-d H:i:s");
    $delete_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    define('MAX_FILE_SIZE', 200 * 1024 * 1024);
    if ($filesize > MAX_FILE_SIZE) {
        $response['message'] = "File size exceeds the 200MB limit.";
        echo json_encode($response);
        exit;
    }

    if (move_uploaded_file($tmp_name, "uploads/" . $filename)) {
        $stmt = $conn->prepare("INSERT INTO shared_files (filename, filetype, filesize, text_data, user_ip, upload_datetime, delete_at, expired) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssissss", $filename, $filetype, $filesize, $text_data, $user_ip, $created_at, $delete_at);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "File uploaded successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $response['message'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Failed to move the uploaded file.";
    }

    echo json_encode($response);
    exit;
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$user_ip = $_SERVER['REMOTE_ADDR'];

$ip_prefix = substr($user_ip, 0, strrpos($user_ip, '.'));
$totalQuery = "SELECT COUNT(*) as total FROM `shared_files` WHERE user_ip LIKE '$ip_prefix.%'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total'];

$pages = ceil($total / $limit);

$query = "SELECT * FROM `shared_files` WHERE user_ip LIKE '$ip_prefix.%' ORDER BY id DESC LIMIT $start, $limit";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    // echo "no query.";
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Files Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="path/to/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
    .actions {
        margin-bottom: 4px;

    }

    .drop-zone {
        border: 2px dashed #ccc;
        border-radius: 10px;
        color: #aaa;
        cursor: pointer;
        transition: border 0.3s ease, color 0.3s ease;
    }

    .drop-zone.dragover {
        border: 2px solid #007bff;
        color: #007bff;
    }
    </style>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-blue">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>IP Share</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="./ip_file.php">IP
                            Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="./dashboard.php">Personal Files</a></li>
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
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container mt-5">
        <div class="box1">
            <button class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#uploadModal" id="myButton"
                onclick="buttonAction()">SHARE FILE</button>
        </div>

        <div class="table-container mb-3">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>File Size</th>
                        <th>Text Data</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="fileTableBody">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
            $uploadedTime = strtotime($row['upload_datetime']);
            $currentTime = time();
            $remainingTime = 15 * 30 - ($currentTime - $uploadedTime); // Time left in seconds (30 minutes)

            if ($remainingTime < 0) {
                $remainingTime = 0;
            }
        ?>
                    <tr>
                        <td>
                            <?php
    $original_filename = $row['original_filename'];

    $displayName = (strlen($original_filename) > 15) ? substr($original_filename, 0, 15) . '...' : $original_filename;
    ?>
                            <span class="message-tooltip" style="text-decoration: none;"
                                data-fulltext="<?php echo htmlspecialchars($original_filename); ?>">
                                <?php echo htmlspecialchars($displayName); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo formatFileSize($row['filesize']); ?>
                        </td>

                        <td>
                            <?php
    $textData = $row['text_data'];
    $isExpired = $row['expired']; // Assuming `is_expired` is a boolean or integer field (1 = expired, 0 = not expired)

    if (!empty($textData)) {
        if ($isExpired) {
            ?>
                            <span class="badge bg-warning text-dark">Expired</span>
                            <?php
        } else {
            if (strlen($textData) > 18) {
                $displayText = substr($textData, 0, 18) . '...';
                ?>
                            <span>
                                <?php echo htmlspecialchars($displayText); ?>
                            </span>
                            <?php
            } else {
                ?>
                            <span>
                                <?php echo htmlspecialchars($textData); ?>
                            </span>
                            <?php
            }
        }
    } else {
        ?>
                            <span class="badge bg-danger">No Message Posted!</span>
                            <?php
    }
    ?>
                        </td>






                        <td>
                            <?php if ($row['expired'] == 0): ?>
                            <span class="badge bg-primary" id="timer-<?php echo $row['id']; ?>"></span>
                            <?php else: ?>
                            <span class="badge bg-danger">Expired</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php 
    if ($row['expired'] == 0): 
        $token = bin2hex(random_bytes(16));
        $secureFileName = $row['filename'] . $original_filename;
        $_SESSION['file_tokens'][$token] = $secureFileName;
        
        $downloadLink = "secure_download.php?file=" . urlencode($secureFileName) . "&token=" . $token;
    ?>
                            <a href="<?php echo $downloadLink; ?>" class="btn actions btn-success btn-sm">Download</a>
                            <?php else: ?>
                            <span class="btn actions btn-success btn-sm">Expired</span>
                            <?php endif; ?>

                            <br />

                            <button class="btn btn-danger btn-sm"
                                onclick="deleteFile(<?php echo $row['id']; ?>, '<?php echo $row['filename'] . $original_filename; ?>')">Delete</button>
                        </td>


                    </tr>

                    <script>
    <?php if ($row['expired'] == 0): ?>
    (function () {
        var uploadedTime = new Date("<?php echo $row['upload_datetime']; ?>").getTime();
        var countdownDate = uploadedTime + 15 * 60 * 1000; // 15 minutes from upload time

        var timerId = setInterval(function () {
            var now = new Date().getTime();
            var distance = countdownDate - now;

            if (distance <= 0) {
                clearInterval(timerId);
                document.getElementById("timer-<?php echo $row['id']; ?>").innerHTML = "Expired";

                // Mark the file as expired on the server
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "mark_as_expired.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.send("id=<?php echo $row['id']; ?>");

                // Update the status in the DOM
                var actionButton = document.querySelector(`#file-<?php echo $row['id']; ?> .actions .btn-success`);
                if (actionButton) {
                    actionButton.classList.add('disabled');
                    actionButton.innerHTML = 'Expired';
                }
            } else {
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("timer-<?php echo $row['id']; ?>").innerHTML = minutes + "m " + seconds + "s";
            }
        }, 1000);
    })();
    <?php else: ?>
    document.getElementById("timer-<?php echo $row['id']; ?>").innerHTML = "Expired";
    <?php endif; ?>
</script>



                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">NO DATA FOUND</td>
                    </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

        <!-- Modal for displaying full message -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel">Full Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modalMessageContent">
                        <!-- Full message will be displayed here dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal for Upload -->
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" id="timezone-offset" name="timezone_offset">
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel"
                aria-hidden="true">
                <input type="hidden" id="timezone-offset" name="timezone_offset">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="uploadModalLabel">SHARE FILE</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <!-- Drag and Drop Area -->
                                <!--<label for="file">Upload File</label>-->
                                <div id="dropZone" class="drop-zone text-center p-4 mb-3"
                                    style="border: 2px dashed #ccc; border-radius: 10px;">
                                    <p id="dropText" class="mb-0">Drag and drop files here or click to <span
                                            class="text-primary">upload</span></p>
                                    <input type="file" name="file" class="form-control d-none" id="file" required>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="text">Text Data</label>
                                <input type="text" name="text" class="form-control"
                                    placeholder="Enter any text associated with this file..." id="text">
                            </div>
                            <div class="progress" id="progressBarContainer" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%;" id="progressBar"
                                    data-content="0%"></div>
                            </div>
                        </div>
                        <input type="hidden" id="timezone-offset" name="timezone_offset">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="uploadButton">Upload</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="../js/ip_share.js"></script>





</body>

</html>

<?php
function formatFileSize($size) {
    if ($size >= 1024 * 1024) {
        return round($size / (1024 * 1024), 2) . ' MB';
    } else {
        return round($size / 1024, 2) . ' KB';
    }
}

include('../layout/footer.php');
?>