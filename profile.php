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

// Check if user data exists
if (!$user) {
    echo "User profile not found.";
    exit();
}

// Fetch attendance data for the logged-in user
$attendance_query = "
    SELECT e.event_name, e.event_date, a.status 
    FROM attendance a 
    JOIN events e ON a.event_id = e.id 
    WHERE a.cadet_id = (SELECT id FROM cadets WHERE user_id = :user_id)
    ORDER BY e.event_date DESC
";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NCC Journey</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom Styles for Profile Page */
        body {
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
        }
        
        .profile-card {
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .profile-header {
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        .badge-role {
            background-color: #17a2b8;
            color: white;
            font-size: 14px;
        }
        .card-body p {
            font-size: 16px;
            line-height: 1.6;
        }
        .btn-edit-profile {
            background-color: #28a745;
            color: white;
        }
        .btn-edit-profile:hover {
            background-color: #218838;
        }
        .post-title {
            font-weight: bold;
        }
        .post-meta {
            color: #6c757d;
            font-size: 14px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>


<div class="container mt-5">
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card profile-card p-3">
                <div class="card-body text-center profile-header">
                    <?php if ($user['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-img">
                    <?php else: ?>
                        <img src="default-profile.png" alt="Default Profile" class="profile-img">
                    <?php endif; ?>
                    <h3 class="card-title"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="text-muted">Rank: <?php echo htmlspecialchars($user['rank']); ?></p>
                    <span class="badge badge-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    <hr>
                    <a href="edit_profile.php" class="btn btn-edit-profile btn-sm"><i class="fas fa-edit"></i> Edit Profile</a>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-md-8">
            <div class="card profile-card p-4">
                <h4 class="card-title">Profile Details</h4>
                <hr>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><i class="fas fa-user"></i> <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><i class="fas fa-calendar-alt"></i> <strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['dob']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-phone"></i> <strong>Contact Number:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                    </div>
                </div>

                <h4 class="mt-5">Attendance History</h4>
                <table class="table table-bordered mt-3">
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
                            <tr><td colspan="3">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
