<?php
include 'db.php';
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Handle form submission
$notificationMessage = '';
$notificationType = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $message = htmlspecialchars($_POST['message']);

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        try {
            // Optionally store in database
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (:name, :email, :phone, :message)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message
            ]);

            // Send email (assuming mail server is configured)
            $to = "contact.zcsco@gmail.com";
            $subject = "New Contact Form Submission";
            $body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
            $headers = "From: $email";

            if (mail($to, $subject, $body, $headers)) {
                $notificationMessage = "Message sent successfully!";
                $notificationType = "success";
            } else {
                $notificationMessage = "Failed to send email. Message stored in database.";
                $notificationType = "warning";
            }
        } catch (PDOException $e) {
            $notificationMessage = "Error: " . $e->getMessage();
            $notificationType = "danger";
        }
    } else {
        $notificationMessage = "Please fill in all required fields with a valid email.";
        $notificationType = "danger";
    }
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
    <title>Contact Us & FAQ - NCC Journey</title>

    <!-- External CSS Libraries -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fc;
            color: #2c3e50;
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

        .section-title {
            font-size: 32px;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 40px;
        }

        .contact-form, .faq-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .contact-form h4, .faq-section h4 {
            font-weight: 500;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-submit {
            background-color: #28a745;
            color: #ffffff;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

        .accordion-button {
            font-weight: 500;
            color: #2c3e50;
        }

        .accordion-button:after {
            font-family: "Font Awesome 5 Free";
            content: "\f107"; /* Down arrow icon */
            font-weight: 900;
        }

        .accordion-button.collapsed:after {
            content: "\f105"; /* Right arrow icon */
        }

        .accordion-item {
            border: none;
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

        .notification.warning {
            background-color: #ffc107;
            color: #212529;
        }

        @media (max-width: 768px) {
            .section-title {
                font-size: 28px;
            }
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
        <div class="notification <?php echo $notificationType; ?> show" id="notification">
            <?php echo htmlspecialchars($notificationMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5">
            <!-- Contact Form Section -->
            <h2 class="section-title">Contact Us</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="contact-form">
                        <h4>We'd love to hear from you</h4>
                        <form action="contact.php" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-submit">Submit</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-section">
                <h4>Find answers to common questions</h4>
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                                <i class="fas fa-question-circle me-2"></i>What is NCC?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                The National Cadet Corps (NCC) is a youth development organization that provides students with opportunities for personal growth, discipline, and leadership skills.
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                                <i class="fas fa-question-circle me-2"></i>How can I join NCC?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You can join NCC through your school or college by applying to the NCC unit associated with it. Contact the institution's administration for more information.
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                                <i class="fas fa-question-circle me-2"></i>What activities are conducted in NCC?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                NCC conducts activities including drill practice, adventure camps, leadership training, physical training, and social service programs.
                            </div>
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

    <!-- External JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        // Display notification with fade-out
        $(document).ready(function() {
            <?php if ($notificationMessage): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>
        });
    </script>
</body>
</html>