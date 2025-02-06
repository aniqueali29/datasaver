<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/nav.css">
</head>
<body>
 <nav class="navbar navbar-expand-lg navbar-dark ">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>
                    <figure class="logo d-flex align-items-center gap-3"><img src="../assets/img/logo.png" alt="">
                    </figure>
                </h1>
            </a>
            <button class="navbar-toggler" style="filter: invert(1);" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link" aria-current="page" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="./ip_file.php">IP Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="./dashboard.php">Personal Files</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo '<li class="nav-but"><a href="#" class="dropdown-item">Profile</a></li>';
                                echo '<li class="nav-but"><a href="./logout.php" class="dropdown-item">Logout</a></li>';
                            } else {
                                echo '<li class="nav-but"><a href="./signup.php" class="dropdown-item">Sign Up</a></li>';
                                echo '<li class="nav-but"><a href="./login.php" class="dropdown-item">Login</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
