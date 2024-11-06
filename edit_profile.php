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
           c.full_name, c.dob, c.rank, c.email, c.contact_number, c.emergency_contact_number, c.profile_picture 
    FROM users u 
    JOIN cadets c ON u.id = c.user_id 
    WHERE u.id = :user_id
";
$stmt = $conn->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission to update the profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $rank = $_POST['rank'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $emergency_contact_number = $_POST['emergency_contact_number'];

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profile_picture = 'uploads/' . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    } else {
        $profile_picture = $user['profile_picture']; // keep existing picture if not uploaded
    }

    // Update the user profile
    $update_query = "
        UPDATE cadets SET 
            full_name = :full_name, 
            dob = :dob, 
            rank = :rank, 
            email = :email, 
            contact_number = :contact_number, 
            emergency_contact_number = :emergency_contact_number, 
            profile_picture = :profile_picture
        WHERE user_id = :user_id
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindValue(':full_name', $full_name);
    $update_stmt->bindValue(':dob', $dob);
    $update_stmt->bindValue(':rank', $rank);
    $update_stmt->bindValue(':email', $email);
    $update_stmt->bindValue(':contact_number', $contact_number);
    $update_stmt->bindValue(':emergency_contact_number', $emergency_contact_number);
    $update_stmt->bindValue(':profile_picture', $profile_picture);
    $update_stmt->bindValue(':user_id', $user_id);
    $update_stmt->execute();

    // Redirect to the profile page after update
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - NCC Journey</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navbar with Conditional Links Based on Authentication and Role -->
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
                <li class="nav-item"><a class="nav-link" href="enrollment.php">Enrollment</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2>Edit Profile</h2>
    <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth</label>
            <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="rank" class="form-label">Rank</label>
            <input type="text" class="form-control" id="rank" name="rank" value="<?php echo htmlspecialchars($user['rank']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="contact_number" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
            <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($user['emergency_contact_number']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="profile_picture" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" id="profile_picture" name="profile_picture">
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
