<?php
// Include the database connection file
require_once 'db.php';
session_start(); // Start session for feedback messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $dob = $_POST['dob'];
    $rank = $_POST['rank'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $emergency_contact_number = $_POST['emergency_contact_number'];
    $cadet_batch = !empty($_POST['cadet_batch']) ? $_POST['cadet_batch'] : null; // Handle optional cadet_batch
    
    // Profile picture upload handling
    $profile_picture = $_FILES['profile_picture']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_picture);
    
    // Default profile picture if not uploaded
    if (empty($profile_picture)) {
        $profile_picture = 'default-profile.png';  // default image name
    } else {
        // Add basic file validation
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['profile_picture']['size'] <= 5 * 1024 * 1024) {
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);
        } else {
            $_SESSION['error_message'] = "Invalid file type or size exceeds 5MB.";
            header("Location: admin_console.php");
            exit();
        }
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Insert into the users table (username, password, role)
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        // Get the last inserted user ID to link with the cadets table
        $user_id = $conn->lastInsertId();
        
        // Insert into the cadets table (including cadet_batch)
        $stmt = $conn->prepare("
            INSERT INTO cadets (user_id, full_name, dob, `rank`, email, contact_number, emergency_contact_number, profile_picture, cadet_batch) 
            VALUES (:user_id, :full_name, :dob, :rank, :email, :contact_number, :emergency_contact_number, :profile_picture, :cadet_batch)
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':rank', $rank);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
        $stmt->bindParam(':profile_picture', $profile_picture);
        $stmt->bindParam(':cadet_batch', $cadet_batch, PDO::PARAM_STR); // Bind cadet_batch
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Set success message and redirect
        $_SESSION['success_message'] = "Cadet registered successfully!";
        header("Location: admin_console.php");
        exit();
        
    } catch (PDOException $e) {
        // Roll back the transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: admin_console.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Cadet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Register New Cadet</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username (Cadet ID)</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" name="dob" id="dob" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="rank" class="form-label">Rank</label>
                <select name="rank" id="rank" class="form-control" required>
                    <option value="None">None</option>
                    <option value="ASSOCIATE NCC OFFICER (ANO)">ASSOCIATE NCC OFFICER (ANO)</option>
                    <option value="SENIOR UNDER OFFICER (SUO)">SENIOR UNDER OFFICER (SUO)</option>
                    <option value="UNDER OFFICER (UO)">UNDER OFFICER (UO)</option>
                    <option value="COMPANY SERGEANT MAJOR (CSM)">COMPANY SERGEANT MAJOR (CSM)</option>
                    <option value="COMPANY QUARTER MASTER SERGEANT (CQMS)">COMPANY QUARTER MASTER SERGEANT (CQMS)</option>
                    <option value="SERGEANT (SGT)">SERGEANT (SGT)</option>
                    <option value="CORPORAL (CPL)">CORPORAL (CPL)</option>
                    <option value="LANCE CORPORAL (L/CPL)">LANCE CORPORAL (L/CPL)</option>
                    <option value="CADET (CDT)">CADET (CDT)</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="cadet_batch" class="form-label">Cadet Batch (Optional)</label>
                <input type="text" name="cadet_batch" id="cadet_batch" class="form-control" placeholder="e.g., Batch 2023" maxlength="50">
            </div>
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture (JPG, PNG, JPEG | Max: 5MB)</label>
                <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
            <button type="submit" class="btn btn-primary">Register Cadet</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>