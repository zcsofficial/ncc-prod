<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notificationMessage = '';
$notificationType = '';

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
        $notificationMessage = "User profile not found.";
        $notificationType = "danger";
    }
} catch (PDOException $e) {
    $notificationMessage = "Error fetching profile: " . $e->getMessage();
    $notificationType = "danger";
}

// Handle form submission to update the profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notificationMessage) {
    $full_name = htmlspecialchars($_POST['full_name']);
    $dob = $_POST['dob'];
    $rank = htmlspecialchars($_POST['rank']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $contact_number = htmlspecialchars($_POST['contact_number']);
    $emergency_contact_number = htmlspecialchars($_POST['emergency_contact_number']);

    // Validate inputs
    if ($full_name && $dob && $rank && filter_var($email, FILTER_VALIDATE_EMAIL) && $contact_number && $emergency_contact_number) {
        // Handle file upload
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_formats = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB
            $file_type = $_FILES['profile_picture']['type'];
            $file_size = $_FILES['profile_picture']['size'];
            $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
            $upload_dir = 'uploads/';
            $file_path = $upload_dir . $file_name;

            if ($file_size <= $max_file_size && in_array($file_type, $allowed_formats)) {
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $file_path)) {
                    $profile_picture = $file_path;
                } else {
                    $notificationMessage = "Failed to upload profile picture.";
                    $notificationType = "danger";
                }
            } else {
                $notificationMessage = "Profile picture must be JPG, PNG, or JPEG and not exceed 5 MB.";
                $notificationType = "danger";
            }
        }

        if (!$notificationMessage) {
            // Update the user profile
            try {
                $update_query = "
                    UPDATE cadets SET 
                        full_name = :full_name, 
                        dob = :dob, 
                        rank = :rank, 
                        email = :email, 
                        contact_number = :contact_number, 
                        emergency_contact_number = :emergency_contact_number, 
                        profile_picture = :profile_picture
                    WHERE user_id = :user_id
                ";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([
                    'full_name' => $full_name,
                    'dob' => $dob,
                    'rank' => $rank,
                    'email' => $email,
                    'contact_number' => $contact_number,
                    'emergency_contact_number' => $emergency_contact_number,
                    'profile_picture' => $profile_picture,
                    'user_id' => $user_id
                ]);

                $notificationMessage = "Profile updated successfully!";
                $notificationType = "success";

                // Refresh user data after update
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $notificationMessage = "Error updating profile: " . $e->getMessage();
                $notificationType = "danger";
            }
        }
    } else {
        $notificationMessage = "Please fill in all required fields with a valid email.";
        $notificationType = "danger";
    }
}

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

$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - NCC Journey</title>

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
            background-color: #f4f7fc;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .container {
            max-width: 800px;
        }

        h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-primary:hover {
            background-color: #218838;
            border-color: #218838;
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
            h2 {
                font-size: 1.5rem;
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
                    <li class="nav-item"><a class="nav-link" href="testimonials.php">Testimonial</a></li>
                    <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                
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
        <div class="container mt-5">
            <h2>Edit Profile</h2>
            <?php if ($user): ?>
                <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="rank" class="form-label">Rank</label>
                        <input type="text" class="form-control" id="rank" name="rank" value="<?php echo htmlspecialchars($user['rank']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                        <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($user['emergency_contact_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture (JPG, PNG, JPEG | Max: 5MB)</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png">
                        <small class="form-text text-muted">Current: <?php echo htmlspecialchars($user['profile_picture'] ?: 'None'); ?></small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            <?php else: ?>
                <p class="text-danger">Unable to load profile data.</p>
            <?php endif; ?>
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

    <!-- External JS Libraries -->
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