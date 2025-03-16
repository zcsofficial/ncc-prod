<?php
// index.php
include 'db.php';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Check if the user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($isLoggedIn) {
    // Fetch notifications for the logged-in user
    $userId = $_SESSION['user_id'];

    // Fetch notifications from the database
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications (assuming a 'read' field exists)
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
    <title>NCC Blog & Projects - NCC Journey</title>

    <!-- CSS Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f4f4;
        }

        /* Preloader */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }

        #preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #preloader svg {
            width: 80px;
            height: 80px;
        }

        /* Section Titles */
        h2 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 20px;
        }

        /* NCC Cadet Section */
        #ncc-cadets {
            background-color: #1c2331;
            color: white;
            padding: 60px 15px;
            text-align: center;
        }

        #ncc-cadets .section-title {
            font-size: 2.5rem;
            font-weight: bold;
        }

        #ncc-cadets p {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        /* Blog Section */
        #blogs {
            padding: 60px 15px;
            background-color: #f8f9fa;
        }

        .blog-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card-body {
            flex-grow: 1;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-img-top {
            max-height: 250px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .card-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 0.95rem;
        }

        /* Carousel */
        .carousel-inner img {
            height: 400px;
            object-fit: cover;
        }

        /* Footer */
        footer {
            background-color: #343a40;
            color: white;
            padding: 30px 15px;
            text-align: center;
            margin-top: auto;
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

        /* Responsive Adjustments */
        @media (max-width: 767px) {
            #ncc-cadets .section-title {
                font-size: 2rem;
            }
            .carousel-inner img {
                height: 250px;
            }
            h2 {
                font-size: 1.8rem;
            }
            .card-img-top {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" stroke="#000" stroke-width="5" fill="none" stroke-dasharray="283" stroke-dashoffset="280">
                <animate attributeName="stroke-dashoffset" from="283" to="0" dur="2s" repeatCount="indefinite" />
            </circle>
            <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-size="16" font-family="Arial" fill="#000">NCC</text>
        </svg>
    </div>

    <!-- Your Provided Navbar -->
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

    <!-- NCC Cadet Section -->
    <section id="ncc-cadets">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">Welcome to the NCC Journey <i class="fas fa-users"></i></h2>
            <p class="lead">Our NCC cadets are committed to leadership, discipline, and community service. They are the pride of the nation, marching towards a brighter future with courage and determination.</p>
            <a href="#blogs" class="btn btn-light btn-lg mt-4">Discover Our Projects</a>
        </div>
    </section>

    <!-- Blogs Section -->
    <section id="blogs">
        <div class="container">
            <h2 class="text-center">Blogs <i class="fas fa-project-diagram"></i></h2>
            <div class="row">
                <?php
                try {
                    $query = "SELECT posts.id, posts.title, posts.body, posts.image, users.username, posts.created_at 
                              FROM posts JOIN users ON posts.author_id = users.id ORDER BY posts.created_at DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($posts) {
                        foreach ($posts as $post) {
                            ?>
                            <div class="col-md-4 mb-4">
                                <div class="card blog-card h-100">
                                    <img src="<?php echo htmlspecialchars($post['image'] ?: 'placeholder.jpg'); ?>" class="card-img-top" alt="Blog Image">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($post['body'], 0, 100)); ?>...</p>
                                        <small class="text-muted">By <?php echo htmlspecialchars($post['username']); ?> on <?php echo date('d M Y', strtotime($post['created_at'])); ?></small>
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary mt-3">Read More</a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p class='text-center'>No posts available yet.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p class='text-danger text-center'>Error loading posts: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Carousel Section -->
    <section id="carousel" class="py-5">
        <h2 class="text-center mb-4">Cadets Gallery</h2>
        <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php
                try {
                    $query = "SELECT * FROM carousel_images ORDER BY created_at DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $activeClass = "active";

                    if ($images) {
                        foreach ($images as $image) {
                            ?>
                            <div class="carousel-item <?php echo $activeClass; ?>">
                                <img src="<?php echo htmlspecialchars($image['image']); ?>" class="d-block w-100" alt="Carousel Image">
                            </div>
                            <?php
                            $activeClass = "";
                        }
                    } else {
                        echo "<div class='carousel-item active'><img src='uploads/placeholder.jpg' class='d-block w-100' alt='No images'></div>";
                    }
                } catch (PDOException $e) {
                    echo "<p class='text-danger text-center'>Error loading carousel: " . $e->getMessage() . "</p>";
                }
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

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <p>© <?php echo date('Y'); ?> NCC Cadet Blog. All rights reserved.</p>
            <div>
                <a href="#" class="footer-icon"><i class="fab fa-facebook"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preloader functionality
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            setTimeout(() => {
                preloader.classList.add('hidden');
            }, 500); // Slight delay for smooth UX
        });
    </script>
</body>
</html>