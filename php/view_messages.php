<?php
ob_start(); // Start output buffering
include '../db_config.php';  // Your database connection file
include '../header.php';

// Set the default timezone
date_default_timezone_set('Asia/Karachi'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $message = $_POST['message'];

        if (!empty($message)) {
            // Set `delete_at` to 30 minutes from now and `created_at` to the current time
            $delete_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));
            $created_at = date("Y-m-d H:i:s");

            // Prepare statements for inserting messages
            $stmt1 = $conn->prepare("INSERT INTO messages (user_id, message, delete_at, created_at) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("isss", $user_id, $message, $delete_at, $created_at);

            $stmt2 = $conn->prepare("INSERT INTO admin_message (user_id, message, delete_at, created_at) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isss", $user_id, $message, $delete_at, $created_at);

            if ($stmt1->execute() && $stmt2->execute()) {
                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error: " . $stmt1->error . " | " . $stmt2->error;
            }

            $stmt1->close();
            $stmt2->close();
        } else {
            echo "Message cannot be empty!";
        }
    } else {
        echo "You must be logged in to post a message.";
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Delete expired messages
$conn->query("DELETE FROM messages WHERE delete_at < NOW()");

// Fetch paginated messages
$query = "SELECT messages.id, users.name, messages.message, messages.created_at, messages.delete_at
          FROM messages
          JOIN users ON messages.user_id = users.id
          ORDER BY messages.created_at DESC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Get total number of messages
$total_query = "SELECT COUNT(*) as total FROM messages";
$total_result = $conn->query($total_query);
$total_messages = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $limit);

ob_end_flush(); // Flush the output buffer and send output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- head content here -->
</head>
<body>
    <!-- body content here -->
</body>
</html>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .message-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .message-card.hidden {
            display: none;
        }

        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .message-header {
            background-color: #007bff;
            color: #ffffff;
            padding: 15px;
            font-weight: bold;
        }

        .message-content {
            padding: 20px;
            font-size: 1.1em;
            color: #555;
        }

        .message-footer {
            background-color: #f1f1f1;
            padding: 10px 20px;
            font-size: 0.9em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-copy {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            transition: background-color 0.2s ease;

        }

        .btn-copy:hover {
            background-color: #0056b3;
        }

        .container {
            max-width: 90%;
            margin: auto;
            padding: 15px;
        }

        .h1 {
            font-size: 2.5rem;
            color: #007bff;
            text-align: center;
            margin-bottom: 40px;
        }

        .pagination {
            justify-content: center;
        }

        /* Scroll-Up Button Styles */
        #scrollUpBtn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99;
            background-color: transparent;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #scrollUpBtn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #scrollUpBtn:hover img {
            transform: scale(1.1);
            opacity: 0.8;
        }


        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .message-content {
                font-size: 1em;
            }

            .btn-copy {
                padding: 4px 8px;
            }
        }

        .trigger-btn {
            background-color: #007bff;
            color: white;
            border-radius: 30px;
            padding: 12px 24px;
            box-shadow: 0px 4px 6px rgba(0, 123, 255, 0.2);
            transition: all 0.3s ease;
        }

        .trigger-btn:hover {
            background-color: #0056b3;
            box-shadow: 0px 6px 8px rgba(0, 86, 179, 0.3);
            transform: translateY(-2px);
        }

        .custom-modal {
            max-width: 600px;
            margin: auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            animation: zoom-in 0.5s ease-out;
        }

        @keyframes zoom-in {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .custom-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .custom-close {
            position: absolute;
            top: 15px;
            right: 15px;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }

        .custom-close:hover {
            color: #ffb3b3;
        }

        .custom-body {
            padding: 2rem;
            background-color: #f7f9fc;
        }

        .custom-textarea {
            width: 100%;
            height: 150px;
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #007bff;
            box-shadow: inset 0 2px 4px rgba(0, 123, 255, 0.1);
            transition: border-color 0.3s;
            resize: none;
        }

        .custom-textarea:focus {
            border-color: #0056b3;
            box-shadow: inset 0 2px 4px rgba(0, 86, 179, 0.3);
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
            box-shadow: 0px 6px 12px rgba(0, 86, 179, 0.3);
            transform: translateY(-3px);
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <h1 class="h1">Community Post</h1>

        <!-- Trigger Button -->
        <button type="button" class="trigger-btn" data-bs-toggle="modal" data-bs-target="#messageModal">
            Post a Message
        </button><br><br>

        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-12">
                        <div class="message-card" id="message-card-<?php echo $row['id']; ?>">
                            <div class="message-header">
                                <?php echo htmlspecialchars($row['name']); ?>
                                <button style="margin-bottom: 10; float: right;" class="btn btn-copy"
                                    onclick="copyText(<?php echo $row['id']; ?>)">Copy Text</button>
                            </div>
                            <div class="message-content" id="message-<?php echo $row['id']; ?>">
                                <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                            </div>
                            <div class="message-footer">
                                <em><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></em> <!-- Display time with AM/PM -->
                                <span id="timer-<?php echo $row['id']; ?>"
                                    data-delete-at="<?php echo $row['delete_at']; ?>"></span>
                                <button class="btn btn-copy" onclick="copyText(<?php echo $row['id']; ?>)">Copy Text</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($page == $i)
                            echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p class="text-muted text-center">No messages yet.</p>
        <?php endif; ?>
    </div>
    
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Modal Structure -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog custom-modal">
                <div class="modal-content">
                    <div class="custom-header">
                        <h5 class="modal-title" id="messageModalLabel">Share Your Thoughts</h5>
                        <span class="custom-close" data-bs-dismiss="modal" aria-label="Close">&times;</span>
                    </div>
                    <div class="custom-body">
                        <form action="" method="POST">
                            <textarea name="message" class="custom-textarea" placeholder="Enter your message here"
                                required></textarea>
                            <br><br>
                            <button type="submit" class="custom-button">Post Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p>You need to log in to post messages.</p>
    <?php endif; ?>
    <!-- Scroll-Up Button -->
    <button id="scrollUpBtn" onclick="scrollUp()">
        <img src="scrollup.png" alt="Scroll Up">
    </button>


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

        // Scroll-Up Button Script
        window.onscroll = function () {
            const scrollUpBtn = document.getElementById("scrollUpBtn");
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                scrollUpBtn.style.display = "block";
            } else {
                scrollUpBtn.style.display = "none";
            }
        };

        function scrollUp() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>