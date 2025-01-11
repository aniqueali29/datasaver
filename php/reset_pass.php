<?php
session_start();
require '../vendor/autoload.php'; // Include the Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include('../connection/db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $email = $_POST['email'];

    // Check if email exists
    $check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0) {
        // Generate a random OTP
        $otp = rand(100000, 999999);

        // Store the OTP and email in session
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;

        // Send OTP email
        if (sendOtpEmail($email, $otp)) {
            // Redirect with success message
            $_SESSION['info'] = 'An OTP has been sent to your email for password reset.';
            header("Location: verify_reset_otp.php");
            exit;
        } else {
            $_SESSION['info'] = 'Unable to send OTP. Please try again.';
            header("Location: reset_pass.php");
            exit;
        }
    } else {
        $_SESSION['info'] = 'Email not found in our system.';
        header("Location: reset_pass.php");
        exit;
    }

    $check_query->close();
}

$conn->close();

// Function to send OTP email
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP settings for support@datasaver.online
        $mail->isSMTP();
        $mail->Host = 'mail.datasaver.online'; // SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'datasave@datasaver.online'; // SMTP username
        $mail->Password = 'Anique0datasaver@'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('datasave@datasaver.online', 'Data Saver Support');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - OTP Code';
        
        // HTML content for a beautiful but lightweight email
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='text-align: center; color: #4CAF50;'>Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password. Use the OTP code below to proceed:</p>
            <div style='background: #f9f9f9; padding: 15px; text-align: center; border-radius: 5px; font-size: 18px; font-weight: bold; color: #333;'>
                $otp
            </div>
            <p style='margin-top: 20px;'>This OTP is valid for 10 minutes. If you did not request a password reset, please ignore this email.</p>
            <p>Thank you,<br>Data Saver Team</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 12px; color: #999; text-align: center;'>If you have any questions, contact us at <a href='mailto:datasave@datasaver.online' style='color: #4CAF50;'>datasave@datasaver.online</a>.</p>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Fallback to Gmail settings
        try {
            $mail->clearAddresses();
            $mail->Host = 'smtp.gmail.com';
            $mail->Username = 'aniqueali000@gmail.com';
            $mail->Password = 'laaxmofdlxzxxara';
            $mail->setFrom('datasave@datasaver.online', 'Data Saver Support');
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/signup.css">
    <title>Reset Password</title>
</head>
<body>
    <?php
    if (isset($_SESSION['info'])) {
        echo '<script>
            Swal.fire({
                icon: "info",
                title: "Notice",
                text: "' . htmlspecialchars($_SESSION['info']) . '",
                timer: 3000
            });
        </script>';
        unset($_SESSION['info']);
    }
    ?>
    
    <div class="wrapper">
        <h2 class="mb-4">Reset Password</h2>
        <div class="alert alert-secondary" role="alert">
            Please enter your email to change your password.
        </div>
        <form method="post" action="">
            <div class="input-box">
                <input type="email" name="email" placeholder="Enter your registered email" required>
            </div>
            <div class="input-box button">
                <input type="submit" name="reset" value="Send OTP">
            </div>
        </form>
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
