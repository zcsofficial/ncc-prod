<?php
// Include your database connection file
include('db.php');
session_start();
include('acl.php');


//Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Check if user is logged in and has the 'admin' role
$user_id = $_SESSION['user_id'] ?? null;
$role = 'user'; // Default to 'user'

if ($user_id) {
    // Fetch user role from database
    $stmt_role = $conn->prepare("SELECT role FROM users WHERE id = :user_id");
    $stmt_role->execute(['user_id' => $user_id]);
    $user = $stmt_role->fetch(PDO::FETCH_ASSOC);
    $role = $user['role'] ?? 'user'; // Get role (admin or user)
}

// Fetch cadets to show in the table when creating a new event
$stmt2 = $conn->prepare("SELECT * FROM cadets");
$stmt2->execute();
$cadets = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Handle attendance marking (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance']) && $role === 'admin') {
    $event_id = $_POST['event_id'] ?? null;

    if ($event_id) {
        foreach ($_POST['status'] as $cadet_id => $status) {
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
    } else {
        echo "Error: Event ID is missing.";
        exit();
    }
}

// Handle new event creation (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event']) && $role === 'admin') {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $stmt6 = $conn->prepare("INSERT INTO events (event_name, event_date) VALUES (:event_name, :event_date)");
    $stmt6->execute(['event_name' => $event_name, 'event_date' => $event_date]);

    // Fetch the newly created event ID
    $event_id = $conn->lastInsertId();

    // Mark attendance for the newly created event
    if (isset($_POST['status'])) {
        foreach ($_POST['status'] as $cadet_id => $status) {
            $stmt5 = $conn->prepare("INSERT INTO attendance (cadet_id, event_id, status) VALUES (:cadet_id, :event_id, :status)");
            $stmt5->execute(['cadet_id' => $cadet_id, 'event_id' => $event_id, 'status' => $status]);
        }
    }

    header("Location: attendance.php");
    exit();
}

// Fetch events to populate the event dropdown (for both admin and user)
$stmt = $conn->prepare("SELECT * FROM events");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle attendance viewing (when an event is clicked)
$attendanceData = [];
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    $stmt7 = $conn->prepare("SELECT cadets.full_name, cadets.rank, attendance.status FROM attendance JOIN cadets ON attendance.cadet_id = cadets.id WHERE attendance.event_id = :event_id");
    $stmt7->execute(['event_id' => $event_id]);
    $attendanceData = $stmt7->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Page</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container { max-width: 1000px; margin-top: 20px; }
        .btn-primary { background-color: #007bff; }
        .attendance-table th, .attendance-table td { text-align: center; vertical-align: middle; }
        .modal .modal-content { border-radius: 8px; }
        .present { background-color: green; color: white; }
        .absent { background-color: red; color: white; }
        .excused { background-color: yellow; color: black; }
        .status-count { font-weight: bold; }
        .attendance-row { cursor: pointer; }
        .attendance-row .attendance-details { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center my-4">Attendance Management</h2>
        <!-- Navbar with Conditional Links Based on Authentication and Role -->
        <?php include('navbar.php'); ?>


        <!-- Button to Add Attendance Modal (only visible for admin) -->
        <?php if ($role === 'admin'): ?>
            <button id="add-attendance-btn" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="fas fa-plus-circle"></i> Create New Event
            </button>
        <?php endif; ?>

        <!-- Existing Events Section -->
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Existing Events</h4>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($events as $event): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($event['event_name']) . ' - ' . htmlspecialchars($event['event_date']); ?>
                        <a href="?event_id=<?php echo $event['id']; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eye"></i> View Attendance
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- View Attendance for the Selected Event -->
        <?php if (!empty($attendanceData)): ?>
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Event Attendance</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cadet Name</th>
                                <th>Rank</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceData as $attendance): ?>
                                <tr class="attendance-row">
                                    <td><?php echo htmlspecialchars($attendance['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attendance['rank']); ?></td>
                                    <td>
                                        <span class="badge 
                                        <?php echo $attendance['status'] == 'present' ? 'bg-success' : ($attendance['status'] == 'absent' ? 'bg-danger' : 'bg-warning'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($attendance['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm toggle-attendance-details">
                                            <i class="fas fa-chevron-down"></i> Details
                                        </button>
                                    </td>
                                </tr>
                                <tr class="attendance-details">
                                    <td colspan="4">
                                        <div class="attendance-info">
                                            <!-- Additional details or actions can go here -->
                                            Attendance Status: <strong><?php echo ucfirst(htmlspecialchars($attendance['status'])); ?></strong>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for Creating a New Event (only visible for admin) -->
    <?php if ($role === 'admin'): ?>
        <!-- Modal for Creating a New Event -->
        <div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createEventModalLabel">Create New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="event_name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="event_date" name="event_date" required>
                            </div>

                            <div class="mb-3">
                                <h5>Mark Attendance:</h5>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Cadet Name</th>
                                            <th>Rank</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cadets as $cadet): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cadet['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($cadet['rank']); ?></td>
                                                <td>
                                                    <select name="status[<?php echo $cadet['id']; ?>]" class="form-select">
                                                        <option value="present">Present</option>
                                                        <option value="absent">Absent</option>
                                                        <option value="excused">Excused</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle attendance details
        document.querySelectorAll('.toggle-attendance-details').forEach(button => {
            button.addEventListener('click', () => {
                const row = button.closest('.attendance-row');
                const detailsRow = row.nextElementSibling;
                detailsRow.style.display = detailsRow.style.display === 'none' || !detailsRow.style.display ? 'table-row' : 'none';
            });
        });
    </script>
</body>
</html>
