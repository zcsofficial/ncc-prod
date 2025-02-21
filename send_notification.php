<?php
// Include database connection file
include 'db.php';

// Function to send a notification to all users
function sendNotificationToAll($message, $conn) {
    try {
        // Get all users from the database
        $stmt = $conn->prepare("SELECT id FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Loop through each user and insert a notification
        foreach ($users as $user) {
            $userId = $user['id'];
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
        }
        echo "<div class='alert alert-success'>Notifications sent successfully to all users.</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch all events from the database
function getEvents($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM events");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error fetching events: " . $e->getMessage() . "</div>";
        return [];
    }
}

// Fetch all camps from the database
function getCamps($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM camps");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error fetching camps: " . $e->getMessage() . "</div>";
        return [];
    }
}

// Check if a notification message is being submitted
if (isset($_POST['send_notification'])) {
    $message = $_POST['message'];
    sendNotificationToAll($message, $conn);
}

// Send notification for an event or camp with a custom message
if (isset($_POST['send_event_notification']) || isset($_POST['send_camp_notification'])) {
    $message = $_POST['message'];
    $entity_id = isset($_POST['event_id']) ? $_POST['event_id'] : $_POST['camp_id'];
    $entity_type = isset($_POST['event_id']) ? 'event' : 'camp';
    
    // Send notification
    $stmt = $conn->prepare("SELECT event_name, camp_name FROM events, camps WHERE events.id = :entity_id OR camps.id = :entity_id");
    $stmt->bindParam(':entity_id', $entity_id);
    $stmt->execute();
    $entity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare notification message
    if ($entity_type == 'event') {
        $message = "Event: " . $entity['event_name'] . " - " . $message;
    } else {
        $message = "Camp: " . $entity['camp_name'] . " - " . $message;
    }
    
    sendNotificationToAll($message, $conn);
}

// Automatically send notifications when an event or camp is added
if (isset($_POST['add_event'])) {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];

    try {
        // Add the event to the database
        $stmt = $conn->prepare("INSERT INTO events (event_name, event_date) VALUES (:event_name, :event_date)");
        $stmt->bindParam(':event_name', $event_name);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->execute();

        // Send notification to all users about the new event
        $message = "New event added: $event_name on $event_date.";
        sendNotificationToAll($message, $conn);

        echo "<div class='alert alert-success'>Event added and notification sent to all users.</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

if (isset($_POST['add_camp'])) {
    $camp_name = $_POST['camp_name'];
    $camp_date = $_POST['camp_date'];

    try {
        // Add the camp to the database
        $stmt = $conn->prepare("INSERT INTO camps (camp_name, camp_date) VALUES (:camp_name, :camp_date)");
        $stmt->bindParam(':camp_name', $camp_name);
        $stmt->bindParam(':camp_date', $camp_date);
        $stmt->execute();

        // Send notification to all users about the new camp
        $message = "New camp added: $camp_name on $camp_date.";
        sendNotificationToAll($message, $conn);

        echo "<div class='alert alert-success'>Camp added and notification sent to all users.</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
        }
        .alert {
            margin-top: 20px;
        }
        h2 {
            color: #343a40;
        }
        .form-control {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2 class="text-center mb-4">Send Notifications</h2>
        
        <!-- Form to send a notification manually -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bell"></i> Send Manual Notification
            </div>
            <div class="card-body">
                <form method="POST" action="send_notification.php">
                    <div class="form-group">
                        <label for="message">Notification Message:</label>
                        <textarea name="message" id="message" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="send_notification" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Notification
                    </button>
                </form>
            </div>
        </div>

        

        <!-- Form to send notification for specific event or camp -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bell"></i> Send Notification for Event or Camp
            </div>
            <div class="card-body">
                <form method="POST" action="send_notification.php">
                    <div class="form-group">
                        <label for="message">Notification Message:</label>
                        <textarea name="message" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="event_id">Select Event:</label>
                        <select name="event_id" class="form-control">
                            <option value="">Select Event</option>
                            <?php
                                $events = getEvents($conn);
                                foreach ($events as $event) {
                                    echo "<option value='{$event['id']}'>{$event['event_name']}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="camp_id">Select Camp:</label>
                        <select name="camp_id" class="form-control">
                            <option value="">Select Camp</option>
                            <?php
                                $camps = getCamps($conn);
                                foreach ($camps as $camp) {
                                    echo "<option value='{$camp['id']}'>{$camp['camp_name']}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="send_event_notification" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Notification for Event
                    </button>

                    <button type="submit" name="send_camp_notification" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> Send Notification for Camp
                    </button>
                </form>
            </div>
        </div>
    </div>
    
</body>
</html>
