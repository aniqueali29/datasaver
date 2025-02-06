<?php
session_start();
include '../connection/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details
$email = $_SESSION['email'];
$query = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    header("Location: login.php");
    exit;
}

$user = $result->fetch_assoc();
$name = $user['name'];
$userId = $user['id'];

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = $conn->real_escape_string($_POST['category']);
    $tags = $conn->real_escape_string($_POST['tags']);
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;

    // Insert post into database
    $query = "INSERT INTO posts (user_id, title, content, category, tags, is_pinned) 
              VALUES ('$userId', '$title', '$content', '$category', '$tags', '$isPinned')";
    if ($conn->query($query)) {
        $postId = $conn->insert_id;

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $image_name = $_FILES['image']['name'];
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_path = "../uploads/" . basename($image_name);

            // Validate image file type and size
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_type = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
            $file_size = $_FILES['image']['size'];

            if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) { // 5MB max size
                if (move_uploaded_file($image_tmp, $image_path)) {
                    $query = "UPDATE posts SET image_path = '$image_path' WHERE id = '$postId'";
                    $conn->query($query);
                }
            } else {
                $error = "Invalid image file type or size (max 5MB).";
            }
        }

        header("Location: community.php");
        exit;
    } else {
        $error = "Error creating post: " . $conn->error;
    }
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $postId = intval($_POST['post_id']);
    $content = $conn->real_escape_string($_POST['content']);

    // Insert reply into database
    $query = "INSERT INTO replies (post_id, user_id, content) VALUES ('$postId', '$userId', '$content')";
    if ($conn->query($query)) {
        header("Location: community.php?id=$postId");
        exit;
    } else {
        $error = "Error submitting reply: " . $conn->error;
    }
}

// Handle upvote/downvote
if (isset($_GET['vote'])) {
    $postId = intval($_GET['post_id']);
    $voteType = $conn->real_escape_string($_GET['vote']);

    // Check if user has already voted
    $query = "SELECT * FROM votes WHERE user_id = '$userId' AND post_id = '$postId'";
    $result = $conn->query($query);
    $existingVote = $result->fetch_assoc();

    if ($existingVote) {
        // Update existing vote
        $query = "UPDATE votes SET type = '$voteType' WHERE id = '{$existingVote['id']}'";
        $conn->query($query);
    } else {
        // Insert new vote
        $query = "INSERT INTO votes (user_id, post_id, type) VALUES ('$userId', '$postId', '$voteType')";
        $conn->query($query);
    }

    // Update post vote count
    $query = "SELECT COUNT(*) as upvotes FROM votes WHERE post_id = '$postId' AND type = 'upvote'";
    $result = $conn->query($query);
    $upvotes = $result->fetch_assoc()['upvotes'];

    $query = "SELECT COUNT(*) as downvotes FROM votes WHERE post_id = '$postId' AND type = 'downvote'";
    $result = $conn->query($query);
    $downvotes = $result->fetch_assoc()['downvotes'];

    $query = "UPDATE posts SET upvotes = '$upvotes', downvotes = '$downvotes' WHERE id = '$postId'";
    $conn->query($query);

    header("Location: community.php?id=$postId");
    exit;
}

// Fetch a single post for details view
$post = null;
$replies = [];
if (isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    $query = "SELECT posts.*, users.name as author 
              FROM posts 
              JOIN users ON posts.user_id = users.id 
              WHERE posts.id = '$postId'";
    $result = $conn->query($query);

    if ($result->num_rows === 1) {
        $post = $result->fetch_assoc();

        // Fetch replies for the post
        $query = "SELECT replies.*, users.name as author 
                  FROM replies 
                  JOIN users ON replies.user_id = users.id 
                  WHERE replies.post_id = '$postId' 
                  ORDER BY replies.created_at ASC";
        $result = $conn->query($query);
        $replies = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Post not found.";
    }
}

// Fetch all posts
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

$query = "SELECT posts.*, users.name as author 
          FROM posts 
          JOIN users ON posts.user_id = users.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR content LIKE '%$search%' OR tags LIKE '%$search%')";
}
if (!empty($categoryFilter)) {
    $query .= " AND category = '$categoryFilter'";
}
$query .= " ORDER BY is_pinned DESC, created_at DESC";

$result = $conn->query($query);
$posts = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
/* Modern and Beautiful Styling for community.php */

/* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    color: #333;
}



/* Main Container */
.container {
    max-width: 900px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

/* Cards */
.card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 15px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: 0.3s;
}

.btn:hover {
    background: #0056b3;
}

/* Forms */
input[type="text"], textarea {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
    font-size: 16px;
}

/* Replies */
.reply {
    padding: 15px;
    background: #f9f9f9;
    border-left: 5px solid #007bff;
    margin: 10px 0;
    border-radius: 5px;
}

/* Footer */
.footer {
    text-align: center;
    padding: 20px;
    background: #007bff;
    color: white;
    margin-top: 30px;
}

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="community.php">Community Hub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="community.php?action=create">Create Post</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['id']) && $post): ?>
            <!-- Read More Page -->
            <h1 class="text-center mb-4"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="<?= $post['image_path'] ?>" alt="Post Image" class="img-fluid mb-3">
                    <?php endif; ?>
                    <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <span class="badge bg-primary"><?= $post['category'] ?></span>
                    <p class="text-muted mt-2">By: <?= $post['author'] ?></p>
                    <p class="text-muted">Tags: <?= $post['tags'] ?></p>
                    <p class="text-muted">Upvotes: <?= $post['upvotes'] ?> | Downvotes: <?= $post['downvotes'] ?></p>
                    <a href="community.php?vote=upvote&post_id=<?= $post['id'] ?>" class="icon-btn"><i class="fas fa-thumbs-up"></i></a>
                    <a href="community.php?vote=downvote&post_id=<?= $post['id'] ?>" class="icon-btn"><i class="fas fa-thumbs-down"></i></a>
                </div>
            </div>

            <!-- Reply Form -->
            <div class="mt-4">
                <h3>Reply to this Post</h3>
                <form method="POST">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="3" placeholder="Write your reply..." required></textarea>
                    </div>
                    <button type="submit" name="submit_reply" class="btn btn-primary">Submit Reply</button>
                </form>
            </div>

            <!-- Display Replies -->
            <div class="mt-4">
                <h3>Replies</h3>
                <?php if (!empty($replies)): ?>
                    <?php foreach ($replies as $reply): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p class="card-text"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                <p class="text-muted">By: <?= $reply['author'] ?></p>
                                <p class="text-muted">Posted on: <?= $reply['created_at'] ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No replies yet.</p>
                <?php endif; ?>
            </div>
        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'create'): ?>
            <!-- Create Post Form -->
            <h1 class="text-center mb-4">Create a New Post</h1>
            <form method="POST" enctype="multipart/form-data" class="mx-auto" style="max-width: 600px;">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="Knowledge">Knowledge</option>
                        <option value="File Sharing">File Sharing</option>
                        <option value="Q&A">Q&A</option>
                        <option value="Privacy">Privacy</option>
                        <option value="News">News</option>
                        <option value="Feedback">Feedback</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tags" class="form-label">Tags (comma-separated)</label>
                    <input type="text" class="form-control" id="tags" name="tags">
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Upload Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <img id="image-preview" src="#" alt="Image Preview" class="image-preview" style="display: none;">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned">
                    <label class="form-check-label" for="is_pinned">Pin this post</label>
                </div>
                <button type="submit" name="create_post" class="btn btn-primary">Submit</button>
            </form>
        <?php else: ?>
            <!-- List of Posts -->
            <h1 class="text-center mb-4">Community Posts</h1>
            <div class="row">
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card <?= $post['is_pinned'] ? 'pinned-post' : '' ?>" onclick="window.location.href='community.php?id=<?= $post['id'] ?>'">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                <?php if (!empty($post['image_path'])): ?>
                                    <img src="<?= $post['image_path'] ?>" alt="Post Image" class="img-fluid mb-3">
                                <?php endif; ?>
                                <p class="card-text"><?= nl2br(htmlspecialchars(substr($post['content'], 0, 200))) ?>...</p>
                                <span class="badge bg-primary"><?= $post['category'] ?></span>
                                <p class="text-muted mt-2">By: <?= $post['author'] ?></p>
                                <p class="text-muted">Tags: <?= $post['tags'] ?></p>
                                <p class="text-muted">Upvotes: <?= $post['upvotes'] ?> | Downvotes: <?= $post['downvotes'] ?></p>
                                <a href="community.php?vote=upvote&post_id=<?= $post['id'] ?>" class="icon-btn"><i class="fas fa-thumbs-up"></i></a>
                                <a href="community.php?vote=downvote&post_id=<?= $post['id'] ?>" class="icon-btn"><i class="fas fa-thumbs-down"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p>&copy; 2023 Community Hub. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Image preview for the create post form
        document.getElementById('image').addEventListener('change', function(event) {
            const imagePreview = document.getElementById('image-preview');
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    </script>
</body>
</html>