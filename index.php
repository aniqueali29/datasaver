<?php
ob_start();
include './connection/db_config.php'; 
session_start();

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

$ip_address = getUserIP();
if (empty($ip_address)) {
    die("Error: IP address is not set.");
}

error_log("Retrieved IP Address: " . $ip_address);

$message_count = 0;

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
} else {
    header("Location: ../php/login.php");
    exit;
}
    $message_limit = PHP_INT_MAX;
} else {

    $name = "Join Us";
    $message_limit = 15;
    $current_date = date('Y-m-d');

    $query = "SELECT COUNT(*) as message_count FROM ip_messages WHERE ip_address = ? AND DATE(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $ip_address, $current_date);
    $stmt->execute();
    $stmt->bind_result($message_count);
    $stmt->fetch();
    $stmt->close();
}

date_default_timezone_set('Asia/Karachi');

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $delete_id = $_POST['delete_message_id'];

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

$query = "SELECT id, ip_address, message, created_at, delete_at FROM ip_messages WHERE ip_address = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$result = $stmt->get_result();

$total_query = "SELECT COUNT(*) as total FROM ip_messages WHERE ip_address = ?";
$stmt_total = $conn->prepare($total_query);
$stmt_total->bind_param("s", $ip_address);
$stmt_total->execute();
$stmt_total->bind_result($total_messages);
$stmt_total->fetch();
$stmt_total->close();

$total_pages = ceil($total_messages / $limit);

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP-Based Messaging</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="./css/index.css" rel="stylesheet">
    <link href="./css/nav.css" rel="stylesheet">

    <style>
    .dropdown-menu {
        background-color: #E3F2FD !important;
        border: none;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        min-width: 180px;
        padding: 8px 0;
    }

    .dropdown-item {
        padding: 10px 20px;
        font-weight: 500;
        color: #333 !important;
        transition: all 0.3s ease-in-out;
        border-radius: 5px;
    }

    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.5);
        transform: scale(1.05);
        font-weight: 600;
    }

    .nav-link.dropdown-toggle {
        font-weight: bold;
        color: #333 !important;
        padding: 8px 15px;
        border-radius: 5px;
        transition: all 0.3s ease-in-out;
    }

    .nav-link.dropdown-toggle:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }
    </style>
</head>

<body>


    <nav class="navbar navbar-expand-lg navbar-dark ">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <h1>
                    <figure class="logo d-flex align-items-center gap-3"><img src="./assets/img/logo.png" alt="">
                    </figure>
                </h1>
            </a>
            <button class="navbar-toggler" style="filter: invert(1);" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- <button class="navbar-toggler" id="navToggleBtn" style="filter: invert(1);" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" id="toggleIcon"></span>
            </button> -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 ">
                    <li class="nav-item"><a class="nav-link" aria-current="page" href="./index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/view_messages.php">Community Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/ip_file.php">IP Share</a></li>
                    <li class="nav-item"><a class="nav-link" href="./php/dashboard.php">Personal Files</a></li>
                </ul>
                <ul class="navbar-nav" aria-labelledby="userDropdown">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <?php echo htmlspecialchars($name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo '<li class="nav-but"><a href="#" class="dropdown-item">Profile</a></li>';
                                echo '<li class="nav-but"><a href="./php/logout.php" class="dropdown-item">Logout</a></li>';
                            } else {
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <div class="container mb-3">
        <?php if ($message_count < 10 || isset($_SESSION['user_id'])): ?>
        <!-- Trigger Button -->
        <button type="button" class="post-btn gap-2 col-6 mt-5" data-bs-toggle="modal" data-bs-target="#messageModal">
            Post a Message
        </button><br><br>
        <?php if ($result->num_rows > 0): ?>
        <div class="row" style="margin-top: 70px ;">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-12">
                <div class="message-card" id="message-card-<?php echo $row['id']; ?>">
                    <div class="message-header">
                        <div class="header-title">
                            <?php echo htmlspecialchars($row['ip_address']); ?>
                        </div>

                        <button class="btn-copy" onclick="copyText('<?php echo $row['id']; ?>')">
                            <svg class="svgs" id="icon-btn-copy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path
                                    d="M15.98,13.96h-4.74c-.13,0-.24-.11-.24-.24V7.07c0-.13,.11-.24,.24-.24h4.74c.13,0,.24,.11,.24,.24v6.64c0,.13-.11,.24-.24,.24Zm-6.77-7.72V14.55c0,.66,.53,1.19,1.19,1.19h6.41c.66,0,1.19-.53,1.19-1.19V6.24c0-.66-.53-1.19-1.19-1.19h-6.41c-.66,0-1.19,.53-1.19,1.19Z">
                                </path>
                                <path
                                    d="M15.68,18.95H7.19c-.66,0-1.19-.53-1.19-1.19V7.37c0-.49,.4-.89,.89-.89s.89,.4,.89,.89v9.57c0,.13,.1,.23,.23,.23h7.67c.49,0,.89,.4,.89,.89s-.4,.89-.89,.89Z">
                                </path>
                            </svg>
                        </button>


                        <label class="toggle-wrap">
                            <input type="checkbox" checked onclick="toggleWrap('<?php echo $row['id']; ?>')">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div id="message-<?php echo $row['id']; ?>" class="message-content wrapped">
                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                    </div>
                    <div class="message-footer">
                        <span id="timer-<?php echo $row['id']; ?>"
                            data-delete-at="<?php echo $row['delete_at']; ?>"></span>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="delete_message_id" value="<?php echo $row['id']; ?>">
                            <!-- <button type="submit" class="btn btn-danger btn-sm">Delete</button> -->

                            <button class="btn-delete" onclick="deleteAction()">
                                <svg class="icon-delete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                    </path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
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
        <!-- <h4>
            <p class="text-muted text-center" style="margin-top: 120px;">No messages yet.</p>
        </h4> -->

        <div class="container mb-3">
            <div class="row" style="margin-top: 70px;">
                <div class="col-md-12">
                    <div class="guide-card">
                        <div class="guide-header">
                            <h2>Welcome to Our Platform!</h2>
                        </div>
                        <div class="guide-content">
                            <p>Our platform is designed to help you share messages efficiently and securely. Whether
                                you're here to post updates, communicate with others, or simply explore, we've got you
                                covered.</p>
                            <h3>How It Works</h3>
                            <ul>
                                <li>Click on the "Post a Message" button to share your thoughts.</li>
                                <li>Each message has a unique ID and can be copied with the copy button.</li>
                                <li>Use the toggle switch to show or hide message details.</li>
                                <li>Messages are time-sensitive and will be automatically deleted after a set period.
                                </li>
                            </ul>
                            <h3>Getting Started</h3>
                            <p>Begin by clicking the "Post a Message" button. Enter your message, and once posted, it
                                will appear here with a timer indicating its expiry.</p>
                            <p>If you need to delete a message, simply click the delete button, and it will be removed
                                immediately.</p>
                        </div>
                        <div class="guide-footer">
                            <p>We hope you enjoy using our platform! If you have any questions, feel free to reach out.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


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
                        <button type="button" class="btn-close" style="color: red;" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <!-- Modal Body -->
                    <form id="messageForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
                        <div class="modal-body bg-light text-black">
                            <div class="compiler-container">
                                <textarea id="messageInput" placeholder="Type your message here..."
                                    class="form-control text-black bg-light border-0" name="message" rows="10"
                                    required></textarea>
                            </div>
                        </div>
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <span id="lineCount" class="ms-start">Total Lines: 0</span>
                            <button type="submit" class="btn btn-model">
                                <i class="bi bi-send-fill"></i> Post Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php else: ?>
        <script>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const messageInput = document.getElementById('messageInput');
        const lineNumbers = document.getElementById('lineNumbers');

        const updateLineNumbers = () => {
            const messageInput = document.getElementById('messageInput');
            const lineNumbers = document.getElementById('lineNumbers');
            const totalLines = messageInput.value.split('\n').length;
            lineNumbers.innerText = '';

            for (let i = 1; i <= totalLines; i++) {
                const line = document.createElement('pre');
                line.textContent = i;
                lineNumbers.appendChild(line);
            }
        };

        messageInput.addEventListener('scroll', () => {
            lineNumbers.scrollTop = messageInput.scrollTop;
        });

        messageInput.addEventListener('input', updateLineNumbers);

        updateLineNumbers();
    });


    function countLines() {
        var text = document.getElementById('messageInput').value;
        var lines = text.split('\n').length;
        document.getElementById('lineCount').textContent = "Total Lines: " + lines;
    }

    document.getElementById('messageInput').addEventListener('input', countLines);

    document.getElementById('messageModal').addEventListener('shown.bs.modal', function() {
        countLines(); // Update line count when modal is opened
    });
    </script>

    <script>
    $(document).ready(function() {
        $('#messageModal').on('shown.bs.modal', function() {
            $('#messageInput').focus();
        });

        $('#postMessageBtn').on('click', function() {
            $('#messageForm').submit();
        });

        $('#messageForm').on('submit', function(e) {
            e.preventDefault();

            const message = $('#messageInput').val();
            if (message.trim() === '') {
                alert('Message cannot be empty!');
                return;
            }

            $.ajax({
                type: 'POST',
                url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>',
                data: {
                    message: message
                },
                success: function(response) {
                    // alert('Message posted successfully!');
                    location.reload(); // Reload to fetch the new message
                },
                error: function() {
                    alert('Failed to post message!');
                }
            });
        });
    });
    </script>





    <script src="./js/index.js"></script>
</body>

</html>