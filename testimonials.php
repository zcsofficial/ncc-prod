<?php
include 'db.php';
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle testimonial submission (Add)
$notificationMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isAdmin && isset($_POST['add_testimonial'])) {
    $title = $_POST['title'];
    $name = $_POST['name'];
    $rank = $_POST['rank'];
    $description = $_POST['description'];

    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetDirectory = "uploads/";
        $targetFile = $targetDirectory . $imageName;

        if (getimagesize($_FILES['image']['tmp_name']) !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $imageName;
            } else {
                $notificationMessage = "Error uploading image.";
            }
        } else {
            $notificationMessage = "File is not an image.";
        }
    }

    if ($title && $name && $rank && $description && !$notificationMessage) {
        try {
            $query = "INSERT INTO testimonials (image, title, name, `rank`, description) 
                      VALUES (:image, :title, :name, :rank, :description)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'image' => $image,
                'title' => $title,
                'name' => $name,
                'rank' => $rank,
                'description' => $description
            ]);
            $notificationMessage = "Alumini added successfully!";
        } catch (PDOException $e) {
            $notificationMessage = "Error: " . $e->getMessage();
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all fields.";
    }
}

// Handle testimonial edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isAdmin && isset($_POST['edit_testimonial'])) {
    $id = $_POST['testimonial_id'];
    $title = $_POST['title'];
    $name = $_POST['name'];
    $rank = $_POST['rank'];
    $description = $_POST['description'];

    $image = $_POST['existing_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetDirectory = "uploads/";
        $targetFile = $targetDirectory . $imageName;

        if (getimagesize($_FILES['image']['tmp_name']) !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $imageName;
            } else {
                $notificationMessage = "Error uploading image.";
            }
        } else {
            $notificationMessage = "File is not an image.";
        }
    }

    if ($title && $name && $rank && $description && !$notificationMessage) {
        try {
            $query = "UPDATE testimonials SET image = :image, title = :title, name = :name, `rank` = :rank, description = :description 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'image' => $image,
                'title' => $title,
                'name' => $name,
                'rank' => $rank,
                'description' => $description,
                'id' => $id
            ]);
            $notificationMessage = "Testimonial updated successfully!";
        } catch (PDOException $e) {
            $notificationMessage = "Error: " . $e->getMessage();
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all fields.";
    }
}

// Handle testimonial deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isAdmin && isset($_POST['delete_testimonial'])) {
    $id = $_POST['testimonial_id'];

    try {
        $query = "DELETE FROM testimonials WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $id]);
        $notificationMessage = "Testimonial deleted successfully!";
    } catch (PDOException $e) {
        $notificationMessage = "Error: " . $e->getMessage();
    }
}

// Fetch testimonials for display
try {
    $query = "SELECT * FROM testimonials ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
    echo "Error fetching testimonials: " . $e->getMessage();
}

// Fetch notifications for the logged-in user
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
        $stmt->bindParam(':user_id', $userId);
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials - NCC Journey</title>

    <!-- External CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
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
        }

        h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .card-text {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-body {
            padding: 20px;
        }

        .profile-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-top: -40px;
            margin-bottom: 15px;
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 30px 0;
            text-align: center;
            flex-shrink: 0;
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

        .notification {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 9999;
            display: none;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .notification.show {
            display: block;
            opacity: 1;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @media (max-width: 767px) {
            h2 {
                font-size: 1.5rem;
            }
            .card img {
                height: 150px;
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
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="camp.php">Camps</a></li>
                    <li class="nav-item"><a class="nav-link" href="testimonials.php">Alumini</a></li>
                    <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <?php if ($isLoggedIn): ?>
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

    <!-- Notification -->
    <?php if ($notificationMessage): ?>
        <div id="notification" class="notification show <?php echo strpos($notificationMessage, 'Error') !== false ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($notificationMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container my-5">
            <?php if ($isAdmin): ?>
                <div class="d-flex justify-content-start mb-3">
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTestimonialModal">
                        <i class="fas fa-plus-circle"></i> Add Alumini
                    </button>
                </div>
            <?php endif; ?>

            <!-- Testimonial Section -->
            <section id="testimonials" class="py-5">
                <h2 class="text-center mb-4">Alumini</h2>
                <div class="row">
                    <?php if (empty($testimonials)): ?>
                        <p class="text-center">No Alumini available yet.</p>
                    <?php else: ?>
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="col-md-4 mb-3">
    <div class="card">
        <img src="<?php echo htmlspecialchars(!empty($testimonial['image']) ? "uploads/{$testimonial['image']}" : 'https://via.placeholder.com/400x300'); ?>" alt="Testimonial Image">
        <div class="card-body">
            <div class="d-flex justify-content-start">
                <img src="<?php echo htmlspecialchars(!empty($testimonial['image']) ? "uploads/{$testimonial['image']}" : 'https://via.placeholder.com/80'); ?>" class="profile-img" alt="Profile Image">
                <div>
                    <h5 class="card-title"><?php echo htmlspecialchars($testimonial['title']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($testimonial['description']), 0, 100); ?>...</p>
                </div>
            </div>
            <?php if ($isAdmin): ?>
                <div class="mt-2">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editTestimonialModal_<?php echo $testimonial['id']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTestimonialModal_<?php echo $testimonial['id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            <?php endif; ?>
           
        </div>
    </div>
</div>
                            </div>

                            <!-- Edit Testimonial Modal -->
                            <?php if ($isAdmin): ?>
                                <div class="modal fade" id="editTestimonialModal_<?php echo $testimonial['id']; ?>" tabindex="-1" aria-labelledby="editTestimonialModalLabel_<?php echo $testimonial['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editTestimonialModalLabel_<?php echo $testimonial['id']; ?>">Edit Alumini</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="testimonials.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($testimonial['image']); ?>">
                                                    <div class="mb-3">
                                                        <label for="title_<?php echo $testimonial['id']; ?>" class="form-label">Title</label>
                                                        <input type="text" class="form-control" name="title" id="title_<?php echo $testimonial['id']; ?>" value="<?php echo htmlspecialchars($testimonial['title']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="name_<?php echo $testimonial['id']; ?>" class="form-label">Name</label>
                                                        <input type="text" class="form-control" name="name" id="name_<?php echo $testimonial['id']; ?>" value="<?php echo htmlspecialchars($testimonial['name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="rank_<?php echo $testimonial['id']; ?>" class="form-label">Rank</label>
                                                        <input type="text" class="form-control" name="rank" id="rank_<?php echo $testimonial['id']; ?>" value="<?php echo htmlspecialchars($testimonial['rank']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description_<?php echo $testimonial['id']; ?>" class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" id="description_<?php echo $testimonial['id']; ?>" rows="4" required><?php echo htmlspecialchars($testimonial['description']); ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="image_<?php echo $testimonial['id']; ?>" class="form-label">Image</label>
                                                        <input type="file" class="form-control" name="image" id="image_<?php echo $testimonial['id']; ?>">
                                                        <?php if ($testimonial['image']): ?>
                                                            <small>Current image: <?php echo htmlspecialchars($testimonial['image']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="submit" name="edit_testimonial" class="btn btn-primary">Update</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Testimonial Modal -->
                                <div class="modal fade" id="deleteTestimonialModal_<?php echo $testimonial['id']; ?>" tabindex="-1" aria-labelledby="deleteTestimonialModalLabel_<?php echo $testimonial['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteTestimonialModalLabel_<?php echo $testimonial['id']; ?>">Delete Alumini</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="testimonials.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                    <p>Are you sure you want to delete this Alumini by <?php echo htmlspecialchars($testimonial['name']); ?>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_testimonial" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Add Testimonial Modal -->
    <?php if ($isAdmin): ?>
        <div class="modal fade" id="addTestimonialModal" tabindex="-1" aria-labelledby="addTestimonialModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTestimonialModalLabel">Add Alumini</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="testimonials.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="rank" class="form-label">Rank</label>
                                <input type="text" class="form-control" name="rank" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" name="image">
                            </div>
                            <button type="submit" name="add_testimonial" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date('Y'); ?> NCC Journey. All rights reserved.</p>
            <div>
                <a href="#" class="footer-icon"><i class="fab fa-facebook"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // Show the notification for 3 seconds
        window.onload = function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(function() {
                    notification.classList.remove('show');
                    setTimeout(() => notification.style.display = 'none', 500); // Wait for fade-out
                }, 3000); // Hide after 3 seconds
            }
        }
    </script>
</body>
</html>