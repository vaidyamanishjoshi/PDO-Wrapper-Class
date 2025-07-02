<?php

// Define database connection constants for the PRIMARY connection
define('DBTYPE', 'mysql'); // Change this to 'pgsql', 'sqlite', or 'sqlsrv' as needed
define('HST', 'localhost');
define('DBN', 'your_database_name'); // IMPORTANT: Change this to your actual database name or SQLite file path
define('USR', 'your_username');     // IMPORTANT: Change this to your actual database username (ignored for SQLite)
define('PWD', 'your_password');     // IMPORTANT: Change this to your actual database password (ignored for SQLite)

// Option to display database errors directly on the page (set to false in production)
define('DISPLAY_DEBUG', true);

// Include the PDO_Wrapper class file
require_once 'pdo_wrapper.php'; // Ensure this path is correct

// Instantiate the PRIMARY PDO_Wrapper database connection
// You can now create multiple instances of PDO_Wrapper for different databases or configurations
try {
    // Pass the driver, host, db name, user, password, and the DISPLAY_DEBUG flag
    $db = new PDO_Wrapper(DBTYPE, HST, DBN, USR, PWD, [], DISPLAY_DEBUG);

    // Optional: Configure error email notifications for the primary connection
    // Uncomment and set your email if you want to receive error alerts
    /*
    $db->set_error_email_config([
        'send_on_error' => true,
        'to_email' => 'admin@example.com', // Your admin email
        'from_email' => 'db-errors@yourdomain.com',
        'subject' => 'URGENT: Database Error on Your App!',
    ]);
    */

    // Example of creating another database connection (e.g., to a different database or server)
    /*
    define('DBTYPE_LOGS', 'mysql');
    define('HST_LOGS', 'localhost');
    define('DBN_LOGS', 'your_logs_database');
    define('USR_LOGS', 'your_logs_username');
    define('PWD_LOGS', 'your_logs_password');

    $db_logs = new PDO_Wrapper(DBTYPE_LOGS, HST_LOGS, DBN_LOGS, USR_LOGS, PWD_LOGS, [], DISPLAY_DEBUG);
    */

} catch (PDOException $e) {
    // In a real application, you might redirect to an error page or show a friendly message.
    // The actual error is logged by the class, and displayed if DISPLAY_DEBUG is true.
    die("A database connection error occurred. Please try again later.");
}

?>
