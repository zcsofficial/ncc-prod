<?php
session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php'; // Database connection file

// Check if the user ID is provided
if (!isset($_GET['id'])) {
    echo "User ID not specified.";
    exit();
}

$user_id = $_GET['id'];

try {
    // Delete user from users table
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Delete corresponding record from cadets table
    $stmt = $conn->prepare("DELETE FROM cadets WHERE user_id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Redirect back to manage users page with a success message
    header("Location: manage_users.php?status=deleted");
    exit();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
