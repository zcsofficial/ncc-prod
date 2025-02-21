<?php
// Include the database connection file
include 'db.php';

// Define file size and type restrictions
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedFileTypes = ['jpg', 'jpeg', 'png', 'gif'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['carousel_image'])) {
    $image = $_FILES['carousel_image'];

    // Validate and upload the image
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a valid image
    $check = getimagesize($image["tmp_name"]);
    if ($check === false) {
        echo "<script>alert('File is not an image.'); window.location = 'add_image.php';</script>";
        exit;
    }

    // Check file size
    if ($image["size"] > $maxFileSize) {
        echo "<script>alert('Sorry, your file is too large. Max size allowed is 5MB.'); window.location = 'add_image.php';</script>";
        exit;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, $allowedFileTypes)) {
        echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.'); window.location = 'add_image.php';</script>";
        exit;
    }

    // Create a unique file name to prevent overwriting
    $uniqueName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $uniqueName;

    // Upload the file
    if (move_uploaded_file($image["tmp_name"], $target_file)) {
        // Insert image path into the database
        try {
            $query = "INSERT INTO carousel_images (image) VALUES (:image)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':image', $target_file);
            $stmt->execute();

            // Redirect to dashboard after successful upload
            echo "<script>alert('Image uploaded successfully!'); window.location = 'dashboard.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.location = 'add_image.php';</script>";
        }
    } else {
        echo "<script>alert('Sorry, there was an error uploading your file.'); window.location = 'add_image.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Carousel Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 50px;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Upload Carousel Image</h2>

    <!-- Form for image upload -->
    <form action="add_image.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="carousel_image" class="form-label">Choose an Image</label>
            <input type="file" class="form-control" name="carousel_image" id="carousel_image" required>
            <small class="form-text text-muted">Allowed file types: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
        </div>

        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
