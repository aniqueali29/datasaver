<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Download</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php
// Adjusted file storage path
$fileStorage = __DIR__ . '/../uploads/';

if (isset($_GET['file'])) {
    $fileName = basename($_GET['file']); // Sanitize input
    $filePath = $fileStorage . $fileName;

    if (file_exists($filePath)) {
        // Serve the file for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        // File not found
        echo '
        <script>
            Swal.fire({
                title: "Error",
                text: "File not found.",
                icon: "error",
                confirmButtonText: "OK"
            }).then(function() {
                window.history.back();
            });
        </script>
        ';
    }
} else {
    // Invalid request
    echo '
    <script>
        Swal.fire({
            title: "Error",
            text: "Invalid request.",
            icon: "error",
            confirmButtonText: "OK"
        }).then(function() {
            window.history.back();
        });
    </script>
    ';
}
?>

</body>
</html>

<?php
ob_end_flush();
?>
