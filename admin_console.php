<?php
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notificationMessage = '';
$notificationType = '';

// Fetch user details
try {
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $notificationMessage = "User not found.";
        $notificationType = "danger";
    }
} catch (PDOException $e) {
    $notificationMessage = "Error fetching user: " . $e->getMessage();
    $notificationType = "danger";
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
    <title>Admin Console - NCC Journey</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
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

        .main-content-wrapper {
            flex: 1 0 auto;
            display: flex;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            background-color: #343a40;
            color: #fff;
            width: 250px;
            padding-top: 80px; /* Space for navbar */
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar a {
            color: #ddd;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            font-size: 16px;
            border-bottom: 1px solid #444;
        }

        .sidebar a:hover {
            background-color: #17a2b8;
        }

        .sidebar a.active {
            background-color: #17a2b8;
        }

        .sidebar h3 {
            color: #fff;
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: 100%;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #17a2b8;
            color: #fff;
        }

        .card-body {
            background-color: #fff;
        }

        .btn-custom {
            background-color: #28a745;
            color: white;
        }

        .btn-custom:hover {
            background-color: #218838;
        }

        .dashboard-card {
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .dashboard-card .card-header {
            background-color: #17a2b8;
            color: #fff;
            font-weight: bold;
        }

        .dashboard-card .card-body {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-label {
            font-weight: bold;
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
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar (Integrated Inline) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm rounded-pill mt-3 mx-auto" style="max-width: 95%; padding: 10px 30px; position: fixed; top: 0; width: 100%; z-index: 1000;">
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

    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-center mb-4">Admin Panel</h3>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_blogs.php"><i class="fas fa-blog"></i> Manage Blogs</a>
        <a href="attendance.php"><i class="fas fa-check-circle"></i> Attendance</a>
        <a href="register_cadet.php"><i class="fas fa-user-plus"></i> Register Cadet</a>
        <a href="achievement.php"><i class="fas fa-trophy"></i> Add Achievements</a>
        <a href="send_notification.php"><i class="fas fa-comment"></i> Send Notification</a>
        <a href="camp.php"><i class="fas fa-campground"></i> Add Camps</a>
    </div>

    <!-- Main Content -->
    <div class="main-content-wrapper">
        <div class="main-content">
            <div class="container">
                <!-- Notification -->
                <?php if ($notificationMessage): ?>
                    <div class="notification <?php echo $notificationType; ?> show" id="notification">
                        <?php echo htmlspecialchars($notificationMessage); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="notification success show" id="notification">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="notification danger show" id="notification">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Dashboard Cards -->
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5><i class="fas fa-users"></i> Manage Users</h5>
                            </div>
                            <div class="card-body">
                                <p>Manage users, view their roles, and make edits.</p>
                                <a href="manage_users.php" class="btn btn-custom btn-sm"><i class="fas fa-edit"></i> Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5><i class="fas fa-blog"></i> Manage Blogs</h5>
                            </div>
                            <div class="card-body">
                                <p>View and manage blog posts by users.</p>
                                <a href="manage_blogs.php" class="btn btn-custom btn-sm"><i class="fas fa-edit"></i> Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5><i class="fas fa-check-circle"></i> Attendance</h5>
                            </div>
                            <div class="card-body">
                                <p>Mark attendance for cadets during events.</p>
                                <a href="attendance.php" class="btn btn-custom btn-sm"><i class="fas fa-edit"></i> Take Attendance</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cadet Registration -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4><i class="fas fa-user-plus"></i> Register New Cadet</h4>
                    </div>
                    <div class="card-body">
                        <form action="register_cadet.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Cadet ID (Username)</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select id="role" name="role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="rank" class="form-label">Rank</label>
                                <select name="rank" id="rank" class="form-control" required>
                                    <option value="None">None</option>
                                    <option value="ASSOCIATE NCC OFFICER (ANO)">ASSOCIATE NCC OFFICER (ANO)</option>
                                    <option value="SENIOR UNDER OFFICER (SUO)">SENIOR UNDER OFFICER (SUO)</option>
                                    <option value="UNDER OFFICER (UO)">UNDER OFFICER (UO)</option>
                                    <option value="COMPANY SERGEANT MAJOR (CSM)">COMPANY SERGEANT MAJOR (CSM)</option>
                                    <option value="COMPANY QUARTER MASTER SERGEANT (CQMS)">COMPANY QUARTER MASTER SERGEANT (CQMS)</option>
                                    <option value="SERGEANT (SGT)">SERGEANT (SGT)</option>
                                    <option value="CORPORAL (CPL)">CORPORAL (CPL)</option>
                                    <option value="LANCE CORPORAL (L/CPL)">LANCE CORPORAL (L/CPL)</option>
                                    <option value="CADET (CDT)">CADET (CDT)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                                <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture (JPG, PNG, JPEG | Max: 5MB)</label>
                                <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png">
                            </div>
                            <button type="submit" class="btn btn-custom">Register Cadet</button>
                        </form>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        // Notification fade-out
        $(document).ready(function() {
            <?php if ($notificationMessage || isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>
        });
    </script>
</body>
</html>