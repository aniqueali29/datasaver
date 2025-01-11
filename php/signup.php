<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/signup.css">
    <style>
        #error-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
            display: none; /* Hidden by default */
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
    // Function to toggle password visibility
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

    // Function to show alert with custom message
    function showError(message) {
        document.getElementById("alert-message").innerText = message;
        document.getElementById("error-alert").style.display = "block";
    }
</script>


</body>
</html>

<?php
session_start(); // Start session at the top

require '../vendor/autoload.php'; // Include the Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../connection/db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>showError('Passwords do not match!');</script>";
        exit;
    }

    // Check if email already exists
    $check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0) {
        echo "<script>showError('Email already exists!');</script>";
        exit;
    }

    $check_query->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate a random OTP
    $otp = rand(100000, 999999);

    // Store the OTP in the session
    $_SESSION['otp'] = $otp; // Store OTP in session
    $_SESSION['email'] = $email; // Store email in session
    $_SESSION['name'] = $name; // Store name in session
    $_SESSION['password'] = $hashed_password; // Store hashed password in session

    // Send OTP email
    if (sendOtpEmail($email, $otp)) {
        // Redirect to verify OTP page
        header("Location: verify_otp.php");
        exit;
    } else {
        echo "<script>showError('Unable to send OTP.');</script>";
    }
}

$conn->close();

// Function to send OTP email
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    // Enable detailed debug output
    $mail->SMTPDebug = 2;  // 0 = off (for production use), 2 = verbose debug output
    $mail->Debugoutput = 'error_log';  // Output debug information to the error log
    
    try {
        // Primary Server settings (support@datasaver.online)
        $mail->isSMTP();
        $mail->Host = 'mail.datasaver.online'; // SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'datasave@datasaver.online'; // SMTP username
        $mail->Password = 'Anique0datasaver@'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Set sender and recipient
        $mail->setFrom('support@datasaver.online', 'Data Saver Account Verification');
        $mail->addAddress($email);
        
        // Email content
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
                    <p style='font-size: 12px; color: #999; text-align: center; margin-top: 15px;'>Need help? Contact us at <a href='mailto:support@datasaver.online' style='color: #4CAF50;'>support@datasaver.online</a></p>
                </div>
            </div>";

        // Attempt to send the email
        if (!$mail->send()) {
            throw new Exception('Primary SMTP failed: ' . $mail->ErrorInfo);
        }

        return true; // Email sent successfully

    } catch (Exception $e) {
        // Log the error from the primary server
        error_log('Primary SMTP Error: ' . $e->getMessage());

        // Attempt to send via Gmail if the primary server fails
        try {
            // Clear addresses and reset PHPMailer
            $mail->clearAddresses();
            $mail->clearAttachments();
            
            // Gmail SMTP settings
            $mail->Host = 'smtp.gmail.com';
            $mail->Username = 'aniqueali000@gmail.com'; // Your Gmail account
            $mail->Password = 'laaxmofdlxzxxara'; // Gmail password or App Password
            $mail->setFrom('support@datasaver.online', 'Data Saver Support');
            $mail->addAddress($email);

            // Attempt to send the email via Gmail
            if (!$mail->send()) {
                throw new Exception('Gmail SMTP failed: ' . $mail->ErrorInfo);
            }

            return true; // Email sent successfully via Gmail

        } catch (Exception $e) {
            // Log the error from Gmail
            error_log('Gmail SMTP Error: ' . $e->getMessage());
            return false; // Failed to send email
        }
    }
}

?>
