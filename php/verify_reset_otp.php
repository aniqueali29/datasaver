<?php
session_start();
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    // Redirect to the start of the process if no OTP request was made
    header("Location: reset_pass.php");
    exit;
}

$user_email = $_SESSION['reset_email'];

// OTP verification logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['reset_otp']) {
        // OTP is correct; allow access to the new password page
        $_SESSION['otp_verified'] = true;
        header("Location: new_password.php");
        exit;
    } else {
        $_SESSION['info'] = "Invalid OTP. Please try again.";
        header("Location: verify_reset_otp.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../css/signup.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .alert-position {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050; /* Ensure it stays on top */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 class="mb-4">Verify OTP</h2>
        
        <?php
        // Display the informational message if it's set
        if (isset($_SESSION['info'])) {
            echo '<div class="alert alert-warning alert-dismissible fade show alert-position" role="alert">';
            echo '<strong>Notice!</strong> ' . htmlspecialchars($_SESSION['info']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            // Clear the info message after displaying it once
            unset($_SESSION['info']);
        }
        ?>

        <div class="alert alert-secondary" role="alert">
            Please check your email: <b><i><?php echo htmlspecialchars($user_email); ?></i></b>. We have sent a verification code.
        </div>
        
        <form method="post" action="">
            <div class="input-box">
                <input type="number" name="otp" placeholder="Enter OTP" required>
            </div>
            <div class="input-box button">
                <input type="submit" name="verify" value="Verify OTP">
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
