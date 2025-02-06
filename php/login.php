<?php
include('../connection/db_config.php');
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate email
    if (empty($email)) {
        $error_message .= "Please enter your email.<br>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message .= "Please enter a valid email address.<br>";
    }

    // Validate password
    if (empty($password)) {
        $error_message .= "Please enter your password.<br>";
    }

    // Proceed only if no validation errors
    if (empty($error_message)) {
        $sql = "SELECT id, password, blocked FROM users WHERE email=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email_param);
            $email_param = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $hashed_password, $blocked);

                    if ($stmt->fetch()) {
                        if ($blocked == 1) {
                            $error_message .= "Your account has been blocked. Please contact the administrator.<br>";
                        } else {
                            if (password_verify($password, $hashed_password)) {
                                session_start();
                                $_SESSION['user_id'] = $id;
                                $_SESSION['email'] = $email;

                                header("location: dashboard.php");
                                exit();
                            } else {
                                $error_message .= "The password you entered is incorrect.<br>";
                            }
                        }
                    } else {
                        $error_message .= "Failed to retrieve user information. Please try again.<br>";
                    }
                } else {
                    $error_message .= "No account found with that email address.<br>";
                }
            } else {
                $error_message .= "Database error: Unable to execute the query.<br>";
            }

            $stmt->close();
        } else {
            $error_message .= "Database error: Unable to prepare the statement.<br>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/signup.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <style>
        /* Custom CSS */
        .alert {
            position: fixed;
            display: flex;
            top: 20px;
            right: 20px;
            z-index: 1050;
            width: auto;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        .fade-out {
            animation: fadeOut 2s forwards;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            20% {
                transform: translateX(-13px);
            }
            40% {
                transform: translateX(13px);
            }
            60% {
                transform: translateX(-13px);
            }
            80% {
                transform: translateX(13px);
            }
        }

        .fade-in {
            animation: fadeIn 2s forwards, shake 0.3s ease-in-out; /* Reduced duration of shake animation */
        }

        .fade-out {
            animation: fadeOut 2s forwards;
        }
    </style>
</head>
<body>

        
<div class="alert alert-danger alert-dismissible fade-in" role="alert" id="errorAlert" style="<?php echo empty($error_message) ? 'display: none;' : ''; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Warning:" style="width: 24px; height: 24px;">
        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
    </svg>
    <div>
        <strong>Error! </strong> <?php echo $error_message; ?>
    </div>
</div>

<div class="wrapper">
    <h2>Login</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="input-box">
            <input type="text" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? $email : ''; ?>">
        </div>
        <div class="input-box">
            <input type="password" name="password" id="password" placeholder="Enter your password" >
            <span class="eye-icon" id="togglePassword">
                <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </span>
            <div class="forgot_pass"><a style="text-decoration: none;" href="reset_pass.php">Forgot Password?</a></div>
        </div>
        <br>
        <div class="input-box button">
            <input type="submit" value="Login">
        </div>
        <div class="text">
            <h3>Not a member? <a href="signup.php">Signup now</a></h3>
        </div>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const errorAlert = document.getElementById('errorAlert');

        if (errorAlert && errorAlert.style.display !== 'none') {
            setTimeout(() => {
                errorAlert.classList.add('fade-out');
            }, 3000);

            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 5000); 
        }
    });
</script>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>
<script src="http://malsup.github.com/jquery.form.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

</body>
</html>
