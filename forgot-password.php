<?php
session_start();
require_once 'db.php';

// Check if the form is submitted
$notificationMessage = '';
$notificationType = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);

    try {
        // Fetch user details based on username
        $stmt = $conn->prepare("
            SELECT u.id, c.email 
            FROM users u 
            JOIN cadets c ON u.id = c.user_id 
            WHERE u.username = :username
        ");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $email = $user['email'];

            // Generate reset link with email and username
            $resetLink = "http://nccv2.redexploit.online/reset-password.php?email=" . urlencode($email) . "&username=" . urlencode($username); // Replace with your actual domain

            // Email template
            $to = $email;
            $subject = "Password Reset Request - NCC Journey";
            $headers = "From: no-reply@nationalcadetcorps.com\r\n"; // Replace with your email
            $headers .= "Reply-To: no-reply@nationalcadetcorps.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $body = "Dear " . $username . ",\n\n";
            $body .= "We received a request to reset your password for your NCC Journey account.\n\n";
            $body .= "Please click the link below to reset your password:\n";
            $body .= "$resetLink\n\n";
            $body .= "This link is valid for the next 1 hour. If you did not request a password reset, please ignore this email or contact support if you suspect unauthorized access.\n\n";
            $body .= "Best regards,\n";
            $body .= "NCC Journey Team\n";

            // Send reset email using mail()
            if (mail($to, $subject, $body, $headers)) {
                $notificationMessage = "A password reset link has been sent to your registered email.";
                $notificationType = "success";
            } else {
                $notificationMessage = "Failed to send the reset email. Please check your server configuration or try again later.";
                $notificationType = "danger";
            }
        } else {
            $notificationMessage = "No user found with that username.";
            $notificationType = "danger";
        }
    } catch (PDOException $e) {
        $notificationMessage = "Error: " . $e->getMessage();
        $notificationType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NCC Journey</title>

    <!-- External CSS & Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .forgot-container {
            max-width: 420px;
            width: 100%;
            margin: 20px;
            padding: 30px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .forgot-container h2 {
            text-align: center;
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 12px;
        }

        .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }

        .form-label {
            font-weight: 500;
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

        .back-to-login {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }

        .back-to-login a:hover {
            text-decoration: underline;
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

        @media (max-width: 576px) {
            .forgot-container {
                padding: 20px;
            }
            .forgot-container h2 {
                font-size: 1.6rem;
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
                    <li class="nav-item"><a class="nav-link" href="camp.php">Camps</a></li>
                    <li class="nav-item"><a class="nav-link" href="testimonials.php">Testimonial</a></li>
                    <li class="nav-item"><a class="nav-link" href="achievement.php">Achievements</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <a href="login.php" class="btn btn-primary ms-3 rounded-pill">Login</a>
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
        <div class="forgot-container">
            <h2><i class="fas fa-key icon"></i> Forgot Password</h2>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username (Cadet ID)</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                    <small class="form-text text-muted">Enter your username to receive a password reset link.</small>
                </div>
                <button type="submit" class="btn btn-submit">Send Reset Link</button>
                <div class="back-to-login">
                    <a href="login.php">Back to Login</a>
                </div>
            </form>
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
            <?php if ($notificationMessage): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>
        });
    </script>
</body>
</html>