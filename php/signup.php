<?php
session_start();

require '../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../connection/db_config.php');
include('../connection/smtp_config.php'); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match!';
        header("Location: signup.php");
        exit;
    }

    $check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0) {
        $_SESSION['error_message'] = 'Email already exists!';
        header("Location: signup.php");
        exit;
    }

    $check_query->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $otp = rand(100000, 999999);

    $_SESSION['otp'] = $otp;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;
    $_SESSION['password'] = $hashed_password;

    // Send OTP email
    if (sendOtpEmail($email, $otp)) {
        header("Location: verify_otp.php");
        exit;
    } else {
        $_SESSION['error_message'] = 'Unable to send OTP. Please try again later.';
        header("Location: signup.php");
    }
}

$conn->close();

function sendOtpEmail($email, $otp) {
    try {
        $mail = getMailerInstance();

        // Send OTP email
        $mail->setFrom('datasaver@datasaver.online', 'Data Saver Account Verification');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code for Data Saver Account Verification';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; background-color: #f7f7f7; padding: 20px;'>
                <div style='max-width: 600px; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.1); margin: auto;'>
                    <h2 style='color: #4CAF50; text-align: center;'>Welcome to Data Saver!</h2>
                    <p style='font-size: 15px; color: #555;'>Hello,</p>
                    <p style='font-size: 15px; color: #555;'>To complete your registration, please use the following One-Time Password (OTP):</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <span style='font-size: 22px; font-weight: bold; color: #4CAF50; padding: 10px 20px; background-color: #f1f1f1; border-radius: 5px;'>$otp</span>
                    </div>
                    <p style='font-size: 15px; color: #555;'>This OTP is valid for 10 minutes. For security reasons, please do not share this code with anyone.</p>
                    <p style='font-size: 15px; color: #555;'>If you did not request this code, please ignore this email or contact our support team.</p>
                    <p style='font-size: 15px; color: #555;'>Regards,<br>The Data Saver Team</p>
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='https://datasaver.online' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;'>Visit Data Saver</a>
                    </div>
                    <p style='font-size: 12px; color: #999; text-align: center; margin-top: 15px;'>Need help? Contact us at <a href='mailto:datasaver@datasaver.online' style='color: #4CAF50;'>datasaver@datasaver.online</a></p>
                </div>
            </div>";

        if (!$mail->send()) {
            throw new Exception('Primary SMTP failed: ' . $mail->ErrorInfo);
        }

        return true;
    } catch (Exception $e) {
        error_log('SMTP Error: ' . $e->getMessage());
        return false; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/signup.css">
    <style>
        #error-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
            display: none; 
        }
    </style>
</head>

<body>
<div class="wrapper">
    <h2>Registration</h2>
    <form method="post" action="">
        <div class="input-box">
            <input type="text" name="name" placeholder="Enter your name" required>
        </div>
        <div class="input-box">
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="input-box">
            <input type="password" id="password" name="password" placeholder="Create password" required>
        </div>
        <div class="input-box">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
        </div>
        <div>
            <input type="checkbox" class="show-passwords" id="show-passwords" onclick="togglePasswordVisibility()">
                <label for="show-passwords">Show Passwords</label>
        </div>
        <div class="input-box button">
            <input type="submit" name="register" value="Register Now">
        </div>
        <div class="text">
            <h3>Already have an account? <a href="login.php">Login now</a></h3>
        </div>
    </form>
</div>

<!-- Bootstrap Alert -->
<div id="error-alert" class="alert alert-warning alert-dismissible fade show" role="alert">
  <strong>Error!</strong> <span id="alert-message">You should check in on some of those fields below.</span>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
    function togglePasswordVisibility() {
        var password = document.getElementById("password");
        var confirmPassword = document.getElementById("confirm_password");
        if (password.type === "password" && confirmPassword.type === "password") {
            password.type = "text";
            confirmPassword.type = "text";
        } else {
            password.type = "password";
            confirmPassword.type = "password";
        }
    }

    function showError(message) {
        document.getElementById("alert-message").innerText = message;
        document.getElementById("error-alert").style.display = "block";
    }
</script>


</body>
</html>