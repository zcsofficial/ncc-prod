<?php
include 'db.php';
session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notificationMessage = '';
$notificationType = '';

// Fetch all posts for the admin
try {
    $query = "
        SELECT posts.id, posts.title, posts.body, posts.created_at, posts.updated_at, users.username, posts.image
        FROM posts
        JOIN users ON posts.author_id = users.id
        ORDER BY posts.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notificationMessage = "Error fetching posts: " . $e->getMessage();
    $notificationType = "danger";
}

// Add Post
if (isset($_POST['add_post'])) {
    $title = htmlspecialchars($_POST['title']);
    $body = htmlspecialchars($_POST['body']);
    $author_id = $_SESSION['user_id'];

    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_formats = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_dir = 'uploads/';
        $imagePath = $upload_dir . $file_name;

        if ($file_size <= $max_file_size && in_array($file_type, $allowed_formats)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $image = $imagePath;
            } else {
                $notificationMessage = "Failed to upload image.";
                $notificationType = "danger";
            }
        } else {
            $notificationMessage = "Image must be JPG, PNG, or JPEG and not exceed 5 MB.";
            $notificationType = "danger";
        }
    }

    if ($title && $body && !$notificationMessage) {
        try {
            $insert_query = "INSERT INTO posts (title, body, author_id, image) VALUES (:title, :body, :author_id, :image)";
            $stmt = $conn->prepare($insert_query);
            $stmt->execute([
                'title' => $title,
                'body' => $body,
                'author_id' => $author_id,
                'image' => $image
            ]);
            $notificationMessage = "Blog post added successfully!";
            $notificationType = "success";
        } catch (PDOException $e) {
            $notificationMessage = "Error adding post: " . $e->getMessage();
            $notificationType = "danger";
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all required fields.";
        $notificationType = "danger";
    }
}

// Edit Post
$post_to_edit = null;
if (isset($_GET['edit'])) {
    $post_id = $_GET['edit'];
    try {
        $edit_query = "SELECT * FROM posts WHERE id = :post_id";
        $stmt = $conn->prepare($edit_query);
        $stmt->bindValue(':post_id', $post_id);
        $stmt->execute();
        $post_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $notificationMessage = "Error fetching post for edit: " . $e->getMessage();
        $notificationType = "danger";
    }
}

if (isset($_POST['update_post'])) {
    $post_id = $_POST['post_id'];
    $title = htmlspecialchars($_POST['title']);
    $body = htmlspecialchars($_POST['body']);
    $image = $_POST['existing_image']; // Keep existing image by default

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_formats = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_dir = 'uploads/';
        $imagePath = $upload_dir . $file_name;

        if ($file_size <= $max_file_size && in_array($file_type, $allowed_formats)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $image = $imagePath;
            } else {
                $notificationMessage = "Failed to upload new image.";
                $notificationType = "danger";
            }
        } else {
            $notificationMessage = "Image must be JPG, PNG, or JPEG and not exceed 5 MB.";
            $notificationType = "danger";
        }
    }

    if ($title && $body && !$notificationMessage) {
        try {
            $update_query = "UPDATE posts SET title = :title, body = :body, image = :image WHERE id = :post_id";
            $stmt = $conn->prepare($update_query);
            $stmt->execute([
                'title' => $title,
                'body' => $body,
                'image' => $image,
                'post_id' => $post_id
            ]);
            $notificationMessage = "Blog post updated successfully!";
            $notificationType = "success";
        } catch (PDOException $e) {
            $notificationMessage = "Error updating post: " . $e->getMessage();
            $notificationType = "danger";
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all required fields.";
        $notificationType = "danger";
    }
}

// Delete Post
if (isset($_GET['delete'])) {
    $post_id = $_GET['delete'];
    try {
        $delete_query = "DELETE FROM posts WHERE id = :post_id";
        $stmt = $conn->prepare($delete_query);
        $stmt->bindValue(':post_id', $post_id);
        $stmt->execute();
        $notificationMessage = "Blog post deleted successfully!";
        $notificationType = "success";
    } catch (PDOException $e) {
        $notificationMessage = "Error deleting post: " . $e->getMessage();
        $notificationType = "danger";
    }
}

// Fetch notifications for the logged-in user
try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadCount = 0;
    foreach ($notifications as $notification) {
        if (isset($notification['read']) && $notification['read'] == 0) {
            $unreadCount++;
        }
    }
} catch (PDOException $e) {
    $notifications = [];
    $unreadCount = 0;
}

$isLoggedIn = true;
$isAdmin = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blogs - NCC Journey</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .container {
            max-width: 1200px;
            margin-top: 30px;
        }

        .card-header {
            background-color: #17a2b8;
            color: white;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .btn-custom {
            background-color: #28a745;
            color: white;
        }

        .btn-custom:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .btn-back {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        .card-body {
            background-color: #f8f9fa;
        }

        .form-label {
            font-weight: bold;
        }

        .post-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: #ffffff;
            font-weight: 500;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .notification.show {
            display: block;
            opacity: 1;
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.danger {
            background-color: #dc3545;
        }

        footer {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 20px 0;
            text-align: center;
            flex-shrink: 0;
        }

        footer .social-icons {
            margin: 20px 0;
        }

        footer .footer-icon {
            color: #ffffff;
            font-size: 20px;
            margin: 0 10px;
            transition: color 0.3s;
        }

        footer .footer-icon:hover {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .btn-custom, .btn-back {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar (Integrated Inline) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm rounded-pill mt-3 mx-auto" style="max-width: 95%; padding: 10px 30px;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="logo.png" alt="Logo" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" href="camp.php">Camps</a></li>
                    <li class="nav-item"><a class="nav-link" href="testimonials.php">Testimonial</a></li>
                    <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <div class="dropdown ms-3">
                    <button class="btn btn-outline-secondary rounded-pill" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger"><?= $unreadCount ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php foreach ($notifications as $notification): ?>
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-bell"></i> <?= htmlspecialchars($notification['message']) ?>
                            </a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="notifications.php">View all notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown ms-3">
                    <button class="btn btn-primary dropdown-toggle rounded-pill" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> View Profile</a></li>
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="admin_console.php"><i class="fas fa-cog"></i> Admin Console</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notification -->
    <?php if ($notificationMessage): ?>
        <div class="notification <?php echo $notificationType; ?> show" id="notification">
            <?php echo htmlspecialchars($notificationMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-center mt-3">Manage Blogs</h3>
                <div>
                    <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#addPostModal">
                        <i class="fas fa-plus"></i> Add Post
                    </button>
                    <a href="admin_console.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Admin Console
                    </a>
                </div>
            </div>

            <!-- Blog Posts Table -->
            <div class="card">
                <div class="card-header">
                    <h4>All Blog Posts</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Created At</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($posts)): ?>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['id']); ?></td>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <?php if ($post['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($post['image']); ?>" class="post-image" alt="Post Image">
                                                <?php else: ?>
                                                    No Image
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="manage_blogs.php?edit=<?php echo $post['id']; ?>" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editPostModal_<?php echo $post['id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="manage_blogs.php?delete=<?php echo $post['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No blog posts found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Post Modal -->
    <div class="modal fade" id="addPostModal" tabindex="-1" aria-labelledby="addPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPostModalLabel">Add New Blog Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage_blogs.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="body" class="form-label">Body</label>
                            <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Image (JPG, PNG, JPEG | Max: 5MB)</label>
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="add_post" class="btn btn-custom">Add Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Post Modals -->
    <?php foreach ($posts as $post): ?>
        <div class="modal fade" id="editPostModal_<?php echo $post['id']; ?>" tabindex="-1" aria-labelledby="editPostModalLabel_<?php echo $post['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPostModalLabel_<?php echo $post['id']; ?>">Edit Blog Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="manage_blogs.php" enctype="multipart/form-data">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($post['image']); ?>">
                            <div class="mb-3">
                                <label for="title_<?php echo $post['id']; ?>" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title_<?php echo $post['id']; ?>" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="body_<?php echo $post['id']; ?>" class="form-label">Body</label>
                                <textarea class="form-control" id="body_<?php echo $post['id']; ?>" name="body" rows="5" required><?php echo htmlspecialchars($post['body']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image_<?php echo $post['id']; ?>" class="form-label">Image (JPG, PNG, JPEG | Max: 5MB)</label>
                                <input type="file" class="form-control" id="image_<?php echo $post['id']; ?>" name="image" accept=".jpg,.jpeg,.png">
                                <small class="form-text text-muted">Current: <?php echo htmlspecialchars($post['image'] ?: 'None'); ?></small>
                            </div>
                            <button type="submit" name="update_post" class="btn btn-custom">Update Post</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <footer>
        <div class="social-icons">
            <a href="#" class="footer-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p>© <?php echo date('Y'); ?> NCC Journey. All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        // Notification fade-out
        $(document).ready(function() {
            <?php if ($notificationMessage): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>
        });
    </script>
</body>
</html>