<?php
include 'db_config.php';
include 'header_admin.php';

// Handle message deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $delete_query->bind_param("i", $delete_id);
    $delete_query->execute();
    $delete_query->close();
    header('Location: admin_panel.php');
    exit;
}

// Handle message posting by admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $message = $_POST['message'];
    $is_admin = 1; // Admin message

    if (!empty($message)) {
        $created_at = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $message, $created_at, $is_admin);

        if ($stmt->execute()) {
            header("Location: admin_panel.php");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Message cannot be empty!";
    }
}

// Fetch all messages
$messages_query = "SELECT messages.id, users.name, messages.message, messages.created_at, messages.is_admin
                   FROM messages
                   JOIN users ON messages.user_id = users.id
                   ORDER BY messages.created_at DESC";
$messages_result = $conn->query($messages_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Add your admin panel styles here */
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>Admin Panel - Manage Messages</h1>

        <!-- Admin Message Form -->
        <form action="" method="POST" class="mb-3">
            <div class="mb-3">
                <textarea name="message" class="form-control" placeholder="Enter your message here" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post Admin Message</button>
        </form>

        <!-- Display Messages -->
        <div class="row">
            <?php while ($row = $messages_result->fetch_assoc()): ?>
                <div class="col-md-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <?php echo htmlspecialchars($row['name']); ?> 
                            <?php if ($row['is_admin']): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php endif; ?>
                            <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm float-end">Delete</a>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        </div>
                        <div class="card-footer text-muted">
                            Posted on <?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
