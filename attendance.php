<?php
// Include your database connection file
include('db.php');
session_start();
include('acl.php'); // Assuming this handles access control, ensure it exists

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch user role if logged in
$user_id = $_SESSION['user_id'] ?? null;
$role = 'user'; // Default to 'user'

if ($user_id) {
    try {
        $stmt_role = $conn->prepare("SELECT role FROM users WHERE id = :user_id");
        $stmt_role->execute(['user_id' => $user_id]);
        $user = $stmt_role->fetch(PDO::FETCH_ASSOC);
        $role = $user['role'] ?? 'user'; // Get role (admin or user)
    } catch (PDOException $e) {
        echo "Error fetching user role: " . $e->getMessage();
        exit();
    }
}

// Fetch cadets to show in the table when creating/marking attendance
try {
    $stmt2 = $conn->prepare("SELECT * FROM cadets");
    $stmt2->execute();
    $cadets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cadets = [];
    echo "Error fetching cadets: " . $e->getMessage();
}

// Handle attendance marking (for both new and existing events, only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance']) && $role === 'admin') {
    $event_id = $_POST['event_id'] ?? null;

    if ($event_id) {
        try {
            foreach ($_POST['status'] as $cadet_id => $status) {
                $status = $status ?: 'absent'; // Default to 'absent' if empty

                $stmt3 = $conn->prepare("SELECT * FROM attendance WHERE cadet_id = :cadet_id AND event_id = :event_id");
                $stmt3->execute(['cadet_id' => $cadet_id, 'event_id' => $event_id]);
                $attendance = $stmt3->fetch(PDO::FETCH_ASSOC);

                if ($attendance) {
                    $stmt4 = $conn->prepare("UPDATE attendance SET status = :status WHERE cadet_id = :cadet_id AND event_id = :event_id");
                    $stmt4->execute(['status' => $status, 'cadet_id' => $cadet_id, 'event_id' => $event_id]);
                } else {
                    $stmt5 = $conn->prepare("INSERT INTO attendance (cadet_id, event_id, status) VALUES (:cadet_id, :event_id, :status)");
                    $stmt5->execute(['cadet_id' => $cadet_id, 'event_id' => $event_id, 'status' => $status]);
                }
            }
            header("Location: attendance.php?event_id=$event_id");
            exit();
        } catch (PDOException $e) {
            echo "Error marking attendance: " . $e->getMessage();
        }
    } else {
        echo "Error: Event ID is missing.";
        exit();
    }
}

// Handle new event creation (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event']) && $role === 'admin') {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];

    try {
        $stmt6 = $conn->prepare("INSERT INTO events (event_name, event_date) VALUES (:event_name, :event_date)");
        $stmt6->execute(['event_name' => $event_name, 'event_date' => $event_date]);

        $event_id = $conn->lastInsertId();

        if (isset($_POST['status'])) {
            foreach ($_POST['status'] as $cadet_id => $status) {
                $status = $status ?: 'absent'; // Default to 'absent' if empty
                $stmt5 = $conn->prepare("INSERT INTO attendance (cadet_id, event_id, status) VALUES (:cadet_id, :event_id, :status)");
                $stmt5->execute(['cadet_id' => $cadet_id, 'event_id' => $event_id, 'status' => $status]);
            }
        }

        header("Location: attendance.php");
        exit();
    } catch (PDOException $e) {
        echo "Error creating event: " . $e->getMessage();
    }
}

// Handle event deletion (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event']) && $role === 'admin') {
    $event_id = $_POST['event_id'] ?? null;

    if ($event_id) {
        try {
            // Delete associated attendance records first
            $stmt_delete_attendance = $conn->prepare("DELETE FROM attendance WHERE event_id = :event_id");
            $stmt_delete_attendance->execute(['event_id' => $event_id]);

            // Delete the event
            $stmt_delete_event = $conn->prepare("DELETE FROM events WHERE id = :event_id");
            $stmt_delete_event->execute(['event_id' => $event_id]);

            header("Location: attendance.php");
            exit();
        } catch (PDOException $e) {
            echo "Error deleting event: " . $e->getMessage();
        }
    } else {
        echo "Error: Event ID is missing.";
        exit();
    }
}

// Handle attendance deletion (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_attendance']) && $role === 'admin') {
    $event_id = $_POST['event_id'] ?? null;
    $cadet_id = $_POST['cadet_id'] ?? null;

    if ($event_id && $cadet_id) {
        try {
            $stmt_delete = $conn->prepare("DELETE FROM attendance WHERE event_id = :event_id AND cadet_id = :cadet_id");
            $stmt_delete->execute(['event_id' => $event_id, 'cadet_id' => $cadet_id]);
            header("Location: attendance.php?event_id=$event_id");
            exit();
        } catch (PDOException $e) {
            echo "Error deleting attendance: " . $e->getMessage();
        }
    } else {
        echo "Error: Event ID or Cadet ID is missing.";
        exit();
    }
}

// Fetch events to populate the event list (for both admin and user)
try {
    $stmt = $conn->prepare("SELECT * FROM events ORDER BY event_date DESC");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    echo "Error fetching events: " . $e->getMessage();
}

// Handle attendance viewing (when an event is clicked)
$attendanceData = [];
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    try {
        $stmt7 = $conn->prepare("SELECT cadets.id AS cadet_id, cadets.full_name, cadets.rank, attendance.status 
                                 FROM attendance 
                                 JOIN cadets ON attendance.cadet_id = cadets.id 
                                 WHERE attendance.event_id = :event_id");
        $stmt7->execute(['event_id' => $event_id]);
        $attendanceData = $stmt7->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching attendance data: " . $e->getMessage();
    }
}

// Fetch notifications for the navbar
if ($isLoggedIn) {
    try {
        $stmt_notifications = $conn->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
        $stmt_notifications->bindParam(':user_id', $user_id);
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
    <title>Attendance Management - NCC Journey</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: #f1f5f8;
            font-family: 'Roboto', sans-serif;
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

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table th {
            background-color: #007bff;
            color: white;
        }

        .attendance-table th, .attendance-table td {
            padding: 1rem;
        }

        .modal .modal-content {
            border-radius: 10px;
        }

        .modal-header, .card-header {
            background-color: #007bff;
            color: white;
        }

        .card-body {
            background-color: white;
            border-radius: 10px;
        }

        .card-footer {
            text-align: center;
        }

        .badge {
            font-size: 1rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 5px;
        }

        .badge.present {
            background-color: #28a745;
            color: white;
        }

        .badge.absent {
            background-color: #dc3545;
            color: white;
        }

        .badge.excused {
            background-color: #ffc107;
            color: black;
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
        <div class="container">
            <h2 class="text-center my-4">Attendance Management</h2>

            <!-- Button to Add Attendance Modal (only visible for admin) -->
            <?php if ($role === 'admin'): ?>
                <button id="add-attendance-btn" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
                    <i class="fas fa-plus-circle"></i> Create New Event
                </button>
            <?php endif; ?>

            <!-- Existing Events Section -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4 class="mb-0">Existing Events</h4>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($events)): ?>
                        <li class="list-group-item text-center">No events available.</li>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($event['event_name']) . ' - ' . htmlspecialchars($event['event_date']); ?>
                                <div>
                                    <a href="?event_id=<?php echo $event['id']; ?>" class="btn btn-secondary btn-sm me-2">
                                        <i class="fas fa-eye"></i> View Attendance
                                    </a>
                                    <?php if ($role === 'admin'): ?>
                                        <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#markAttendanceModal_<?php echo $event['id']; ?>">
                                            <i class="fas fa-check"></i> Mark Attendance
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteEventModal_<?php echo $event['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- View Attendance for the Selected Event -->
            <?php if (!empty($attendanceData)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Event Attendance</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered attendance-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Rank</th>
                                    <th>Status</th>
                                    <?php if ($role === 'admin'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceData as $attendance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attendance['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attendance['rank']); ?></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($attendance['status']); ?>">
                                                <?php echo ucfirst($attendance['status']); ?>
                                            </span>
                                        </td>
                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAttendanceModal_<?php echo $attendance['cadet_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAttendanceModal_<?php echo $attendance['cadet_id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Edit and Delete Attendance Modals for Each Record (Admin Only) -->
                <?php if ($role === 'admin'): ?>
                    <?php foreach ($attendanceData as $attendance): ?>
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editAttendanceModal_<?php echo $attendance['cadet_id']; ?>" tabindex="-1" aria-labelledby="editAttendanceModalLabel_<?php echo $attendance['cadet_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editAttendanceModalLabel_<?php echo $attendance['cadet_id']; ?>">Edit Attendance for <?php echo htmlspecialchars($attendance['full_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="attendance.php" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                            <input type="hidden" name="cadet_id" value="<?php echo $attendance['cadet_id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-control" name="status[<?php echo $attendance['cadet_id']; ?>]">
                                                    <option value="present" <?php echo $attendance['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="absent" <?php echo $attendance['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                    <option value="excused" <?php echo $attendance['status'] === 'excused' ? 'selected' : ''; ?>>Excused</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="mark_attendance" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Attendance Modal -->
                        <div class="modal fade" id="deleteAttendanceModal_<?php echo $attendance['cadet_id']; ?>" tabindex="-1" aria-labelledby="deleteAttendanceModalLabel_<?php echo $attendance['cadet_id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteAttendanceModalLabel_<?php echo $attendance['cadet_id']; ?>">Delete Attendance for <?php echo htmlspecialchars($attendance['full_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="attendance.php" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                            <input type="hidden" name="cadet_id" value="<?php echo $attendance['cadet_id']; ?>">
                                            <p>Are you sure you want to delete this attendance record?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="delete_attendance" class="btn btn-danger">Delete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Modal for Event Creation -->
            <div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createEventModalLabel">Create New Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="attendance.php" method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="event_name" class="form-label">Event Name</label>
                                    <input type="text" class="form-control" name="event_name" id="event_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="event_date" class="form-label">Event Date</label>
                                    <input type="date" class="form-control" name="event_date" id="event_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Mark Attendance for Cadets</label>
                                    <?php if (empty($cadets)): ?>
                                        <p>No cadets available.</p>
                                    <?php else: ?>
                                        <?php foreach ($cadets as $cadet): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="status[<?php echo $cadet['id']; ?>]" id="status_<?php echo $cadet['id']; ?>" value="present">
                                                <label class="form-check-label" for="status_<?php echo $cadet['id']; ?>">
                                                    <?php echo htmlspecialchars($cadet['full_name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mark Attendance Modals for Existing Events (Admin Only) -->
            <?php if ($role === 'admin'): ?>
                <?php foreach ($events as $event): ?>
                    <div class="modal fade" id="markAttendanceModal_<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="markAttendanceModalLabel_<?php echo $event['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="markAttendanceModalLabel_<?php echo $event['id']; ?>">Mark Attendance for <?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="attendance.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Mark Attendance for Cadets</label>
                                            <?php if (empty($cadets)): ?>
                                                <p>No cadets available.</p>
                                            <?php else: ?>
                                                <?php foreach ($cadets as $cadet): ?>
                                                    <div class="form-check">
                                                        <?php
                                                        // Check current status for this cadet and event
                                                        $stmt_current = $conn->prepare("SELECT status FROM attendance WHERE cadet_id = :cadet_id AND event_id = :event_id");
                                                        $stmt_current->execute(['cadet_id' => $cadet['id'], 'event_id' => $event['id']]);
                                                        $current_status = $stmt_current->fetchColumn();
                                                        ?>
                                                        <input class="form-check-input" type="checkbox" name="status[<?php echo $cadet['id']; ?>]" id="status_<?php echo $cadet['id'] . '_' . $event['id']; ?>" value="present" <?php echo $current_status === 'present' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="status_<?php echo $cadet['id'] . '_' . $event['id']; ?>">
                                                            <?php echo htmlspecialchars($cadet['full_name']); ?> (Current: <?php echo $current_status ?: 'Not Marked'; ?>)
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="mark_attendance" class="btn btn-primary">Update Attendance</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Event Modal -->
                    <div class="modal fade" id="deleteEventModal_<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="deleteEventModalLabel_<?php echo $event['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteEventModalLabel_<?php echo $event['id']; ?>">Delete Event: <?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="attendance.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <p>Are you sure you want to delete this event and all associated attendance records?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="delete_event" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>