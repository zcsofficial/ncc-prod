<?php
include 'db.php';
session_start();

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $author_id = $_SESSION['user_id'];
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = basename($_FILES['image']['name']);
        $targetPath = 'uploads/' . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    }

    $query = "INSERT INTO posts (title, body, author_id, image, created_at) VALUES (:title, :body, :author_id, :image, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':body', $body);
    $stmt->bindParam(':author_id', $author_id);
    $stmt->bindParam(':image', $image);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: Unable to save the post.";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
