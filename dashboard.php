<?php
include 'db.php';
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch notifications for the logged-in user
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];

    // Fetch notifications from the database
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications
    $unreadCount = 0;
    foreach ($notifications as $notification) {
        if (isset($notification['read']) && $notification['read'] == 0) {
            $unreadCount++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NCC Journey</title>

    <!-- External CSS Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        /* Flexbox layout to push footer to bottom */
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure body takes full viewport height */
        }

        /* Main content grows to push footer down */
        .main-content {
            flex: 1 0 auto; /* Flex-grow to take available space */
        }

        .header {
            background-color: #343a40;
            color: #fff;
            padding: 20px 0;
        }

        .header .navbar-nav .nav-link {
            color: #fff;
            padding: 15px 20px;
        }

        .header .navbar-nav .nav-link:hover {
            background-color: #495057;
            border-radius: 5px;
        }

        h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-weight: 600;
        }

        .card-text {
            color: #6c757d;
        }

        .card img {
            max-height: 200px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .carousel-item img {
            max-height: 500px;
            object-fit: cover;
            transition: transform 0.3s ease-in-out;
        }

        .carousel-item img.zoomed {
            transform: scale(1.5);
        }

        /* Footer stays at bottom */
        footer {
            background-color: #343a40;
            color: white;
            padding: 30px 0;
            text-align: center;
            flex-shrink: 0; /* Prevent footer from shrinking */
        }

        .footer-icon {
            font-size: 1.5rem;
            margin: 0 10px;
            color: white;
            text-decoration: none;
        }

        .footer-icon:hover {
            color: #17a2b8;
        }

        @media (max-width: 767px) {
            h2 {
                font-size: 1.5rem;
            }

            .carousel-item img {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
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

                <!-- Show links only if the user is logged in -->
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="camp.php">Camps</a></li>
                <li class="nav-item"><a class="nav-link" href="testimonials.php">Alumini</a></li>
                <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>

            <!-- Notifications and User Profile -->
            <?php if ($isLoggedIn): ?>
                <!-- Notification Icon -->
                <div class="dropdown ms-3">
                    <button class="btn btn-outline-secondary rounded-pill" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger"><?= $unreadCount ?></span> <!-- Notification count -->
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

                <!-- User Profile and Admin Options -->
                <div class="dropdown ms-3">
                    <button class="btn btn-primary dropdown-toggle rounded-pill" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> View Profile</a></li>
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php if ($isAdmin): ?>
                            <li><a class="dropdown-item" href="admin_console.php"><i class="fas fa-cog"></i> Admin Console</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary ms-3 rounded-pill">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content"> <!-- Wrapped in a div with flex-grow -->
    <div class="container my-5">
        <?php if ($isAdmin): ?>
            <div class="d-flex justify-content-start mb-3">
                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addBlogModal">
                    <i class="fas fa-plus-circle"></i> Add New Blog Post
                </button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addImageModal">
                    <i class="fas fa-image"></i> Upload New Image for Carousel
                </button>
            </div>
        <?php endif; ?>

        <!-- Blog Section -->
        <section id="blogs" class="py-5">
            <h2 class="text-center mb-4">Blogs</h2>
            <div class="row">
                <?php
                $query = "SELECT posts.id, posts.title, posts.body, posts.image, users.username, posts.created_at 
                          FROM posts JOIN users ON posts.author_id = users.id ORDER BY posts.created_at DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($posts as $post):
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars(!empty($post['image']) ? "{$post['image']}" : 'https://via.placeholder.com/400x300'); ?>" alt="Blog Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($post['body']), 0, 100); ?>...</p>
                                <small class="text-muted">By <?php echo htmlspecialchars($post['username']); ?> on <?php echo date('d M Y', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Carousel Section -->
        <section id="carousel" class="py-5">
            <h2 class="text-center mb-4">Cadets Gallery</h2>
            <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $query = "SELECT * FROM carousel_images ORDER BY created_at DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $activeClass = "active";

                    foreach ($images as $image):
                    ?>
                        <div class="carousel-item <?php echo $activeClass; ?>">
                            <img src="<?php echo htmlspecialchars($image['image']); ?>" class="d-block w-100" alt="Carousel Image" onclick="zoomImage(this)">
                        </div>
                    <?php
                        $activeClass = "";
                    endforeach;
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </section>
    </div>
</div> <!-- End of main-content -->

<!-- Modal for Adding Blog Post (Admin Only) -->
<div class="modal fade" id="addBlogModal" tabindex="-1" aria-labelledby="addBlogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="add_blog.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBlogModalLabel">Add New Blog Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="body" class="form-label">Body</label>
                        <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Image Upload (Admin Only) -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addImageModalLabel">Upload New Carousel Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_image.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="carousel_image" class="form-label">Choose an Image</label>
                        <input type="file" class="form-control" name="carousel_image" id="carousel_image" required>
                        <small class="form-text text-muted">Allowed file types: JPG, JPEG, PNG, GIF. Max size: 5MB.</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="container">
        <p>© 2024 NCC Journey. All rights reserved.</p>
        <div>
            <a href="#" class="footer-icon"><i class="fab fa-facebook"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</footer>

<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
<script>
    function zoomImage(img) {
        img.classList.toggle('zoomed');
    }
</script>
</body>
</html>