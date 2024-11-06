<?php
// Include the database connection file
require_once 'db.php';

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
    
    // Profile picture upload handling
    $profile_picture = $_FILES['profile_picture']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_picture);
    
    // Default profile picture if not uploaded
    if (empty($profile_picture)) {
        $profile_picture = 'default-profile.png';  // default image name
    } else {
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);
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
        
        // Insert into the cadets table (user_id, full_name, dob, rank, email, contact number, emergency contact number, profile_picture)
        $stmt = $conn->prepare("INSERT INTO cadets (user_id, full_name, dob, rank, email, contact_number, emergency_contact_number, profile_picture) 
            VALUES (:user_id, :full_name, :dob, :rank, :email, :contact_number, :emergency_contact_number, :profile_picture)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':rank', $rank);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
        $stmt->bindParam(':profile_picture', $profile_picture);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Redirect to a success page or show a success message
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Cadet registered successfully!";
            header("Location: admin_console.php");
        } else {
            $_SESSION['error_message'] = "There was an error registering the cadet. Please try again.";
        }
        
    } catch (PDOException $e) {
        // If there is an error, roll back the transaction
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>

