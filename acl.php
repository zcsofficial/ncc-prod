<?php
// acl.php

function checkPermission($allowedRoles = ['user']) {
    // Start session to check the logged-in user's role
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php"); // Redirect to login if user is not logged in
        exit();
    }

    // Get the user's role from the session or set default to 'user'
    $role = $_SESSION['role'] ?? 'user';

    // If the user's role is not in the allowed roles, deny access
    if (!in_array($role, $allowedRoles)) {
        // Show an alert and redirect to home page (or any other page)
        echo "<script>alert('You do not have permission to view this page.'); window.location.href = 'index.php';</script>";
        exit();
    }
}
?>
