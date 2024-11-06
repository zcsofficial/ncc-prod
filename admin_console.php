<?php
include 'db.php';
session_start();
include('acl.php');



// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];

// Fetch user details
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console - NCC Journey</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom Admin Panel Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
        }

        .navbar {
            background-color: #343a40;
        }

        .navbar .navbar-brand,
        .navbar .nav-link {
            color: #fff !important;
        }

        .navbar .nav-link:hover {
            color: #17a2b8 !important;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            background-color: #343a40;
            color: #fff;
            width: 250px;
            padding-top: 30px;
        }

        .sidebar a {
            color: #ddd;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            margin: 10px 0;
            font-size: 16px;
        }

        .sidebar a:hover {
            background-color: #17a2b8;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .card {
            border-radius: 15px;
            margin-bottom: 30px;
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

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        /* Dashboard Cards Styling */
        .dashboard-card {
            border-radius: 15px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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

        .dashboard-card .card-body a {
            color: #17a2b8;
            font-size: 16px;
        }

        /* Form Fields */
        .form-control {
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-select {
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-label {
            font-weight: bold;
        }
        .notification {
            padding: 10px;
            background-color: #28a745;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .notification.error {
            background-color: #dc3545;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">NCC Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_console.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <h3 class="text-center text-white mb-4">Admin Panel</h3>
    <a href="admin_console.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="manage_blogs.php"><i class="fas fa-blog"></i> Manage Blogs</a>
    <a href="attendance.php"><i class="fas fa-check-circle"></i> Attendance</a>
    <a href="#"><i class="fas fa-user-plus"></i> Register Cadet</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
    <?php
        if (isset($_SESSION['success_message'])) {
            echo "<div class='notification'>" . $_SESSION['success_message'] . "</div>";
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['error_message'])) {
            echo "<div class='notification error'>" . $_SESSION['error_message'] . "</div>";
            unset($_SESSION['error_message']);
        }
        ?>
        <div class="row">
            <!-- Dashboard Cards -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h4><i class="fas fa-users"></i> Manage Users</h4>
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
                        <h4><i class="fas fa-blog"></i> Manage Blogs</h4>
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
                        <h4><i class="fas fa-check-circle"></i> Attendance Module</h4>
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
                    <!-- Username (Cadet ID) -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Cadet ID (Username)</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    <!-- Role -->
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>

                    <!-- Date of Birth -->
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" required>
                    </div>

                    <!-- Rank -->
                    <div class="mb-3">
                        <label for="rank" class="form-label">Rank</label>
                        <input type="text" id="rank" name="rank" class="form-control" required>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                    </div>
                     <!-- Email -->
                     <div class="mb-3">
                        <label for="email" class="form-label">Email </label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                     <!-- Emergency Contact -->
                    <div class="mb-3">
                        <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" required>
                    </div>

                    <!-- Profile Picture -->
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-custom">Register Cadet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS and Bootstrap Bundle -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
