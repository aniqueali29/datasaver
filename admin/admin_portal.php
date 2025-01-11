<?php
include('db_config.php');
include('header_admin.php');
?>
<body>
    <div class="container-fluid">
        <h2 class="text-center mt-3">Welcome, Admin</h2>
        <h3 class="text-center mt-3">All Users Data</h3>
        <div class="table-responsive mt-3">
            <table style="text-align: center;" class="table table-hover table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>
                            <div>Users Email</div>
                        </th>
                        <th>
                            <div>File Name</div>
                        </th>
                        <th>
                            <div>File Type</div>
                        </th>
                        <th>
                            <div>File Size</div>
                        </th>
                        <th>
                            <div>Upload Date & Time</div>
                        </th>
                        <th>
                            <div>Users Texts</div>
                        </th>
                        <th>
                            <div>Actions</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <td>
                        <div><b>
                                <?php echo $row['email']; ?>
                            </b></div>
                    </td>
                    <td>
                        <div>
                            <?php echo $row['filename']; ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php echo $row['filetype']; ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php echo formatFileSize($row['filesize']); ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php echo $row['upload_datetime']; ?>
                        </div>
                    </td>
                    <td>
                        <?php echo $row['text_data']; ?>
                    </td>
                    <td class="action">
                        <a href="uploads/<?php echo $row['filename']; ?>" download
                            class="btn btn-success btn-sm">Download</a>
                        <button class="btn btn-warning btn-sm update-btn" data-id="<?php echo $row['id']; ?>"
                            data-username="<?php echo $row['email']; ?>" data-filename="<?php echo $row['filename']; ?>"
                            data-filetype="<?php echo $row['filetype']; ?>"
                            data-filesize="<?php echo $row['filesize']; ?>"
                            data-text="<?php echo $row['text_data']; ?>">Update</button>
                        <a href="admin_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updateForm" method="post" action="admin_update.php" enctype="multipart/form-data">
                    <!-- <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div> -->
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Update DATA</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="update-id">
                        <div class="form-group">
                            <label for="update-username">Username</label>
                            <input type="text" class="form-control" id="update-username" name="username" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update-file">Current File</label>
                            <a href="" id="update-file-link" download></a><br>
                            <label for="update-new-file">Upload New File</label>
                            <!-- <input type="file" class="form-control" id="update-new-file" name="file"> -->
                            <input type="file" name="file" class="form-control" id="file">
                        </div>
                        <div class="form-group">
                            <label for="update-text">Text</label>
                            <textarea class="form-control" id="update-text" name="text" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            aria-label="Close">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('.update-btn').on('click', function () {
                var id = $(this).data('id');
                var username = $(this).data('username');
                var filename = $(this).data('filename');
                var text = $(this).data('text');

                $('#update-id').val(id);
                $('#update-username').val(username);
                $('#update-file-link').attr('href', 'uploads/' + filename).text(filename);
                $('#update-text').val(text);

                $('#updateModal').modal('show');
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.update-btn').on('click', function () {
                var id = $(this).data('id');
                var username = $(this).data('username');
                var filename = $(this).data('filename');
                var filetype = $(this).data('filetype');
                var filesize = $(this).data('filesize');
                var text = $(this).data('text');

                $('#update-id').val(id);
                $('#update-username').val(username);
                $('#update-file-link').attr('href', 'uploads/' + filename).text(filename);
                $('#update-text').val(text);

                $('#updateModal').modal('show');
            });

            // Handle modal closing for both close buttons
            $('#updateModal .btn-close, #updateModal [data-bs-dismiss="modal"]').on('click', function () {
                $('#updateModal').modal('hide');
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
</body>

</html>