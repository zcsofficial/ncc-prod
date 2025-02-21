<?php
session_start();
require 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch all available camps from the database
try {
    $stmt = $conn->prepare("SELECT * FROM camps ORDER BY camp_date DESC");
    $stmt->execute();
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $camps = [];
    echo "Error fetching camps: " . $e->getMessage();
}

// Fetch all distinct ranks from the cadets table for eligibility checkboxes
try {
    $stmt_ranks = $conn->prepare("SELECT DISTINCT `rank` FROM cadets ORDER BY `rank`");
    $stmt_ranks->execute();
    $available_ranks = $stmt_ranks->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_ranks = [];
    echo "Error fetching ranks: " . $e->getMessage();
}

// Advanced eligibility check function
function isEligible($cadetId, $conn) {
    try {
        // Fetch cadet details
        $cadetStmt = $conn->prepare("SELECT * FROM cadets WHERE id = :cadet_id");
        $cadetStmt->execute(['cadet_id' => $cadetId]);
        $cadet = $cadetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$cadet) return false;

        // Calculate attendance percentage
        $totalEventsStmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_date <= CURDATE()");
        $totalEventsStmt->execute();
        $totalEvents = $totalEventsStmt->fetchColumn();

        $attendedStmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE cadet_id = :cadet_id AND status = 'present'");
        $attendedStmt->execute(['cadet_id' => $cadetId]);
        $attended = $attendedStmt->fetchColumn();

        $attendancePercentage = $totalEvents > 0 ? ($attended / $totalEvents) * 100 : 0;

        // Count achievements
        $achievementsStmt = $conn->prepare("SELECT COUNT(*) FROM achievements WHERE cadet_id = :cadet_id");
        $achievementsStmt->execute(['cadet_id' => $cadetId]);
        $achievements = $achievementsStmt->fetchColumn();

        // Fetch latest camp eligibility criteria
        $campStmt = $conn->prepare("SELECT eligibility FROM camps ORDER BY created_at DESC LIMIT 1");
        $campStmt->execute();
        $eligibility = $campStmt->fetchColumn();

        // Parse eligibility criteria
        $eligibilityData = [];
        if ($eligibility) {
            preg_match('/Ranks: (.*?), Achievements: (\d+), Attendance: (\d+)%/', $eligibility, $matches);
            if (count($matches) === 4) {
                $eligibilityData['ranks'] = array_map('trim', explode(',', $matches[1]));
                $eligibilityData['achievements'] = (int)$matches[2];
                $eligibilityData['attendance'] = (int)$matches[3];
            }
        }

        // Check eligibility
        $rankEligible = empty($eligibilityData['ranks']) || in_array($cadet['rank'], $eligibilityData['ranks']);
        $achievementsEligible = $achievements >= ($eligibilityData['achievements'] ?? 0);
        $attendanceEligible = $attendancePercentage >= ($eligibilityData['attendance'] ?? 0);

        return $rankEligible && $achievementsEligible && $attendanceEligible;
    } catch (PDOException $e) {
        echo "Error checking eligibility: " . $e->getMessage();
        return false;
    }
}

// Handle form submission to add a new camp (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['create_camp'])) {
    $campName = $_POST['camp_name'];
    $location = $_POST['location'];
    $campDetails = $_POST['camp_details'];
    $campDate = $_POST['camp_date'];
    $eligibilityRanks = isset($_POST['eligibility_ranks']) ? implode(', ', $_POST['eligibility_ranks']) : '';
    $eligibilityAchievements = $_POST['eligibility_achievements'];
    $eligibilityAttendance = $_POST['eligibility_attendance'];

    try {
        $stmt = $conn->prepare("INSERT INTO camps (camp_name, location, camp_details, camp_date, eligibility) VALUES (:camp_name, :location, :camp_details, :camp_date, :eligibility)");
        $eligibilityDescription = "Ranks: $eligibilityRanks, Achievements: $eligibilityAchievements, Attendance: $eligibilityAttendance%";
        $stmt->execute([
            'camp_name' => $campName,
            'location' => $location,
            'camp_details' => $campDetails,
            'camp_date' => $campDate,
            'eligibility' => $eligibilityDescription
        ]);
        header("Location: camp.php"); // Redirect to avoid form resubmission
        exit();
    } catch (PDOException $e) {
        echo "Error creating camp: " . $e->getMessage();
    }
}

// Handle camp deletion (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['delete_camp'])) {
    $campId = $_POST['camp_id'] ?? null;

    if ($campId) {
        try {
            $stmt = $conn->prepare("DELETE FROM camps WHERE id = :camp_id");
            $stmt->execute(['camp_id' => $campId]);
            header("Location: camp.php");
            exit();
        } catch (PDOException $e) {
            echo "Error deleting camp: " . $e->getMessage();
        }
    } else {
        echo "Error: Camp ID is missing.";
    }
}

// Fetch notifications for the navbar
if ($isLoggedIn) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Camps - NCC Journey</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts for typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .container {
            max-width: 1200px;
            margin-top: 20px;
        }

        .camp-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .camp-item h5 {
            color: #007bff;
        }

        .badge {
            font-size: 0.9em;
        }

        .btn-primary, .btn-success {
            background-color: #FF3A3A; /* Neon Red */
            border-color: #FF3A3A;
        }

        .btn-primary:hover, .btn-success:hover {
            background-color: #FF1A1A;
            border-color: #FF1A1A;
        }

        .form-control {
            border-radius: 25px;
        }

        .form-label {
            font-weight: bold;
        }

        .modal-content {
            border-radius: 10px;
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            text-align: center;
            flex-shrink: 0;
        }

        .footer-icon {
            font-size: 1.5rem;
            margin: 0 10px;
            color: white;
            text-decoration: none;
        }

        .footer-icon:hover {
            color: #17a2b8;
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
                <?php if ($isLoggedIn): ?>
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
            <h1 class="mb-4 text-center">Available Camps</h1>

            <!-- List of Available Camps -->
            <?php if (!empty($camps)): ?>
                <div class="row">
                    <?php foreach ($camps as $camp): ?>
                        <div class="col-md-4">
                            <div class="camp-item">
                                <h5 class="mb-1"><?php echo htmlspecialchars($camp['camp_name']); ?></h5>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($camp['location']); ?></p>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($camp['camp_date']); ?></p>
                                <p><strong>Details:</strong> <?php echo htmlspecialchars($camp['camp_details']); ?></p>
                                <p><strong>Eligibility:</strong> <?php echo htmlspecialchars($camp['eligibility']); ?></p>

                                <!-- Eligibility Badge -->
                                <?php
                                $cadetStmt = $conn->prepare("SELECT id FROM cadets WHERE user_id = :user_id");
                                $cadetStmt->execute(['user_id' => $_SESSION['user_id']]);
                                $cadet = $cadetStmt->fetch(PDO::FETCH_ASSOC);
                                $isCadetEligible = $cadet && isEligible($cadet['id'], $conn);
                                ?>
                                <?php if ($isLoggedIn && $isCadetEligible): ?>
                                    <span class="badge bg-success">Eligible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Eligible</span>
                                <?php endif; ?>

                                <!-- Delete Button (Admin Only) -->
                                <?php if ($isAdmin): ?>
                                    <button class="btn btn-sm btn-danger mt-2" data-bs-toggle="modal" data-bs-target="#deleteCampModal_<?php echo $camp['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delete Camp Modal -->
                        <?php if ($isAdmin): ?>
                            <div class="modal fade" id="deleteCampModal_<?php echo $camp['id']; ?>" tabindex="-1" aria-labelledby="deleteCampModalLabel_<?php echo $camp['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteCampModalLabel_<?php echo $camp['id']; ?>">Delete Camp: <?php echo htmlspecialchars($camp['camp_name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="camp.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="camp_id" value="<?php echo $camp['id']; ?>">
                                                <p>Are you sure you want to delete this camp?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_camp" class="btn btn-danger">Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No camps available at the moment.</p>
            <?php endif; ?>

            <!-- Admin Only: Button to Add a New Camp -->
            <?php if ($isAdmin): ?>
                <h2 class="mt-5">Create New Camp</h2>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createCampModal">
                    <i class="fas fa-plus"></i> Add Camp
                </button>
            <?php endif; ?>
        </div>

        <!-- Modal for Camp Creation -->
        <?php if ($isAdmin): ?>
            <div class="modal fade" id="createCampModal" tabindex="-1" aria-labelledby="createCampModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createCampModalLabel">Create New Camp</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="camp.php" method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="camp_name" class="form-label">Camp Name</label>
                                    <input type="text" name="camp_name" id="camp_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" name="location" id="location" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="camp_details" class="form-label">Camp Details</label>
                                    <textarea name="camp_details" id="camp_details" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="camp_date" class="form-label">Date</label>
                                    <input type="date" name="camp_date" id="camp_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Eligibility - Ranks</label>
                                    <?php if (empty($available_ranks)): ?>
                                        <p>No ranks available in the database.</p>
                                    <?php else: ?>
                                        <?php foreach ($available_ranks as $rank): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="eligibility_ranks[]" id="rank_<?php echo htmlspecialchars(str_replace(' ', '_', $rank)); ?>" value="<?php echo htmlspecialchars($rank); ?>">
                                                <label class="form-check-label" for="rank_<?php echo htmlspecialchars(str_replace(' ', '_', $rank)); ?>">
                                                    <?php echo htmlspecialchars($rank); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="eligibility_achievements" class="form-label">Minimum Achievements</label>
                                    <input type="number" name="eligibility_achievements" id="eligibility_achievements" class="form-control" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="eligibility_attendance" class="form-label">Minimum Attendance (%)</label>
                                    <input type="number" name="eligibility_attendance" id="eligibility_attendance" class="form-control" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="create_camp" class="btn btn-success">Create Camp</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date('Y'); ?> NCC Journey. All rights reserved.</p>
            <div>
                <a href="#" class="footer-icon"><i class="fab fa-facebook"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="footer-icon"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>