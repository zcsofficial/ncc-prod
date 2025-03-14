<?php
session_start();
require 'db.php';

// Check if the user is logged in and has admin privileges
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if (!$isLoggedIn || !$isAdmin) {
    header("Location: login.php");
    exit();
}

// Fetch all contact messages from the database
try {
    $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    echo "Error fetching contact messages: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Requests - NCC Journey</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts for typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .container {
            max-width: 1200px;
            margin-top: 20px;
        }

        .message-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .message-item h5 {
            color: #007bff;
        }

        .btn-primary {
            background-color: #FF3A3A; /* Neon Red */
            border-color: #FF3A3A;
        }

        .btn-primary:hover {
            background-color: #FF1A1A;
            border-color: #FF1A1A;
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5">
            <h1 class="mb-4 text-center">Contact Requests</h1>

            <!-- List of Contact Messages -->
            <?php if (!empty($messages)): ?>
                <div class="row">
                    <?php foreach ($messages as $message): ?>
                        <div class="col-md-4">
                            <div class="message-item">
                                <h5 class="mb-1"><?php echo htmlspecialchars($message['name']); ?></h5>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($message['phone'] ?: 'N/A'); ?></p>
                                <p><strong>Message:</strong> <?php echo htmlspecialchars($message['message']); ?></p>
                                <p><strong>Received:</strong> <?php echo htmlspecialchars($message['created_at']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No contact requests at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>