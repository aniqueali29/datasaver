<?php
session_start();
include('../connection/db_config.php');

// Check if user is logged in or not:
if (!isset($_SESSION['email'])) {
    header("location: ./login.php");
    exit;
}

$email = $_SESSION['email'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];      
} else {
    header("location: ./login.php");
    exit;
}

$queery = "SELECT * FROM `users_data` WHERE email='$email'";
$reesult = mysqli_query($conn, $queery);
if (!$reesult) {
    die("Query Failed: " . mysqli_error($conn));
}

if ($row = mysqli_fetch_assoc($reesult)) {
    $tokeen = bin2hex(random_bytes(16));

    $_SESSION['file_tokens'][$tokeen] = $row['filename'];

    $downloadLink = "secure_personal_download.php?token=" . $tokeen;
}


// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start = ($page - 1) * $limit;

$totalQuery = "SELECT COUNT(*) as total FROM `users_data` WHERE email='$email'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$total = (int) $totalRow['total'];

$total_pages = ($total > 0) ? ceil($total / $limit) : 1;

$query = "SELECT * FROM `users_data` WHERE email='$email' ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
    <!-- nav start -->

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

    <div class="container mt-5">
        <div class="box1">
            <button class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#exampleModal">ADD DATA</button>
        </div>

        <div class="table-container mb-3">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>File Size</th>
                        <th>Date & Time</th>
                        <th>Text Data</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="fileTableBody">
                    <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                    <tr>
                        <td>
                            <?php
                        $filename = $row['filename'];
                        $displayName = (strlen($filename) > 12) ? substr($filename, 0, 12) . '...' : $filename;
                        ?>
                            <span title="<?php echo htmlspecialchars($filename); ?>">
                                <?php echo htmlspecialchars($displayName); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo formatFileSize($row['filesize']); ?>
                        </td>
                        <td>
                            <?php echo $row['upload_datetime']; ?>
                        </td>
                        <td>
                            <?php
                        $textData = $row['text_data'];
                        if (!empty($textData)) {
                            if (strlen($textData) > 15) {
                                $displayText = substr($textData, 0, 15) . '...';
                        ?>
                            <span class="message-tooltip" data-fulltext="<?php echo htmlspecialchars($textData); ?>"
                                data-bs-toggle="modal" data-bs-target="#messageModal">
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
                        } else {
                        ?>
                            <span class="badge bg-danger">No Message Posted!</span>
                            <?php
                        }
                        ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm share" data-file-id="<?php echo $row['id']; ?>">
                                <i class="fas fa-share-alt"></i>
                            </button>
                            <a href="<?php echo $downloadLink; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                    }
                } else {
                ?>
                    <tr>
                    <tr>
                        <td colspan="7" class="text-center ">
                            <div class="card shadow-sm border-0 mx-auto" style="max-width: 500px; color: #424a4b;">
                                <div class="card-body text-center">
                                    <h4 class="fw-bold" style="color: #424a4b;">No Files Available</h4>
                                    <p style="color: #424a4b;">
                                        You haven't uploaded any files yet. This system allows users to store data,
                                        share files using link.
                                    </p>
                                    <ul class="list-unstyled text-start d-inline-block" style="color: #424a4b;">
                                        <li>📁 Upload and share files securely.</li>
                                        <li>🔍 View and download shored files.</li>
                                        <li>⚡ Fast and simple access for all users.</li>
                                    </ul>
                                    <button class="btn mt-3 px-4 py-2" style="background-color: #427bff; color: white;"
                                        data-bs-toggle="modal" data-bs-target="#exampleModal">
                                        Upload Your First File
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>



<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">
    <ul class="pagination pagination-lg">
        <!-- Previous Button -->
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo max(1, $page - 1); ?>" 
                aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
            <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>

        <!-- Next Button -->
        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo min($total_pages, $page + 1); ?>" 
                aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

    </div>

    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Full Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalMessageContent">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <form method="post" enctype="multipart/form-data" id="uploadForm">
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">ADD DATA</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="file">Select File</label>
                            <input type="file" name="file" class="form-control" id="file" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="text">Text Data</label>
                            <input type="text" name="text" class="form-control" id="text">
                        </div>
                        <!-- Progress Bar -->
                        <div class="progress" id="progressBarContainer" style="display: none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" id="progressBar"
                                data-content="0%"></div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="uploadButton">Upload</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://malsup.github.io/jquery.form.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const shareButtons = document.querySelectorAll('.share');

        shareButtons.forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id'); // Get the file ID

                Swal.fire({
                    title: 'Generating Link...',
                    text: 'Please wait while the shareable link is generated.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('generate_link.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'file_id=' + fileId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Link Generated!',
                                text: 'Shareable link has been copied to clipboard.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });

                            navigator.clipboard.writeText(data.url);
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to generate the shareable link.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            });
        });
    });
    </script>

    <?php
function formatFileSize($size)
{
    if ($size >= 1024 * 1024) {
        return round($size / (1024 * 1024), 2) . ' MB';
    } else {
        return round($size / 1024, 2) . ' KB';
    }
}
?>

    <?php include('../layout/footer.php'); ?>


    <script src="../js/personal_files.js"></script>


    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>
    <script src="http://malsup.github.com/jquery.form.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>