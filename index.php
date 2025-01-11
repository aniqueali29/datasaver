<?php
ob_start(); // Start output buffering
include './connection/db_config.php'; // Include your database connection file
session_start();

// Function to get the real IP address
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}

// Retrieve the IP address
$ip_address = getUserIP();
if (empty($ip_address)) {
    die("Error: IP address is not set.");
}

error_log("Retrieved IP Address: " . $ip_address);

// Initialize message count
$message_count = 0;

// Check if the user is logged in
if (isset($_SESSION['email'])) {
    // User is logged in
    $email = $_SESSION['email'];

    // Fetch user's data
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name']; // Assuming the user's name is stored in the 'name' column
} else {
    // Redirect to login.php if user is not found
    header("Location: ../php/login.php");
    exit;
}

    // Set message limit to unlimited for logged-in users
    $message_limit = PHP_INT_MAX;
} else {
    // User is not logged in
    $name = "Join Us";
    $message_limit = 15; // Set daily message limit

    // Get the current date
    $current_date = date('Y-m-d');

    // Count the number of messages posted by the user's IP address today
    $query = "SELECT COUNT(*) as message_count FROM ip_messages WHERE ip_address = ? AND DATE(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $ip_address, $current_date);
    $stmt->execute();
    $stmt->bind_result($message_count);
    $stmt->fetch();
    $stmt->close();
}

date_default_timezone_set('Asia/Karachi'); // Set your timezone

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    if (!empty($message)) {
        if ($message_count < $message_limit || isset($_SESSION['user_id'])) {
            $created_at = date("Y-m-d H:i:s");
            $delete_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

            $stmt = $conn->prepare("INSERT INTO ip_messages (ip_address, message, created_at, delete_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $ip_address, $message, $created_at, $delete_at);

            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Limit Reached!',
                        text: 'You have reached the daily message limit. Please log in to post more messages.',
                        icon: 'warning',
                        confirmButtonText: 'Login',
                        showCancelButton: true,
                        cancelButtonText: 'Signup',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-secondary'
                        },
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = './php/login.php';
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            window.location.href = './php/signup.php';
                        }
                    });
                });
            </script>";
        }
    } else {
        echo "Message cannot be empty!";
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = $_POST['delete_message_id'];

    // Verify if the message belongs to the current user/IP
    $query = "DELETE FROM ip_messages WHERE id = ? AND ip_address = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $delete_id, $ip_address);

    if ($stmt->execute()) {
        echo "<script>alert('Message deleted successfully!');</script>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Failed to delete the message. Please try again.');</script>";
    }

    $stmt->close();
}


// Set limit and current page
$limit = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of records
$query = "SELECT COUNT(*) AS total_records FROM ip_messages"; // Replace 'products' with your table name
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_records = $row['total_records'];

// Calculate total pages
$total_pages = ceil($total_records / $limit);

// Fetch data for the current page
$query = "SELECT * FROM ip_messages LIMIT $limit OFFSET $offset"; // Fetch the data for the current page
$products = mysqli_query($conn, $query);

// Delete expired messages
// $conn->query("DELETE FROM ip_messages WHERE delete_at < NOW()");

// Fetch paginated messages for the user's IP address
$query = "SELECT id, ip_address, message, created_at, delete_at FROM ip_messages WHERE ip_address = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$result = $stmt->get_result();

// Get total number of messages for pagination
$total_query = "SELECT COUNT(*) as total FROM ip_messages WHERE ip_address = ?";
$stmt_total = $conn->prepare($total_query);
$stmt_total->bind_param("s", $ip_address);
$stmt_total->execute();
$stmt_total->bind_result($total_messages);
$stmt_total->fetch();
$stmt_total->close();

$total_pages = ceil($total_messages / $limit);

ob_end_flush(); // Flush the output buffer and send output
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP-Based Messaging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/index.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        

<style>
/* General Container Styling */
.container {
    max-width: 90%;
    margin: auto;
    padding: 15px;
}

/* Message Card Styling */
.message-card {
    /*background: linear-gradient(to bottom right, #e3f2fd, #bbdefb);*/
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    margin-bottom: 30px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.message-card:hover {
    /*transform: translateY(-10px);*/
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
}

/* Message Header Styling */
.message-header {
    background: #5985b9;
    color: #ffffff;
    padding: 15px 20px;
    font-weight: bold;
    font-size: 1.2em;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 4px solid #82B959;
}
.header-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-copy {
    background: #42a5f5;
    color: #ffffff;
    border: none;
    padding: 8px 15px;
    font-size: 0.9em;
    border-radius: 25px;
    cursor: pointer;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.btn-copy:hover {
    background: #2196f3;
    box-shadow: 0px 5px 10px rgba(66, 165, 245, 0.4);
}

/* Message Content Styling */
.message-content {
    padding: 25px;
    font-size: 1.1em;
    color: #333;
    line-height: 1.8;
    background: #ffffff;
    border-radius: 0 0 15px 15px;
}

/* Message Footer Styling */
.message-footer {
    background: linear-gradient(90deg, #f3f6f9, #e3eaf2);
    padding: 15px 20px;
    font-size: 0.9em;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px solid #bbdefb;
}

.message-footer em {
    font-style: italic;
    color: #555;
}

.message-footer form .btn {
    padding: 8px 15px;
    border-radius: 20px;
    background: #e53935;
    color: #ffffff;
    border: none;
    font-size: 0.9em;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.message-footer form .btn:hover {
    background: #c62828;
    transform: scale(1.1);
}

.btn-danger {
    background-color: #dc3545;
    border: none;
    padding: 5px 10px;
    font-size: 14px;
    color: #fff;
    border-radius: 3px;
    cursor: pointer;
}

.btn-danger:hover {
    background-color: #c82333;
}

    .post-btn {
        background-color: #629f33;
        font:30px;
        width:300px;
        border: none;
        color: white;
        margin: 0;
        border-radius: 30px;
        /*padding: 12px 24px;*/
        box-shadow: 0px 4px 6px rgba(0, 123, 255, 0.2);
        transition: all 0.3s ease;
    }

    .post-btn:hover {

        background-color: #82b859;
        color: white;
        box-shadow: 0px 6px 8px rgba(0, 86, 179, 0.3);
    }
    
        .custom-button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        cursor: pointer;
        transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s;
        display: block;
        margin: auto;
    }

    .custom-button:hover {
        background-color: #0056b3;
        color: white;
        box-shadow: 0px 6px 12px rgba(0, 86, 179, 0.3);
        transform: translateY(-3px);
    }

/* Modal Header */
.modal-header {
    background: #5985B9;
    color: white;
    padding: 20px;
    border-bottom: 4px solid #82B959;
    font-size: 1.5rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: -50px;
    left: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: headerPulse 6s infinite;
}


.modal-header h5 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.7;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-header .btn-close:hover {
    transform: rotate(90deg);
    opacity: 1;
}

/* Modal Footer */
.modal-footer {
    background: #ffff;
    color: #555555;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}


/* Buttons */
.modal-footer .btn {
    font-size: 16px;
    font-weight: bold;
    padding: 10px 20px;
    border-radius: 25px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
}

.modal-footer .btn-model {
    background-color: #007bff;
    color:white;
    border: none;
}

.modal-footer .btn-model:hover {
    background-color: #0056b3;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

.modal-footer .btn-danger {
    background-color: #ff4c4c;
    border: none;
}

.modal-footer .btn-danger:hover {
    background-color: #d32f2f;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

 /* Wrapped Content Styling */
    .message-content {
        padding: 25px;
        font-size: 1.1em;
        color: #333;
        line-height: 1.8;
        background: #ffffff;
        border-radius: 0 0 15px 15px;
        max-height: none;
        overflow: visible;
    }

    .message-content.wrapped {
        max-height: 200px;
        overflow-y: auto;
    }

 .btn-wrap {
        background: #42a5f5;
        color: #ffffff;
        border: none;
        padding: 8px 15px;
        font-size: 0.9em;
        border-radius: 25px;
        cursor: pointer;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        position: absolute;
        left: 50%;
        
        transform: translateX(-50%);
    }


    .btn-wrap:hover {
        background: #2196f3;
        box-shadow: 0px 5px 10px rgba(66, 165, 245, 0.4);
    }
    
       /* Message Header Styling */
    .message-header {
        background: #5985b9;
        color: #ffffff;
        padding: 15px 20px;
        font-weight: bold;
        font-size: 1.2em;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 4px solid #82B959;
    }

/* Toggle Button Styling */
.toggle-wrap {
    position: absolute;
    left: 88%;
    margin-top: 4px;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-wrap input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input:checked + .slider {
    background-color: #4caf50;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

/* Wrapped Content Styling */
.message-content {
    padding: 25px;
    font-size: 1.1em;
    color: #333;
    line-height: 1.8;
    background: #ffffff;
    border-radius: 0 0 15px 15px;
    max-height: none;
    overflow: visible;
    transition: max-height 0.3s ease, overflow 0.3s ease;
}

.message-content.wrapped {
    max-height: 200px;
    overflow-y: auto;
}

</style>
</head>

<body>


    <nav class="navbar navbar-expand-lg navbar-dark bg-blue">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>Dashboard</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="./index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/ip_file.php">IP Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/dashboard.php">Personal Files</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://via.placeholder.com/30" alt="User Avatar" class="rounded-circle me-1">
                            <!-- Username -->
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo '<li class="nav-but"><a href="#" class="dropdown-item">Profile</a></li>';
                                echo '<li class="nav-but"><a href="./php/logout.php" class="dropdown-item">Logout</a></li>';
                            } else {
                                // User is not logged in, show the Signup & Login buttons
                                echo '<li class="nav-but"><a href="./php/signup.php" class="dropdown-item">Sign Up</a></li>';
                                echo '<li class="nav-but"><a href="./php/login.php" class="dropdown-item">Login</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-5 mb-5">
    <?php if ($message_count < 10 || isset($_SESSION['user_id'])): ?>
        <!-- Trigger Button -->
        <button type="button" class="post-btn sticky-post-btn d-grid gap-2 col-6 mx-auto mt-5"
            data-bs-toggle="modal" data-bs-target="#messageModal">
            Post a Message
        </button><br><br>
        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
    <div class="col-md-12">
        <div class="message-card" id="message-card-<?php echo $row['id']; ?>">
            <div class="message-header">
                <div class="header-title">
                    <?php echo htmlspecialchars($row['ip_address']); ?>
                    <button class="btn-copy" onclick="copyText('<?php echo $row['id']; ?>')">Copy</button>
                </div>
                <!-- Toggle Button -->
<label class="toggle-wrap">
    <input type="checkbox" checked onclick="toggleWrap('<?php echo $row['id']; ?>')">
    <span class="slider"></span>
</label>
            </div>
            <!--<div class="message-content" id="message-<?php echo $row['id']; ?>">-->
            <div id="message-<?php echo $row['id']; ?>" class="message-content wrapped">
                <?php echo nl2br(htmlspecialchars($row['message'])); ?>
            </div>
            <div class="message-footer">
                <!--<em><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></em>-->
                <span id="timer-<?php echo $row['id']; ?>" data-delete-at="<?php echo $row['delete_at']; ?>"></span>

                <!-- Delete Button -->
                <form method="POST" class="d-inline">
                    <input type="hidden" name="delete_message_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): // Show pagination only if there are more than 1 page ?>
                <nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">
                    <ul class="pagination pagination-lg">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo $page - 1; ?>"
                                    aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($page == $i)
                                echo 'active'; ?>">
                                <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link rounded-circle shadow-sm" href="?page=<?php echo $page + 1; ?>"
                                    aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <h4>
                <p class="text-muted text-center mt-2">No messages yet.</p>
            </h4>
        <?php endif; ?>

<!-- Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="compilerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="compilerModalLabel">
                    <i class="bi bi-chat-dots-fill"></i> <span class="fw-bold">Post Your Message</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Modal Body -->
            <form id="messageForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
                <div class="modal-body bg-light text-black">
                    <div class="compiler-container">
                        <textarea 
                            id="messageInput" 
                            placeholder="Type your message here..." 
                            class="form-control text-black bg-light border-0" 
                            name="message" 
                            rows="10" 
                            required>
                        </textarea>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <span id="lineCount" class="ms-start">Total Lines: 0</span>
                    <!--<button type="button" class="btn btn-danger" data-bs-dismiss="modal">-->
                    <!--    <i class="bi bi-x-circle-fill"></i> Close-->
                    <!--</button>-->
                     <button type="submit" class="btn btn-model">
                        <i class="bi bi-send-fill"></i> Post Message
                    </button>
                     <!--<button type="button" id="postMessageBtn" class="btn btn-model">Post Message</button>-->
                </div>
            </form>
        </div>
    </div>
</div>





    <?php else: ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Limit Reached!',
                    text: 'You have reached the daily message limit. Please log in to post more messages.',
                    icon: 'warning',
                    confirmButtonText: 'Login',
                    showCancelButton: true,
                    cancelButtonText: 'Signup',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    },
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = './php/login.php';
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = './php/signup.php';
                    }
                });
            });
        </script>
    <?php endif; ?>
</div>


    <!-- Scroll-Up Button -->
    <button id="scrollUpBtn">
        <img src="./assets/img/scrollup.png" alt="Scroll Up">
    </button>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <script>
        // Scroll-Up Button Functionality
        const scrollUpBtn = document.getElementById("scrollUpBtn");

        window.onscroll = function () {
            // Show button when scrolled down 300px
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollUpBtn.style.display = "block";
            } else {
                scrollUpBtn.style.display = "none";
            }
        };

        scrollUpBtn.onclick = function () {
            // Scroll back to the top smoothly
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        };
    </script>

    <script>
        // Message Timer Functionality
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.message-footer span[data-delete-at]').forEach(function (timerElement) {
                const deleteAt = new Date(timerElement.dataset.deleteAt);
                const timerId = setInterval(function () {
                    const now = new Date();
                    const diff = deleteAt - now;
                    if (diff <= 0) {
                        clearInterval(timerId);
                        const messageCard = timerElement.closest('.message-card');
                        if (messageCard) {
                            messageCard.classList.add('hidden');
                        }
                    }
                }, 1000);
            });
        });

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </script>


    <script>
            document.addEventListener("DOMContentLoaded", function () {
                let messages = document.querySelectorAll('[id^="message-card-"]');
            let currentMessage = 0;
            const messageInterval = 2000;

            function showNextMessage() {
                    if (messages[currentMessage]) {
                messages[currentMessage].classList.remove('hidden');
            currentMessage++;
                    }
                }

            let messageIntervalID = setInterval(showNextMessage, messageInterval);
                
                messages.forEach((message) => {
                message.addEventListener('mouseenter', () => {
                    clearInterval(messageIntervalID);
                });
                    
                    message.addEventListener('mouseleave', () => {
                messageIntervalID = setInterval(showNextMessage, messageInterval);
                    });
                });
                
                document.querySelectorAll('[id^="timer-"]').forEach(timer => {
                    const deleteAt = new Date(timer.getAttribute('data-delete-at')).getTime();
                
                    const updateTimer = () => {
                    const now = new Date().getTime();
            const distance = deleteAt - now;

            if (distance < 0) {
                timer.innerHTML = "Message will be expired soon";
                    } else {
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            timer.innerHTML = `Deleting in: ${minutes}m ${seconds}s`;
                    }
                };

            setInterval(updateTimer, 1000);
            });
        });

            function copyText(id) {
            const content = document.getElementById(`message-${id}`).textContent;
            const textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Text copied to clipboard');
        }

            function copyText(id) {
            const content = document.getElementById(`message-${id}`).textContent;
            const textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    </script>
    <script>

        // Automatically focus the input field when the modal is shown
        $('#messageModal').on('shown.bs.modal', function () {
            $('#messageInput').focus();
        });
        
        // Submit the form when Enter key is pressed
        $('#messageInput').keypress(function (e) {
            if (e.which === 13 && !e.shiftKey) { // Enter key pressed
                e.preventDefault();
                $('#messageForm').submit();
            }
        });
    </script>

    <script>
            // Example to dynamically show/hide the sticky button based on scroll position
            window.addEventListener('scroll', function() {
        const stickyButton = document.querySelector('.sticky-button');
        if (window.scrollY > 100) {
                stickyButton.style.display = 'block'; // Show the button when scrolled down
        } else {
                stickyButton.style.display = 'none'; // Hide the button when at the top
        }
    });

            // Ensure the button is initially visible
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.sticky-button').style.display = 'block';
    });
    </script>
<script>

document.addEventListener('DOMContentLoaded', function () {
    const messageInput = document.getElementById('messageInput');
    const lineNumbers = document.getElementById('lineNumbers');

    // Function to update line numbers
    const updateLineNumbers = () => {
        const totalLines = messageInput.value.split('\n').length; // Get number of lines
        lineNumbers.innerHTML = ''; // Clear existing numbers

        for (let i = 1; i <= totalLines; i++) {
            const line = document.createElement('pre');
            line.textContent = i; // Add line number
            lineNumbers.appendChild(line);
        }
    };

    // Sync scrolling between textarea and line numbers
    messageInput.addEventListener('scroll', () => {
        lineNumbers.scrollTop = messageInput.scrollTop; // Sync scroll
    });

    // Update line numbers on input
    messageInput.addEventListener('input', updateLineNumbers);

    // Initialize line numbers
    updateLineNumbers();
});



// Function to count the number of lines in the textarea
function countLines() {
    var text = document.getElementById('messageInput').value;
    var lines = text.split('\n').length;
    document.getElementById('lineCount').textContent = "Total Lines: " + lines;
}

// Add event listener to update line count as user types
document.getElementById('messageInput').addEventListener('input', countLines);

// Initialize line count on modal open
document.getElementById('messageModal').addEventListener('shown.bs.modal', function() {
    countLines(); // Update line count when modal is opened
});

</script>

<script>
$(document).ready(function () {
    // Ensure the message input is focused when the modal is shown
    $('#messageModal').on('shown.bs.modal', function () {
        $('#messageInput').focus();
    });

    // Handle the button click to submit the form
    $('#postMessageBtn').on('click', function () {
        $('#messageForm').submit();
    });

    // Form submission with AJAX
    $('#messageForm').on('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        const message = $('#messageInput').val();
        if (message.trim() === '') {
            alert('Message cannot be empty!');
            return;
        }

        // AJAX request to submit the message
        $.ajax({
            type: 'POST',
            url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>',
            data: { message: message },
            success: function (response) {
                // alert('Message posted successfully!');
                location.reload(); // Reload to fetch the new message
            },
            error: function () {
                alert('Failed to post message!');
            }
        });
    });
});

</script>

<script>
function toggleWrap(id) {
    const content = document.getElementById(`message-${id}`);
    if (content) {
        content.classList.toggle('wrapped');
    }
}

</script>




    <!-- Include Bootstrap and SweetAlert2 scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

</body>
</html>
