<?php
include 'db.php';
session_start();

// Check if the user is logged in and is admin
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$notificationMessage = '';

// Restrict access to admins only for POST actions
if (!$isAdmin && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $notificationMessage = "You do not have permission to perform this action.";
}

// Handle form submission to add achievement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $isAdmin) {
    $cadet_id = $_POST['cadet_id'];
    $achievement_name = $_POST['achievement_name'];
    $achievement_date = $_POST['achievement_date'];

    $allowed_formats = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    $certificate = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
        $file_tmp = $_FILES['certificate']['tmp_name'];
        $file_name = time() . "_" . basename($_FILES['certificate']['name']);
        $file_type = $_FILES['certificate']['type'];
        $file_size = $_FILES['certificate']['size'];

        if ($file_size <= $max_file_size && in_array($file_type, $allowed_formats)) {
            $upload_dir = 'uploads/';
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $certificate = $file_path;
            } else {
                $notificationMessage = "Failed to upload the certificate.";
            }
        } else {
            $notificationMessage = "File size must not exceed 5 MB and must be JPG, PNG, or JPEG.";
        }
    }

    if ($cadet_id && $achievement_name && $achievement_date && !$notificationMessage) {
        try {
            $stmt = $conn->prepare("INSERT INTO achievements (cadet_id, achievement_name, achievement_date, certificate) VALUES (:cadet_id, :achievement_name, :achievement_date, :certificate)");
            $stmt->execute([
                'cadet_id' => $cadet_id,
                'achievement_name' => $achievement_name,
                'achievement_date' => $achievement_date,
                'certificate' => $certificate
            ]);
            $notificationMessage = "Achievement added successfully!";
        } catch (PDOException $e) {
            $notificationMessage = "Error: " . $e->getMessage();
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all required fields.";
    }
}

// Handle achievement edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_achievement']) && $isAdmin) {
    $id = $_POST['achievement_id'];
    $cadet_id = $_POST['cadet_id'];
    $achievement_name = $_POST['achievement_name'];
    $achievement_date = $_POST['achievement_date'];

    $certificate = $_POST['existing_certificate'];
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
        $file_tmp = $_FILES['certificate']['tmp_name'];
        $file_name = time() . "_" . basename($_FILES['certificate']['name']);
        $file_type = $_FILES['certificate']['type'];
        $file_size = $_FILES['certificate']['size'];

        $allowed_formats = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if ($file_size <= $max_file_size && in_array($file_type, $allowed_formats)) {
            $upload_dir = 'uploads/';
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $certificate = $file_path;
            } else {
                $notificationMessage = "Failed to upload the new certificate.";
            }
        } else {
            $notificationMessage = "File size must not exceed 5 MB and must be JPG, PNG, or JPEG.";
        }
    }

    if ($cadet_id && $achievement_name && $achievement_date && !$notificationMessage) {
        try {
            $stmt = $conn->prepare("UPDATE achievements SET cadet_id = :cadet_id, achievement_name = :achievement_name, achievement_date = :achievement_date, certificate = :certificate WHERE id = :id");
            $stmt->execute([
                'cadet_id' => $cadet_id,
                'achievement_name' => $achievement_name,
                'achievement_date' => $achievement_date,
                'certificate' => $certificate,
                'id' => $id
            ]);
            $notificationMessage = "Achievement updated successfully!";
        } catch (PDOException $e) {
            $notificationMessage = "Error: " . $e->getMessage();
        }
    } elseif (!$notificationMessage) {
        $notificationMessage = "Please fill in all required fields.";
    }
}

// Handle achievement deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_achievement']) && $isAdmin) {
    $id = $_POST['achievement_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM achievements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $notificationMessage = "Achievement deleted successfully!";
    } catch (PDOException $e) {
        $notificationMessage = "Error: " . $e->getMessage();
    }
}

// Fetch cadets from the database
try {
    $cadets_stmt = $conn->query("SELECT id, full_name FROM cadets ORDER BY full_name");
    $cadets = $cadets_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cadets = [];
    echo "Error fetching cadets: " . $e->getMessage();
}

// Fetch achievements to display
try {
    $achievements_stmt = $conn->query("SELECT a.id, a.achievement_name, a.achievement_date, c.full_name, a.certificate 
                                       FROM achievements a 
                                       JOIN cadets c ON a.cadet_id = c.id 
                                       ORDER BY a.achievement_date DESC");
    $achievements = $achievements_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $achievements = [];
    echo "Error fetching achievements: " . $e->getMessage();
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
    <title>Achievements - NCC Journey</title>

    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
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

        h2 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 20px;
        }

        #achievements {
            background-color: #f8f9fa;
            color: #333;
            padding: 60px 15px;
            text-align: center;
        }

        #achievements .section-title {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .achievement-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            background-color: #ffffff;
        }

        .achievement-card:hover {
            transform: translateY(-5px);
        }

        .card-body {
            color: #333;
        }

        .card-title {
            font-weight: 600;
        }

        .card img {
            object-fit: cover;
            max-height: 250px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 30px 15px;
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
            #achievements .section-title {
                font-size: 2rem;
            }
            .card img {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" stroke="#007bff" stroke-width="5" fill="none" stroke-dasharray="283" stroke-dashoffset="280">
                <animate attributeName="stroke-dashoffset" from="283" to="0" dur="2s" repeatCount="indefinite" />
            </circle>
            <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-size="16" font-family="Arial" fill="#007bff">NCC</text>
        </svg>
    </div>

    <!-- Navbar (Integrated Inline) -->
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
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="camp.php">Camps</a></li>
                <li class="nav-item"><a class="nav-link" href="testimonials.php">Testimonial</a></li>
                <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
            <?php if ($isLoggedIn): ?>
                <?php
                // Fetch notifications for the logged-in user
                try {
                    $stmt_notifications = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
                    $stmt_notifications->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt_notifications->execute();
                    $notifications = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

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
                ?>
                <div class="dropdown ms-3">
                    <button class="btn btn-outline-secondary rounded-pill" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger"><?= $unreadCount ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li><a class="dropdown-item" href="notifications.php">
                                    <i class="fas fa-bell"></i> <?= htmlspecialchars($notification['message']) ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($notification['created_at']) ?></small>
                                </a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item-text">No notifications</span></li>
                        <?php endif; ?>
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
        <section id="achievements">
            <div class="container">
                <h2 class="section-title"> Achievements</h2>

                <!-- Add Achievement Button (Admin Only) -->
                <?php if ($isAdmin): ?>
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                            <i class="fas fa-plus-circle"></i> Add Achievement
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Display existing achievements -->
                <div class="row">
                    <?php if (empty($achievements)): ?>
                        <p class="text-center">No achievements available yet.</p>
                    <?php else: ?>
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="col-md-4">
    <div class="card achievement-card mb-4">
        <img src="<?php echo htmlspecialchars($achievement['certificate'] ?: 'https://via.placeholder.com/400x300'); ?>" class="card-img-top" alt="Certificate Image">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($achievement['achievement_name']); ?></h5>
            <p class="card-text">Achieved by: <?php echo htmlspecialchars($achievement['full_name']); ?></p>
            <p class="card-text">Date: <?php echo date('F j, Y', strtotime($achievement['achievement_date'])); ?></p>
            <?php if ($isAdmin): ?>
                <div class="mt-2">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAchievementModal_<?php echo $achievement['id']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAchievementModal_<?php echo $achievement['id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

                            <!-- Edit Achievement Modal -->
                            <?php if ($isAdmin): ?>
                                <div class="modal fade" id="editAchievementModal_<?php echo $achievement['id']; ?>" tabindex="-1" aria-labelledby="editAchievementModalLabel_<?php echo $achievement['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editAchievementModalLabel_<?php echo $achievement['id']; ?>">Edit Achievement</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="achievement.php" enctype="multipart/form-data">
                                                    <input type="hidden" name="achievement_id" value="<?php echo $achievement['id']; ?>">
                                                    <input type="hidden" name="existing_certificate" value="<?php echo htmlspecialchars($achievement['certificate']); ?>">
                                                    <div class="mb-3">
                                                        <label for="cadet_id_<?php echo $achievement['id']; ?>" class="form-label">Cadet</label>
                                                        <select name="cadet_id" id="cadet_id_<?php echo $achievement['id']; ?>" class="form-select" required>
                                                            <?php foreach ($cadets as $cadet): ?>
                                                                <option value="<?php echo $cadet['id']; ?>" <?php echo $cadet['id'] == $achievement['cadet_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($cadet['full_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="achievement_name_<?php echo $achievement['id']; ?>" class="form-label">Achievement Name</label>
                                                        <input type="text" name="achievement_name" id="achievement_name_<?php echo $achievement['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($achievement['achievement_name']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="achievement_date_<?php echo $achievement['id']; ?>" class="form-label">Achievement Date</label>
                                                        <input type="date" name="achievement_date" id="achievement_date_<?php echo $achievement['id']; ?>" class="form-control" value="<?php echo $achievement['achievement_date']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="certificate_<?php echo $achievement['id']; ?>" class="form-label">Certificate (JPG, PNG, JPEG | Max: 5MB)</label>
                                                        <input type="file" name="certificate" id="certificate_<?php echo $achievement['id']; ?>" class="form-control" accept=".jpg,.jpeg,.png">
                                                        <small class="form-text text-muted">Current: <?php echo htmlspecialchars($achievement['certificate'] ?: 'None'); ?></small>
                                                    </div>
                                                    <button type="submit" name="edit_achievement" class="btn btn-primary">Update Achievement</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Achievement Modal -->
                                <div class="modal fade" id="deleteAchievementModal_<?php echo $achievement['id']; ?>" tabindex="-1" aria-labelledby="deleteAchievementModalLabel_<?php echo $achievement['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteAchievementModalLabel_<?php echo $achievement['id']; ?>">Delete Achievement</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="achievement.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="achievement_id" value="<?php echo $achievement['id']; ?>">
                                                    <p>Are you sure you want to delete the achievement "<?php echo htmlspecialchars($achievement['achievement_name']); ?>" by <?php echo htmlspecialchars($achievement['full_name']); ?>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_achievement" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Add Achievement Modal (Admin Only) -->
    <?php if ($isAdmin): ?>
        <div class="modal fade" id="addAchievementModal" tabindex="-1" aria-labelledby="addAchievementModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAchievementModalLabel">Add Achievement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="achievement.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="cadet_id" class="form-label">Cadet</label>
                                <select name="cadet_id" id="cadet_id" class="form-select" required>
                                    <option value="" disabled selected>Select Cadet</option>
                                    <?php foreach ($cadets as $cadet): ?>
                                        <option value="<?php echo $cadet['id']; ?>"><?php echo htmlspecialchars($cadet['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="achievement_name" class="form-label">Achievement Name</label>
                                <input type="text" name="achievement_name" id="achievement_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="achievement_date" class="form-label">Achievement Date</label>
                                <input type="date" name="achievement_date" id="achievement_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="certificate" class="form-label">Certificate (JPG, PNG, JPEG | Max: 5MB)</label>
                                <input type="file" name="certificate" id="certificate" class="form-control" accept=".jpg,.jpeg,.png" required>
                                <small class="form-text text-muted">Please upload the certificate in JPG, PNG, or JPEG format.</small>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary">Add Achievement</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="social-icons">
                <a href="#" class="footer-icon"><i class="fab fa-facebook"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            </div>
            <p>© <?php echo date('Y'); ?> NCC Journey. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // Preloader hide script
        window.addEventListener('load', function() {
            document.getElementById('preloader').classList.add('hidden');
        });

        // Notification fade-out
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