<?php
include('../connection/db_config.php');
include('../layout/header.php');

$limit = 10; 
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1; // Get current page number from URL, default is 1
$start = ($page > 1) ? ($page * $limit) - $limit : 0; // Calculate starting row

$totalQuery = "SELECT COUNT(*) as total FROM `users_data` WHERE email='$email'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total'];

$pages = ceil($total / $limit);

$query = "SELECT * FROM `users_data` WHERE email='$email' ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/dashboard.css">
    <title>Personal Files</title>
</head>

<div class="container mt-5">
    <div class="box1">
        <button class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#exampleModal">ADD DATA</button>
    </div>

    <div class="table-container mb-3">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>File Name</th>
                    <!--<th>File Type</th>-->
                    <th>File Size</th>
                    <th>Date & Time</th>
                    <th>Text Data</th>
                    <th>Download</th>
                    <th>Share</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody id="fileTableBody">
                <?php
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
<td>
    <?php
    $filename = $row['filename'];
    // Check if the filename length is greater than 12 characters
    $displayName = (strlen($filename) > 12) ? substr($filename, 0, 12) . '...' : $filename;
    ?>
    <!-- Display the truncated name, but show the full name on hover using the title attribute -->
    <span title="<?php echo htmlspecialchars($filename); ?>">
        <?php echo htmlspecialchars($displayName); ?>
    </span>
</td>
                        <!--<td><?php echo $row['filetype']; ?></td>-->
                        <td><?php echo formatFileSize($row['filesize']); ?></td>
                        <td><?php echo $row['upload_datetime']; ?></td>
<td>
    <?php
    $textData = $row['text_data'];
    if (!empty($textData)) {
        if (strlen($textData) > 15) {
            $displayText = substr($textData, 0, 15) . '...';
            ?>
            <span class="message-tooltip" 
                  data-fulltext="<?php echo htmlspecialchars($textData); ?>" 
                  data-bs-toggle="modal" 
                  data-bs-target="#messageModal">
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
        <span class= "badge bg-danger">No Message Posted!</span>
    <?php
    }
    ?>

<td>
    <button class="btn btn-info btn-sm share-btn" data-file-id="<?php echo $row['id']; ?>">
        Share
    </button>
</td>



</td>
                        <td><a href="../uploads/personal_files<?php echo $row['filename']; ?>" download
                                class="btn btn-success btn-sm">Download</a></td>
                        <td><button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button></td>

                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination links -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true" >
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

document.addEventListener('DOMContentLoaded', function () {
    const shareButtons = document.querySelectorAll('.share-btn');

    shareButtons.forEach(button => {
        button.addEventListener('click', function () {
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
