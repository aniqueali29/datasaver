<?php
// Include database configuration
include('../connection/db_config.php');
include('../layout/header.php');

// Pagination logic
$limit = 16; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Fetch total number of rows
$totalQuery = "SELECT COUNT(*) as total FROM `users_data` WHERE email='$email'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total'];

// Calculate total pages
$pages = ceil($total / $limit);

// Fetch data for the current page
$query = "SELECT * FROM `users_data` WHERE email='$email' ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Utility functions
function formatFileSize($size)
{
    if ($size >= 1024 * 1024) {
        return round($size / (1024 * 1024), 2) . ' MB';
    } else {
        return round($size / 1024, 2) . ' KB';
    }
}

function getFileIcon($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => '../assets/img/pdf.png',
        'rar' => '../assets/img/rar.png',
        'zip' => '../assets/img/zip.png',
        'png' => '../assets/img/png.png',
        'jpg' => '../assets/img/png.png',
        'jpeg' => '../assets/img/png.png',
        'default' => '../assets/img/xyz.png'
    ];
    return $icons[$extension] ?? $icons['default'];
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && isset($_POST['text_data'])) {
        $filename = $_FILES['file']['name'];
        $filesize = $_FILES['file']['size'];
        $text_data = mysqli_real_escape_string($conn, $_POST['text_data']);

        $uploadDir = '../uploads/';
        $filePath = $uploadDir . basename($filename);

        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            $sql = "INSERT INTO `users_data` (email, filename, filesize, text_data) VALUES ('$email', '$filename', '$filesize', '$text_data')";
            if (!mysqli_query($conn, $sql)) {
                echo "Database error: " . mysqli_error($conn);
            }
        } else {
            echo "Failed to upload file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced File Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef6fc, #d9e4f1);
            min-height: 100vh;
            margin: 0;
        }

        .file-manager {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .file-item {
            position: relative;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            cursor: pointer;
        }

        .file-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .file-item img {
            max-width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }

        .file-item h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #007bff;
        }

.context-menu {
    position: fixed; /* Change from absolute to fixed */
    z-index: 999;
    display: none;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    overflow: hidden;
    min-width: 150px;
}

.context-menu li {
    list-style: none;
    padding: 10px 20px;
    color: #333;
    cursor: pointer;
    transition: background 0.3s;
}

.context-menu li:hover {
    background: #f0f0f0;
}

    </style>
</head>

<body>
    <div class="file-manager">
        <button class="btn btn-primary btn-upload" data-bs-toggle="modal" data-bs-target="#addFileModal">Upload File</button>
        <div class="file-grid">
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <div class="file-item" 
                     data-id="<?php echo $row['id']; ?>" 
                     data-name="<?php echo htmlspecialchars($row['filename']); ?>" 
                     data-size="<?php echo $row['filesize']; ?>" 
                     data-text_data="<?php echo htmlspecialchars($row['text_data']); ?>">
                    <img src="<?php echo getFileIcon($row['filename']); ?>" alt="File Icon">
                    <h3><?php echo htmlspecialchars($row['filename']); ?></h3>
                </div>
                

            <?php endwhile; ?>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++) : ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
                    <!-- Context Menu -->
    <ul class="context-menu" id="fileContextMenu">
        <li data-action="download">Download</li>
        <li class="share" data-action="download"data-file-id="<?php echo $row['id']; ?>">Share</li>
        <li data-action="delete">Delete</li>
        <li data-action="text_data">text_data</li>
    </ul>
    
    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage">No message available.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <!--<div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFileModalLabel" aria-hidden="true">-->
    <!--    <div class="modal-dialog">-->
    <!--        <div class="modal-content">-->
    <!--            <form id="uploadForm" method="POST" enctype="multipart/form-data">-->
    <!--                <div class="modal-header">-->
    <!--                    <h5 class="modal-title">Upload File</h5>-->
    <!--                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>-->
    <!--                </div>-->
    <!--                <div class="modal-body">-->
    <!--                    <div class="mb-3">-->
    <!--                        <label for="fileInput" class="form-label">Select File</label>-->
    <!--                        <input type="file" class="form-control" id="fileInput" name="file" required>-->
    <!--                    </div>-->
    <!--                    <div class="mb-3">-->
    <!--                        <label for="messageInput" class="form-label">Message (optional)</label>-->
    <!--                        <textarea class="form-control" id="messageInput" name="text_data" rows="3"></textarea>-->
    <!--                    </div>-->
    <!--                </div>-->
    <!--                <div class="modal-footer">-->
    <!--                    <button type="submit" class="btn btn-primary">Upload</button>-->
    <!--                </div>-->
    <!--            </form>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->
    
    <!-- Modal -->
<form method="post" enctype="multipart/form-data" id="uploadForm">
    <div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Add Data</h1>
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
                        <div class="progress-bar" role="progressbar" style="width: 0%;" id="progressBar" data-content="0%"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>
</form>


<script>
$(document).ready(function () {
    const $contextMenu = $('#fileContextMenu');

    // Handle right-click (context menu) on file items
    $('.file-item').on('contextmenu', function (e) {
        e.preventDefault();

        const fileId = $(this).data('id');
        const fileName = $(this).data('name');
        const fileSize = $(this).data('size');
        const textData = $(this).data('text_data');

        // Attach file details to the context menu
        $contextMenu.data('file', { id: fileId, name: fileName, size: fileSize, text_data: textData });

        // Show or hide the text_data option based on availability
        $contextMenu.find('[data-action="text_data"]').toggle(!!textData);

        // Calculate menu position
        const menuWidth = $contextMenu.outerWidth();
        const menuHeight = $contextMenu.outerHeight();
        const windowWidth = $(window).width();
        const windowHeight = $(window).height();

        let top = e.pageY;
        let left = e.pageX;

        // Adjust position if the menu goes outside the viewport
        if (e.pageX + menuWidth > windowWidth) {
            left = windowWidth - menuWidth - 10;
        }
        if (e.pageY + menuHeight > windowHeight) {
            top = windowHeight - menuHeight - 10;
        }

        $contextMenu.css({
            top: `${top}px`,
            left: `${left}px`,
            display: 'block',
        });
    });

    // Hide context menu on click outside
    $(document).on('click', function () {
        $contextMenu.hide();
    });

    // Handle context menu actions
    $contextMenu.on('click', 'li', function () {
        const action = $(this).data('action');
        const file = $contextMenu.data('file');

        if (action === 'delete') {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the file.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('./delete_file.php', { file_id: file.id }, function (response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Deleted!', 'Your file has been deleted.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    }).fail(function () {
                        Swal.fire('Error!', 'Unable to delete the file.', 'error');
                    });
                }
            });
        } else if (action === 'text_data') {
            $('#modalMessage').text(file.text_data);
            $('#messageModal').modal('show');
        } else if (action === 'download') {
            // Trigger download
            const link = document.createElement('a');
            link.href = `../uploads/personal_files/${file.name}`;
            link.download = file.name;
            link.click();
        } else if (action === 'share') {
            const shareUrl = `${window.location.origin}./share.php?file_id=${file.id}`;
            navigator.clipboard.writeText(shareUrl).then(() => {
                Swal.fire('Shared!', 'File link copied to clipboard.', 'success');
            }).catch(() => {
                Swal.fire('Error!', 'Failed to copy file link.', 'error');
            });
        } else {
            alert('Unknown action selected.');
        }
    });
});
</script>


    <script>
    $(document).ready(function () {
    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: './upload.php', // Ensure this path points to the correct location of upload.php
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#progressBarContainer').show();
                $('#progressBar').css('width', '0%').text('0%');
            },
            xhr: function () {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progressBar').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                let res = JSON.parse(response);
                if (res.success) {
                    Swal.fire({
                        title: 'Success',
                        text: 'File uploaded successfully!',
                        icon: 'success'
                    }).then(() => {
                        location.reload(); // Reload the page to refresh the file list
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: res.message,
                        icon: 'error'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: 'Error',
                    text: 'Something went wrong during the upload process.',
                    icon: 'error'
                });
            },
            complete: function () {
                $('#progressBarContainer').hide();
                $('#progressBar').css('width', '0%').text('0%');
            }
        });
    });
});

    </script>
</body>

</html>
