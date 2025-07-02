<?php

// Define database connection constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name'); // IMPORTANT: Change this to your actual database name
define('DB_USER', 'your_username');     // IMPORTANT: Change this to your actual database username
define('DB_PASS', 'your_password');     // IMPORTANT: Change this to your actual database password

// Include the PDO_Wrapper class file
require_once 'pdo_wrapper.php';

// Instantiate the PDO_Wrapper class
try {
    $db = new PDO_Wrapper(DB_HOST, DB_NAME, DB_USER, DB_PASS);

    // Optional: Configure error email notifications
    // Uncomment and set your email if you want to receive error alerts
    /*
    $db->set_error_email_config([
        'send_on_error' => true,
        'to_email' => 'admin@example.com', // Your admin email
        'from_email' => 'db-errors@yourdomain.com',
        'subject' => 'URGENT: Database Error on Your App!',
    ]);
    */

} catch (PDOException $e) {
    // In a real application, you might redirect to an error page or show a friendly message.
    // For now, we'll just show a generic error. The actual error is logged by the class.
    die("A database connection error occurred. Please try again later.");
}

?>