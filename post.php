<?php
// Include the database connection settings
include('db.php');

// Check if the 'id' parameter is passed in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $post_id = $_GET['id'];

    try {
        // Prepare SQL statement to get the post details
        $stmt = $conn->prepare("SELECT posts.id, posts.title, posts.body, posts.image, posts.created_at, users.username 
                                FROM posts 
                                JOIN users ON posts.author_id = users.id
                                WHERE posts.id = :post_id");
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the post details
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the post is not found, show an error
        if (!$post) {
            echo "Post not found.";
            exit();
        }
    } catch (PDOException $e) {
        // Handle any errors during the database operation
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "Invalid post ID.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <!-- Link to external CSS or Bootstrap for styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Add your custom CSS here -->
</head>
<body>

<!-- Navbar Section -->
<?php include('navbar.php'); ?>

<!-- Post Detail Section -->
<section class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="post-detail">
                <h1 class="display-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                <p class="text-muted">Posted by <strong><?php echo htmlspecialchars($post['username']); ?></strong> on <?php echo date("F j, Y, g:i a", strtotime($post['created_at'])); ?></p>

                <!-- Display the post image if it exists -->
                <?php if ($post['image']) : ?>
                    <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="img-fluid mb-3">
                <?php endif; ?>

                <p><?php echo nl2br(htmlspecialchars($post['body'])); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Footer Section -->
<footer class="bg-light text-center py-4">
    <p>&copy; <?php echo date('Y'); ?> NCC Cadet Blog. All rights reserved.</p>
</footer>

<!-- JS Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
