<?php
include('db_config.php');
include('header_admin.php');

// Fetch all users from the database
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<body>
    <div class="container-fluid">
        <h2 class="text-center mt-3">Admin Panel - All Users</h2>
        <div class="table-responsive mt-3">
            <table class="table table-hover table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status = $row['blocked'] ? 'Blocked' : 'Active';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $status; ?></td>
                            <td>
                                <?php if ($row['blocked']): ?>
                                    <a href="unblock_user.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Unblock</a>
                                <?php else: ?>
                                    <a href="block_user.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Block</a>
                                <?php endif; ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal<?php echo $row['id']; ?>">Change Password</button>
                            </td>
                        </tr>

                        <!-- Change Password Modal -->
                        <div class="modal fade" id="changePasswordModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="changePasswordModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="change_password.php">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="changePasswordModalLabel<?php echo $row['id']; ?>">Change Password</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <div class="form-group">
                                                <label for="new_password">New Password</label>
                                                <input type="password" class="form-control" id="new_password<?php echo $row['id']; ?>" name="new_password" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
