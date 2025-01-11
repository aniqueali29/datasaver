<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous"> -->
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .action a {
            margin: 3px;
        }

        .action button {
            margin: 3px;
        }

        .thead-dark th,
        td {
            vertical-align: middle;
            text-align: center;
            height: 50px;
            display: table-cell;
        }

        .thead-dark th div,
        td div {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
    </style>

</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <h2 style="color: aliceblue;">DashBoard</h2>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                 <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin_portal.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">All USERS</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link active" href="#">Disabled</a>
                    </li> -->
                </ul> 
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php
                    session_start();

                    if (!isset($_SESSION['admin'])) {
                        header("location: admin_login.php");
                        exit;
                    }
                    
                    // Fetch all user data
                    $sql = "SELECT * FROM users_data";
                    $result = $conn->query($sql);

                    // Check if the user is logged in
                    if (isset($_SESSION['username'])) {
                        // User is logged in, show the Logout button

                        echo '<li class="nav-but"><a href="signup.php" class="btn btn-outline-light">Sign Up</a></li>';
                        echo '<li class="nav-but"><a href="login.php" class="btn btn-outline-light">Login</a></li>';


                    } else {
                        // User is not logged in, show the Signup & Login buttons
                        echo '<li class="nav-but"><a href="logout.php" class="btn btn-outline-light">Logout</a></li>';

                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Your page content goes here -->