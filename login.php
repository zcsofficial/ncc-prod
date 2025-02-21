<?php
session_start();
require_once 'db.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user details based on username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Store user details in session and redirect to dashboard
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NCC Cadets</title>
    
    <!-- External CSS & Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 20px;
            padding: 30px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .login-container h2 {
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
        .btn-login {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #0056b3;
        }
        .form-label {
            font-weight: 500;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 0.9rem;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .icon {
            font-size: 1.2rem;
            color: #007bff;
        }

        /* Media Queries for smaller screens */
        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
            }
            .login-container h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <a href="index.php" class="btn btn-light mb-3">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <h2><i class="fas fa-user-lock icon"></i> Login to NCC Cadets</h2>
        <form method="POST">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error; ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username (Cadet ID)</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
