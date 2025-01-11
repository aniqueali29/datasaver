<?php
session_start(); 

require '../vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    
    if (isset($_SESSION['otp']) && isset($_SESSION['email'])) {
        $otp = $_SESSION['otp'];
        $email = $_SESSION['email'];

        
        if ($entered_otp == $otp) {
            
            $name = $_SESSION['name'] ?? null; 
            $password = $_SESSION['password'] ?? null; 

            
            if ($name && $password) {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    echo "Registration successful!";
                    
                    session_unset();
                    session_destroy();
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Error: User details are missing.";
            }
        } else {
            echo "Error: Invalid OTP.";
        }
    } else {
        echo "Error: OTP session data not found.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../css/signup.css">
</head>
<body>
    <div class="wrapper">
        <h2>Verify OTP</h2>
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
