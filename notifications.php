<?php
session_start();
require_once 'db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user notifications
try {
    $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read
    $updateQuery = "UPDATE notifications SET `read` = 1 WHERE user_id = :user_id AND `read` = 0";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':user_id', $userId);
    $updateStmt->execute();
} catch (PDOException $e) {
    $notificationMessage = "Error fetching notifications: " . $e->getMessage();
    $notificationType = "danger";
}

// Fetch notifications for the navbar (to reflect updated read status)
try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $navbarNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadCount = 0;
    foreach ($navbarNotifications as $notification) {
        if (isset($notification['read']) && $notification['read'] == 0) {
            $unreadCount++;
        }
    }
} catch (PDOException $e) {
    $navbarNotifications = [];
    $unreadCount = 0;
}

$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - NCC Journey</title>

    <!-- External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .notification-card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-time {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .empty-message {
            text-align: center;
            margin-top: 30px;
            font-size: 1.2rem;
            color: #6c757d;
        }

        h2 {
            font-size: 2rem;
            font-weight: 500;
            color: #2c3e50;
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
                        <?php foreach ($navbarNotifications as $notification): ?>
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
            </div>
        </div>
    </nav>

    <!-- Notification -->
    <?php if (isset($notificationMessage)): ?>
        <div class="notification <?php echo $notificationType; ?> show" id="notification">
            <?php echo htmlspecialchars($notificationMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <h2 class="text-center mb-4">Notifications</h2>
                    <div class="list-group">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item notification-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-bell text-danger me-3"></i>
                                            <span><?php echo htmlspecialchars($notification['message']); ?></span>
                                        </div>
                                        <small class="notification-time">
                                            <?php echo date("F j, Y, g:i a", strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-message alert alert-info">You have no notifications at the moment.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- External JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        // Notification fade-out
        $(document).ready(function() {
            <?php if (isset($notificationMessage)): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>
        });
    </script>
</body>
</html>