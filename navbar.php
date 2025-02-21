<?php


// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Check if the user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($isLoggedIn) {
    // Fetch notifications for the logged-in user
    $userId = $_SESSION['user_id'];

    // Database connection
    include('db.php');

    // Fetch notifications from the database
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications (for example purposes, assuming a field "read" to track read/unread)
    $unreadCount = 0;
    foreach ($notifications as $notification) {
        // Check if the 'read' key exists before accessing it
        if (isset($notification['read']) && $notification['read'] == 0) {
            $unreadCount++;
        }
    }
}
?>

<!-- Navbar with Conditional Links Based on Authentication and Role -->
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
                <li class="nav-item"><a class="nav-link" href="testimonials.php">Testimonial</a></li>
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
