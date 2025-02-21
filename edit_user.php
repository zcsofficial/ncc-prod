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

// Fetch user details for the given ID
try {
    $stmt = $conn->prepare("SELECT u.username, u.role, c.full_name, c.email FROM users u LEFT JOIN cadets c ON u.id = c.user_id WHERE u.id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.";
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Variable to store success message
$success_message = "";

// Update user details when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];

    try {
        // Update users and cadets tables
        $stmt = $conn->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE cadets SET full_name = :full_name, email = :email WHERE user_id = :id");
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $success_message = "User updated successfully!";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
        }
        .alert {
            font-weight: 500;
            font-size: 1rem;
            text-align: center;
        }
        h2 {
            font-weight: 700;
            color: #343a40;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center"><i class="bi bi-pencil-square"></i> Edit User</h2>

    <!-- Success notification -->
    <?php if ($success_message): ?>
        <div class="alert alert-success mt-3" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="username" class="form-label">Username <i class="bi bi-person"></i></label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="full_name" class="form-label">Full Name <i class="bi bi-person-badge"></i></label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email <i class="bi bi-envelope"></i></label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role <i class="bi bi-person-gear"></i></label>
            <select name="role" class="form-select">
                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
            </select>
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update User</button>
            <a href="manage_users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </form>
</div>
</body>
</html>

<?php
$conn = null;
?>
