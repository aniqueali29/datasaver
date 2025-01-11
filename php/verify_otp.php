<?php
session_start(); // Start session

require '../vendor/autoload.php'; // Include the Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../connection/db_config.php');

// Initialize user_email from session
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    // Check if the OTP and email are set in the session
    if (isset($_SESSION['otp']) && isset($_SESSION['email'])) {
        $otp = $_SESSION['otp'];
        $email = $_SESSION['email'];

        // Debug: Output stored OTP and entered OTP
        error_log("Stored OTP: $otp"); // Log the stored OTP
        error_log("Entered OTP: $entered_otp"); // Log the entered OTP

        // Validate the entered OTP
        if ($entered_otp == $otp) {
            // Retrieve user information from the session
            $name = $_SESSION['name'] ?? null;
            $password = $_SESSION['password'] ?? null;

            // Check if name and password are set
            if ($name !== null && $password !== null) {
                // Check if the email already exists in the database
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Email already exists, set error message
                    $_SESSION['error_message'] = 'This email address is already registered. Please use a different email or login.';
                } else {
                    // Hash the password before storing it in the database for security
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Insert the user into the database
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $name, $email, $hashed_password);

                    if ($stmt->execute()) {
                        // Send a welcome email
                        try {
                            $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'mail.datasaver.online'; // SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'datasave@datasaver.online'; // SMTP username
        $mail->Password = 'Anique0datasaver@'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

                            // Set the email content
                            $mail->setFrom('datasave@datasaver.online', 'DataSaver Support');
                            $mail->addAddress($email, $name);
                            $mail->isHTML(true);
                            $mail->Subject = 'Welcome to Our Platform';
                            $mail->Body = "
                                    <div style='font-family: Arial, sans-serif; color: #333; background-color: #f7f7f7; padding: 20px;'>
                                        <div style='max-width: 600px; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.1); margin: auto;'>
                                        <h2 style='color: #4CAF50; text-align: center;'>Welcome to Data Saver!</h2>
                                         <p style='font-size: 15px; color: #555;'>Thank you for registering with us. We're excited to have you on board.</p>
                                        <p style='font-size: 15px; color: #555;'>You can now log in and explore our services.</p>
                                        <p style='font-size: 15px; color: #555;'>If you have any questions, feel free to reply to this email or reach out to our support team.</p>
                                        <p style='font-size: 15px; color: #555;'>Best regards,<br>The Data Saver Team</p>
                                        <div style='text-align: center; margin-top: 20px;'>
                                            <a href='https://datasaver.online' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;'>Visit Data Saver</a>
                                            </div>
                                        <p style='font-size: 12px; color: #999; text-align: center; margin-top: 15px;'>Need help? Contact us at <a href='mailto:datasave@datasaver.online' style='color: #4CAF50;'>datasave@datasaver.online</a></p>
                                            </div>
                                    </div>";
                            
                            $mail->send();
                            $_SESSION['success_message'] = 'Registration successful! A welcome email has been sent to your email address.';
                        } catch (Exception $e) {
                            $_SESSION['error_message'] = 'Registration successful, but email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                        }
                    } else {
                        $_SESSION['error_message'] = 'Registration failed: ' . $stmt->error;
                    }
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'User details are missing.';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid OTP.';
        }
    } else {
        $_SESSION['error_message'] = 'OTP session data not found.';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../css/signup.css">
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php
        // Display any error message
        if (isset($_SESSION['error_message'])) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '" . $_SESSION['error_message'] . "',
                    showConfirmButton: true
                });
            </script>";
            // Unset the error message after displaying
            unset($_SESSION['error_message']);
        }

        // Display success message if set
        if (isset($_SESSION['success_message'])) {
            echo "<script>
                Swal.fire({
                            icon: 'success',
                            title: 'Registration successful!',
                            text: 'You have successfully registered.',
                            showConfirmButton: true
                        }).then(() => {
                            window.location.href = 'login.php'; // Redirect to login page
                        });
            </script>";
            // Unset the success message after displaying
            unset($_SESSION['success_message']);
        }
    ?>
    <div class="wrapper">
        <h2>Verify OTP</h2>
        
        <div class="alert alert-secondary" role="alert">
            <?php if ($user_email): ?>
                Please check your email: <b><i><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></i></b>. We have sent a verification code.
            <?php else: ?>
                <b>Email not found.</b> Please return to the registration page and try again.
            <?php endif; ?>
        </div>

        <form method="post" action="">
            <div class="input-box">
                <input type="text" name="otp" placeholder="Enter your OTP" required>
            </div>
            <div class="input-box button">
                <input type="submit" name="verify" value="Verify OTP">
            </div>
        </form>
    </div>
</body>
</html>
