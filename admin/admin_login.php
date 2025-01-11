<?php
include('db_config.php');
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $_SESSION['admin'] = $username;
        header("location: admin_portal.php");
    } else {
        echo "Invalid username or password";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: poppins;
            text-decoration: none;
        }

        .help-block {
            color: red;
            /* Customize error message color */
            font-size: 14px;
            /* Customize error message font size */
        }

        body {
            display: grid;
            align-items: center;
            justify-content: center;
            background-color: #4070f4;

        }

        .login-form {
            width: 450px;
            background-color: white;
            box-shadow: 0px 5px 10px black;
            margin-top: 50px;
            /* Added margin-top for better positioning */
        }

        .login-form h2 {
            text-align: center;
            /* background-color: #204969; */
            background-color: #41b6e6;
            padding: 12px 0px;
            color: white;
        }

        .login-form form {
            padding: 30px 60px;
        }

        .login-form form .input-field {
            display: flex;
            flex-direction: row;
            margin: 10px 0px;
        }

        .login-form form .input-field i {
            color: darkslategray;
            padding: 10px 14px;
            background-color: #f2f4f6;
            margin-right: 4px;
        }

        .login-form form .input-field input {
            background-color: #f2f4f6;
            padding: 10px;
            border: none;
            width: 100%;
            padding-left: 15px;
        }

        .login-form form button {
            width: 100%;
            background-color: #5bd1d7;
            padding: 8px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            color: white;
            margin: 15px 0;
            transition: background-color 0.4s;
            cursor: pointer;
            /* Added cursor pointer for better UX */
        }

        .login-form form button:hover {
            background-color: #41b6e6;
        }

        .login-form form .extra {
            font-size: 14px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        .login-form form .extra a:first-child {
            color: darkgrey;
        }

        .login-form form .extra a:last-child {
            color: grey;
        }
    </style>
</head>

<body>
    <div class="login-form">
        <h2>ADMIN LOGIN</h2>
        <form method="post">

            <?php if (!empty($password_err) || !empty($username_err)) : ?>
            <div class="alert alert-dark" role="alert">
                <?php echo $password_err; ?>
                <?php echo $username_err; ?>
            </div>
            <?php endif; ?>


            <div class="input-field <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <i class="bi bi-person-circle"></i>
                <input type="text" name="username" placeholder="Username" value="<?php echo $username; ?>">
            </div>
            <div class="input-field <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="password" placeholder="Password"> <br>
            </div>

            <button type="submit" value="Login">Login In</button>

        </form>
    </div>
</body>

</html>