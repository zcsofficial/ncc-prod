<?php
include 'db.php';
session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notificationMessage = '';
$notificationType = '';

// Handle filters and sorting
$rank_filter = isset($_GET['rank']) ? $_GET['rank'] : '';
$batch_filter = isset($_GET['cadet_batch']) ? $_GET['cadet_batch'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] == 'ASC' ? 'ASC' : 'DESC';

// Validate sort_by to prevent SQL injection
$valid_sort_columns = ['id', 'username', 'role', 'created_at', 'full_name', 'email'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'created_at'; // Default fallback
}

// Fetch all distinct users from the database with filters and sorting
try {
    $query = "
        SELECT DISTINCT u.id, u.username, u.role, u.created_at, c.full_name, c.email, c.`rank`, c.cadet_batch 
        FROM users u 
        LEFT JOIN cadets c ON u.id = c.user_id
        WHERE 1=1
    ";
    
    // Add filters
    $params = [];
    if (!empty($rank_filter)) {
        $query .= " AND c.`rank` = :rank";
        $params[':rank'] = $rank_filter;
    }
    if (!empty($batch_filter)) {
        $query .= " AND c.cadet_batch = :cadet_batch";
        $params[':cadet_batch'] = $batch_filter;
    }

    // Add sorting
    $query .= " ORDER BY $sort_by $sort_order";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct ranks and batches for filter dropdowns
    $rank_stmt = $conn->query("SELECT DISTINCT `rank` FROM cadets WHERE `rank` IS NOT NULL ORDER BY `rank`");
    $ranks = $rank_stmt->fetchAll(PDO::FETCH_COLUMN);

    $batch_stmt = $conn->query("SELECT DISTINCT cadet_batch FROM cadets WHERE cadet_batch IS NOT NULL ORDER BY cadet_batch");
    $batches = $batch_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $notificationMessage = "Error fetching users: " . $e->getMessage();
    $notificationType = "danger";
    $users = [];
    $ranks = [];
    $batches = [];
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
    <title>Manage Users - NCC Journey</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts for typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .container {
            margin-top: 30px;
            max-width: 1200px;
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .actions a {
            margin-right: 10px;
            text-decoration: none;
        }

        .actions .edit {
            color: #007bff;
        }

        .actions .edit:hover {
            color: #0056b3;
        }

        .actions .delete {
            color: #dc3545;
        }

        .actions .delete:hover {
            color: #b02a37;
        }

        .btn-add {
            background-color: #28a745;
            color: #fff;
            transition: all 0.2s ease-in-out;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        .btn-back {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-back:hover {
            background-color: #5a6268;
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

        footer .footer-icon {
            color: #ffffff;
            font-size: 20px;
            margin: 0 10px;
            transition: color 0.3s;
        }

        footer .footer-icon:hover {
            color: #28a745;
        }

        .filter-form {
            margin-bottom: 20px;
        }

        .sort-icon {
            cursor: pointer;
            margin-left: 5px;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 14px;
            }
            .btn-add, .btn-back {
                width: 100%;
                margin-bottom: 10px;
            }
            .filter-form select, .filter-form button {
                width: 100%;
                margin-bottom: 10px;
            }
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

    <!-- Notification -->
    <?php if (!empty($notificationMessage)): ?>
        <div class="notification <?php echo $notificationType; ?> show" id="notification">
            <?php echo htmlspecialchars($notificationMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="my-3">Manage Users</h1>
                <div>
                    <a href="register.php" class="btn btn-add me-2"><i class="fas fa-plus"></i> Add User</a>
                    <a href="admin_console.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <!-- Filter and Sort Form -->
            <form method="GET" class="filter-form d-flex flex-wrap gap-3">
                <div class="flex-grow-1">
                    <label for="rank" class="form-label">Filter by Rank</label>
                    <select name="rank" id="rank" class="form-select">
                        <option value="">All Ranks</option>
                        <?php foreach ($ranks as $rank): ?>
                            <option value="<?= htmlspecialchars($rank) ?>" <?= $rank_filter == $rank ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rank) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-grow-1">
                    <label for="cadet_batch" class="form-label">Filter by Batch</label>
                    <select name="cadet_batch" id="cadet_batch" class="form-select">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch) ?>" <?= $batch_filter == $batch ? 'selected' : '' ?>>
                                <?= htmlspecialchars($batch) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-grow-1">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="id" <?= $sort_by == 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="username" <?= $sort_by == 'username' ? 'selected' : '' ?>>Username</option>
                        <option value="full_name" <?= $sort_by == 'full_name' ? 'selected' : '' ?>>Full Name</option>
                        <option value="email" <?= $sort_by == 'email' ? 'selected' : '' ?>>Email</option>
                        <option value="role" <?= $sort_by == 'role' ? 'selected' : '' ?>>Role</option>
                        <option value="created_at" <?= $sort_by == 'created_at' ? 'selected' : '' ?>>Created At</option>
                    </select>
                </div>
                <div class="flex-grow-1">
                    <label for="sort_order" class="form-label">Order</label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
                <div class="align-self-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </form>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Rank</th>
                            <th>Batch</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['rank'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['cadet_batch'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    <td class="actions">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash-alt"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    <!-- Bootstrap JavaScript and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            <?php if (!empty($notificationMessage)): ?>
                $('#notification').fadeIn().delay(3000).fadeOut();
            <?php endif; ?>

            // Handle sort icon clicks (optional enhancement)
            $('.sort-icon').on('click', function() {
                const column = $(this).data('sort');
                const currentOrder = $('#sort_order').val();
                $('#sort_by').val(column);
                $('#sort_order').val(currentOrder === 'ASC' ? 'DESC' : 'ASC');
                $('form.filter-form').submit();
            });
        });
    </script>
</body>
</html>

<?php
$conn = null;
?>