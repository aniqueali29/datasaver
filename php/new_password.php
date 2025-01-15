<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/signup.css">
    <title>Set New Password</title>
    <style>
        .input-box {
            position: relative;
            margin-bottom: 1rem;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 class="mb-4">Set New Password</h2>
        <div class="alert alert-secondary" role="alert">
            Please enter your new Password.
        </div>
        <form method="post" action="">
            <div class="input-box">
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
                <i class="bi bi-eye-slash toggle-password" id="toggleNewPassword"></i>
            </div>
            <div class="input-box">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
                <i class="bi bi-eye-slash toggle-password" id="toggleConfirmPassword"></i>
            </div>
            <div class="input-box button">
                <input type="submit" name="reset_password" value="Reset Password" class="btn btn-primary">
            </div>
        </form>
    </div>

    <script>
        document.getElementById('toggleNewPassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('new_password');
            const icon = this;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        });
    </script>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>

<?php
session_start();
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../connection/db_config.php');
include('../connection/smtp_config.php');

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: verify_reset_otp.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Passwords do not match.',
                timer: 2000
            });
        </script>";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $update_query = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_query->bind_param("ss", $hashed_password, $email);

        if ($update_query->execute()) {
            try {
                $mail = getMailerInstance();
                $mail->addAddress($email); 
                $mail->isHTML(true);
                $mail->Subject = 'Password Changed Successfully';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                        <h2 style='color: #4CAF50;'>Your Password Has Been Changed Successfully</h2>
                        <p>Dear user,</p>
                        <p>We wanted to let you know that your password was changed successfully. If you did not make this change, please contact us immediately.</p>
                        <p style='color: #555;'>If you did change your password, you can safely ignore this message. For your reference, here's a summary of the change:</p>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li><strong>Email:</strong> $email</li>
                            <li><strong>Change Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                        </ul>
                        <p>Thank you for using our service!</p>
                        <p>Best regards,<br>The DataSaver Team</p>
                        <p style='font-size: 12px; color: #888;'>If you did not request this change, please contact our support team immediately.</p>
                    </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Password changed successfully, but we could not send the email. Mailer Error: {$mail->ErrorInfo}',
                        timer: 3000
                    });
                </script>";
            }
            session_unset();
            session_destroy();

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Your password has been updated successfully.',
                    timer: 2000
                }).then(() => {
                    window.location.href = 'login.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update password. Please try again.',
                    timer: 2000
                });
            </script>";
        }

        $update_query->close();
    }
}

$conn->close();
?>
