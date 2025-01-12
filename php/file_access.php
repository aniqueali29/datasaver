<?php
session_start();
require_once 'db_connection.php';

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id === 0) {
    die("You must be logged in to access this page.");
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT file_path, user_id FROM file_downloads WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();

        if ($file['user_id'] != $user_id) {
            die("Unauthorized access.");
        }

        $file_path = $file['file_path'];

        if (file_exists($file_path)) {
            // Serve the file for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid or expired link.";
    }
    exit;
}

if (isset($_POST['share'])) {
    $file_path = $_POST['file_path']; // File the user wants to share
    if (!file_exists($file_path)) {
        die("File does not exist.");
    }

    // Generate a token
    $token = generateToken();

    $stmt = $conn->prepare("INSERT INTO file_downloads (user_id, file_path, token) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $file_path, $token);
    $stmt->execute();

    // Generate shareable link
    $share_link = "http://yourdomain.com/file_access.php?token=" . $token;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Download</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3>Secure File Download</h3>
    <hr>
    <form method="POST">
        <div class="form-group">
            <label for="file_path">Enter File Path:</label>
            <input type="text" class="form-control" name="file_path" required placeholder="e.g., /uploads/myfile.pdf">
        </div>
        <button type="submit" name="share" class="btn btn-primary">Generate Share Link</button>
    </form>

    <?php if (isset($share_link)): ?>
        <div class="mt-3">
            <strong>Shareable Link:</strong>
            <a href="<?php echo htmlspecialchars($share_link); ?>" target="_blank"><?php echo htmlspecialchars($share_link); ?></a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
