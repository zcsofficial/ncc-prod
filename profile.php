<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user and cadet details
try {
    $query = "
        SELECT u.username, u.role, 
               c.full_name, c.dob, c.rank, c.email, c.contact_number, 
               c.emergency_contact_number, c.profile_picture
        FROM users u 
        JOIN cadets c ON u.id = c.user_id 
        WHERE u.id = :user_id
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User profile not found.";
        exit();
    }
} catch (PDOException $e) {
    echo "Error fetching profile: " . $e->getMessage();
    exit();
}

// Fetch attendance data for the logged-in user
try {
    $attendance_query = "
        SELECT e.event_name, e.event_date, a.status
        FROM attendance a 
        JOIN events e ON a.event_id = e.id 
        WHERE a.cadet_id IN (SELECT id FROM cadets WHERE user_id = :user_id)
        ORDER BY e.event_date DESC
    ";
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate attendance percentage
    $total_events = count($attendance_result);
    $present_events = 0;
    foreach ($attendance_result as $attendance) {
        if ($attendance['status'] == 'present') {
            $present_events++;
        }
    }
    $attendance_percentage = $total_events > 0 ? ($present_events / $total_events) * 100 : 0;
} catch (PDOException $e) {
    $attendance_result = [];
    $attendance_percentage = 0;
    echo "Error fetching attendance: " . $e->getMessage();
}

// Fetch achievements data for the logged-in user
try {
    $achievements_query = "
        SELECT achievement_name, achievement_date
        FROM achievements 
        WHERE cadet_id IN (SELECT id FROM cadets WHERE user_id = :user_id)
        ORDER BY achievement_date DESC
    ";
    $achievements_stmt = $conn->prepare($achievements_query);
    $achievements_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $achievements_stmt->execute();
    $achievements_result = $achievements_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $achievements_result = [];
    echo "Error fetching achievements: " . $e->getMessage();
}

// Check if the user is logged in and admin status
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch notifications for the logged-in user
if ($isLoggedIn) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NCC Journey</title>

    <!-- External Libraries for Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: #f4f7fc;
            font-family: 'Poppins', sans-serif;
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

        .profile-header {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .profile-header h3 {
            font-weight: 600;
            color: #2c3e50;
        }

        .badge-role {
            background-color: #4e73df;
            color: white;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .profile-detail .row p {
            font-size: 16px;
            color: #2c3e50;
        }

        .card-table {
            margin-top: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table td {
            font-size: 14px;
        }

        .btn-edit-profile {
            background-color: #28a745;
            color: white;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-edit-profile:hover {
            background-color: #218838;
        }

        .achievements-list {
            margin-top: 20px;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .achievements-list h5 {
            font-weight: 600;
            color: #2c3e50;
        }

        .achievements-list ul {
            list-style: none;
            padding: 0;
        }

        .achievements-list ul li {
            font-size: 16px;
            color: #2c3e50;
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
            .profile-header {
                padding: 20px;
            }

            .card-table {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5">
            <div class="row">
                <!-- Profile Header -->
                <div class="col-lg-4 col-md-5">
                    <div class="profile-header text-center">
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default_profile.jpg'); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" width="120" height="120">
                        <h3 class="card-title"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p class="text-muted">Rank: <?php echo htmlspecialchars($user['rank']); ?></p>
                        <span class="badge badge-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        <a href="edit_profile.php" class="btn btn-edit-profile mt-3"><i class="fas fa-edit"></i> Edit Profile</a>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="col-lg-8 col-md-7">
                    <div class="card profile-detail p-4">
                        <h4 class="mb-4">Profile Information</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['dob']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                            </div>
                        </div>

                        <!-- Attendance History -->
                        <div class="card card-table p-3">
                            <h5 class="mb-4">Attendance History</h5>
                            <p><strong>Attendance Percentage:</strong> <?php echo round($attendance_percentage, 2); ?>%</p>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Event Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($attendance_result): ?>
                                        <?php foreach ($attendance_result as $attendance): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attendance['event_name']); ?></td>
                                                <td><?php echo htmlspecialchars($attendance['event_date']); ?></td>
                                                <td class="<?php echo ($attendance['status'] == 'present') ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ucfirst($attendance['status']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">No attendance data found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Achievements -->
                        <div class="achievements-list">
                            <h5>Recent Achievements</h5>
                            <?php if ($achievements_result): ?>
                                <ul>
                                    <?php foreach ($achievements_result as $achievement): ?>
                                        <li><?php echo htmlspecialchars($achievement['achievement_name']); ?> (<?php echo htmlspecialchars($achievement['achievement_date']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No recent achievements found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer>
        <div class="social-icons">
            <a href="#" class="footer-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            <a href="#" class="footer-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p>© <?php echo date('Y'); ?> NCC Journey. All Rights Reserved.</p>
    </footer>

    <!-- External Libraries JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>