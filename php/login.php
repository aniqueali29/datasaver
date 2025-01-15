<?php
include('../connection/db_config.php'); 
session_start();

$email_err = $password_err = "";
$error_message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    
    if (empty($email)) {
        $email_err = "Please enter your email.";
    }

    
    if (empty($password)) {
        $password_err = "Please enter your password.";
    }

    
    if (empty($email_err) && empty($password_err)) {
        
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
                            $error_message = "Your account has been blocked. Please contact the administrator.";
                        } else {
                            
                            if (password_verify($password, $hashed_password)) {
                                
                                session_start();
                                $_SESSION['user_id'] = $id;
                                $_SESSION['email'] = $email;

                                
                                header("location: dashboard.php");
                                exit();
                            } else {
                                
                                $error_message = "Invalid email or password.";
                            }
                        }
                    }
                } else {
                    
                    $error_message = "Invalid email or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
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
    <link href="https:
    <link rel="stylesheet" href="https:
    <style>
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            width: auto;
        }

        @import url('https:
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #4070f4;
        }
        .wrapper {
            position: relative;
            max-width: 430px;
            width: 100%;
            background: #fff;
            padding: 34px;
            border-radius: 6px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        .wrapper h2 {
            position: relative;
            font-size: 22px;
            font-weight: 600;
            color: #333;
        }
        .wrapper h2::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 28px;
            border-radius: 12px;
            background: #4070f4;
        }
        .wrapper form {
            margin-top: 30px;
        }
        .wrapper form .input-box {
            height: 52px;
            margin: 18px 0;
            position: relative;
        }
        form .input-box input {
            height: 100%;
            width: 100%;
            outline: none;
            padding: 0 15px;
            font-size: 17px;
            font-weight: 400;
            color: #333;
            border: 1.5px solid #C7BEBE;
            border-bottom-width: 2.5px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .input-box input:focus,
        .input-box input:valid {
            border-color: #4070f4;
        }
        .input-box .eye-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .input-box.button input {
            color: #fff;
            letter-spacing: 1px;
            border: none;
            background: #4070f4;
            cursor: pointer;
        }
        .input-box.button input:hover {
            background: #0e4bf1;
        }
        form .text h3 {
            color: #333;
            width: 100%;
            text-align: center;
        }
        form .text h3 a {
            color: #4070f4;
            text-decoration: none;
        }
        form .text h3 a:hover {
            text-decoration: underline;
        }
        form .input-box .forgot_pass {
            margin-top: 8px;
        }
    </style>
</head>
<body style="background-color: #4070f4;">
<div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert" style="<?php echo empty($error_message) ? 'display: none;' : ''; ?>">
    <strong>Error!</strong> <?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="wrapper">
    <h2>Login</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="input-box <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
            <input type="text" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? $email : ''; ?>" required>
        </div>
        <div class="input-box <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
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
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    togglePassword.addEventListener("click", function () {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        eyeIcon.classList.toggle("fa-eye");
        eyeIcon.classList.toggle("fa-eye-slash");
    });
</script>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>
<script src="http://malsup.github.com/jquery.form.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
