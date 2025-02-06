<?php
session_start();
include('../connection/db_config.php');

set_time_limit(300);
ini_set('memory_limit', '256M');

$name = "Join Us"; 

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    $sql = "SELECT name FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $name = htmlspecialchars($user['name']); 
    }
    
    $stmt->close();
}


define('EXPIRATION_LIMIT', 15 * 60); // 15 minutes

// Secure session handling
session_regenerate_id(true);

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Expire old files
$query = "UPDATE `shared_files` SET `expired` = 1 WHERE `upload_datetime` < NOW() - INTERVAL ? SECOND AND `expired` = 0";
$stmt = $conn->prepare($query);
$expiration_limit = EXPIRATION_LIMIT;
$stmt->bind_param("i", $expiration_limit);

$stmt->execute();
$stmt->close();

// File upload handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $response = ['success' => false, 'message' => ''];

    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = "Invalid CSRF token.";
        echo json_encode($response);
        exit;
    }

    $file = $_FILES['file'];
    $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filesize = $file['size'];
    $tmp_name = $file['tmp_name'];
    $text_data = htmlspecialchars($_POST['text'] ?? '', ENT_QUOTES, 'UTF-8');
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $created_at = date("Y-m-d H:i:s");
    $delete_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    define('MAX_FILE_SIZE', 200 * 1024 * 1024);
    $allowed_extensions = ['jpg', 'png', 'pdf', 'txt', 'docx', 'xlsx', 'zip']; // Allowed file types

    if (!in_array($extension, $allowed_extensions)) {
        $response['message'] = "Invalid file type.";
        echo json_encode($response);
        exit;
    }

    if ($filesize > MAX_FILE_SIZE) {
        $response['message'] = "File size exceeds the 200MB limit.";
        echo json_encode($response);
        exit;
    }

    // Secure file name
    $filename = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $original_filename) . ".$extension";
    $upload_path = "uploads/" . $filename;

    if (move_uploaded_file($tmp_name, $upload_path)) {
        $stmt = $conn->prepare("INSERT INTO shared_files (filename, filetype, filesize, text_data, user_ip, upload_datetime, delete_at, expired) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssissss", $filename, $extension, $filesize, $text_data, $user_ip, $created_at, $delete_at);

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

// Secure Pagination Handling
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Secure IP filtering for user
$user_ip = $_SERVER['REMOTE_ADDR'];
$ip_prefix = substr($user_ip, 0, strrpos($user_ip, '.'));

// Securely get total count
$totalQuery = "SELECT COUNT(*) as total FROM `shared_files` WHERE user_ip LIKE CONCAT(?, '%')";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param("s", $ip_prefix);
$stmt->execute();
$result = $stmt->get_result();
$totalRow = $result->fetch_assoc();
$total = $totalRow['total'];
$pages = ceil($total / $limit);
$stmt->close();

// Secure fetching of files
$query = "SELECT * FROM `shared_files` WHERE user_ip LIKE CONCAT(?, '%') ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $ip_prefix, $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Files Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="path/to/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/nav.css">
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

    .btn-danger {
        display: inline-block !important;
    }

    .dropdown-menu {
        background-color: #E3F2FD !important;
        border: none;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        min-width: 180px;
        padding: 8px 0;
    }

    .dropdown-item {
        padding: 10px 20px;
        font-weight: 500;
        color: #333 !important;
        transition: all 0.3s ease-in-out;
        border-radius: 5px;
    }

    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.5);
        transform: scale(1.05);
        font-weight: 600;
    }

    .nav-link.dropdown-toggle {
        font-weight: bold;
        color: #333 !important;
        padding: 8px 15px;
        border-radius: 5px;
        transition: all 0.3s ease-in-out;
    }

    .nav-link.dropdown-toggle:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }
    </style>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark ">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>
                    <figure class="logo d-flex align-items-center gap-3"><img src="../assets/img/logo.png" alt="">
                    </figure>
                </h1>
            </a>
            <button class="navbar-toggler" style="filter: invert(1);" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link" aria-current="page" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="./ip_file.php">IP Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="./dashboard.php">Personal Files</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo '<li class="nav-but"><a href="#" class="dropdown-item">Profile</a></li>';
                                echo '<li class="nav-but"><a href="./logout.php" class="dropdown-item">Logout</a></li>';
                            } else {
                                echo '<li class="nav-but"><a href="./signup.php" class="dropdown-item">Sign Up</a></li>';
                                echo '<li class="nav-but"><a href="./login.php" class="dropdown-item">Login</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <div class="container mb-5">
        <div class="box1">
            <button class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#uploadModal" id="myButton"
                onclick="buttonAction()">
                SHARE FILE
            </button>
        </div>

        <div class="table-container">
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
                            $remainingTime = 15 * 30 - ($currentTime - $uploadedTime);
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
                                    $isExpired = $row['expired'];
                                    if (!empty($textData)) {
                                        if ($isExpired) {
                                ?>
                            <span class="badge bg-warning text-dark">Expired</span>
                            <?php
                                        } else {
                                            if (strlen($textData) > 18) {
                                                $displayText = substr($textData, 0, 18) . '...';
                                ?>
                            <span><?php echo htmlspecialchars($displayText); ?></span>
                            <?php
                                            } else {
                                ?>
                            <span><?php echo htmlspecialchars($textData); ?></span>
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
                            <a href="<?php echo $downloadLink; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php else: ?>
                            <span class="btn btn-secondary btn-sm disabled">
                                <i class="fas fa-ban"></i>
                            </span>
                            <?php endif; ?>

                            <button class="btn btn-danger btn-sm "
                                onclick="deleteFile(<?php echo $row['id']; ?>, '<?php echo $row['filename'] . $original_filename; ?>')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <script>
                    <?php if ($row['expired'] == 0): ?>
                        (function() {
                            var uploadedTime = new Date("<?php echo $row['upload_datetime']; ?>").getTime();
                            var countdownDate = uploadedTime + 15 * 60 * 1000;
                            var timerId = setInterval(function() {
                                var now = new Date().getTime();
                                var distance = countdownDate - now;
                                if (distance <= 0) {
                                    clearInterval(timerId);
                                    document.getElementById("timer-<?php echo $row['id']; ?>").innerHTML =
                                        "Expired";
                                    var xhr = new XMLHttpRequest();
                                    xhr.open("POST", "mark_as_expired.php", true);
                                    xhr.setRequestHeader("Content-type",
                                        "application/x-www-form-urlencoded");
                                    xhr.send("id=<?php echo $row['id']; ?>");
                                    var actionButton = document.querySelector(
                                        `#file-<?php echo $row['id']; ?> .actions .btn-success`);
                                    if (actionButton) {
                                        actionButton.classList.add('disabled');
                                        actionButton.innerHTML = 'Expired';
                                    }
                                } else {
                                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                    document.getElementById("timer-<?php echo $row['id']; ?>").innerHTML =
                                        minutes + "m " + seconds + "s";
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
                        <td colspan="7" class="text-center ">
                            <div class="card shadow-sm border-0 mx-auto" style="max-width: 500px; color: #424a4b;">
                                <div class="card-body text-center">
                                    <h4 class="fw-bold" style="color: #424a4b;">No Files Available</h4>
                                    <p style="color: #424a4b;">
                                        You haven't uploaded any files yet. This system allows users to share files
                                        based on their IP addresses like connected on a same WiFi connection.
                                    </p>
                                    <ul class="list-unstyled text-start d-inline-block" style="color: #424a4b;">
                                        <li>📁 Upload and share files securely.</li>
                                        <li>🔍 View and download shared files.</li>
                                        <li>⚡ Fast and simple access for all users.</li>
                                    </ul>
                                    <button class="btn mt-3 px-4 py-2" style="background-color: #427bff; color: white;"
                                        data-bs-toggle="modal" data-bs-target="#uploadModal" id="myButton"
                                        onclick="buttonAction()">
                                        Upload Your First File
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
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