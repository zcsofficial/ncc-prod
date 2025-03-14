<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Insert into users table
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
        $role = $_POST['role'];

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        $user_id = $conn->lastInsertId();

        // Handle file upload (profile picture)
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowed) && $_FILES['profile_picture']['size'] <= 5 * 1024 * 1024) {
                $profile_picture = 'uploads/' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
            }
        }

        // Insert into cadets table
        $full_name = $_POST['full_name'];
        $dob = $_POST['dob'];
        $rank = $_POST['rank'];
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $emergency_contact_number = $_POST['emergency_contact_number'];
        $cadet_batch = !empty($_POST['cadet_batch']) ? $_POST['cadet_batch'] : null;

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
        $stmt->bindParam(':cadet_batch', $cadet_batch, PDO::PARAM_STR);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Cadet registered successfully!";
        header("Location: admin_console.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error registering cadet: " . $e->getMessage();
        header("Location: admin_console.php");
        exit();
    }
}
?>