<?php
// Database connection settings
$host = 'localhost';        // Database host (usually 'localhost')
$dbname = 'ncc_prod'; // Your database name
$username = 'root';          // Your database username
$password = '';              // Your database password (empty if there's no password)

try {
    // Create a new PDO instance and set error mode to exception
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Echo a success message if the connection is successful
    // echo "Connected to the database successfully.";
} catch (PDOException $e) {
    // If the connection fails, display the error message
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
