PDO Wrapper Class
A robust, secure, and easy-to-use PHP PDO wrapper designed to simplify database interactions (MySQL, PostgreSQL, SQLite, SQL Server). This class provides a clean API for common database operations, including a fluent Query Builder, basic ORM-like object mapping, and built-in security features like prepared statements and input filtering.

Features
Multi-Database Support: Works seamlessly with MySQL, PostgreSQL, SQLite, and SQL Server.

Singleton Pattern: Ensures a single database connection per script execution for efficient resource management.

Prepared Statements: All queries use prepared statements to prevent SQL injection attacks.

Basic CRUD Operations: Simple methods for insert(), insert_multi(), update(), and delete().

Fluent Query Builder: Chainable methods like select(), from(), where(), join(), orderBy(), limit(), offset(), group_by(), having(), and union() for constructing complex queries programmatically.

Object Mapping (ORM-like): Map query results directly to custom PHP objects for an object-oriented data access layer.

Input Filtering: Basic data sanitization using filter_var.

Error Handling: Configurable error logging and email notifications on database errors.

Utility Methods: Functions to check table/record existence, get last insert ID, count rows/columns, and execute stored procedures.

Dynamic Identifier Quoting: Automatically quotes table and column names based on the database driver, allowing unquoted simple identifiers for MySQL.

Installation
Download Files:
Download pdo_wrapper.php and db.php into your project directory.

Configure db.php:
Open db.php and update the database connection constants (DBTYPE, HST, DBN, USR, PWD) with your actual database credentials.
Set DISPLAY_DEBUG to true during development to see database errors directly, but set it to false in production.

// db.php
define('DBTYPE', 'mysql'); // or 'pgsql', 'sqlite', 'sqlsrv'
define('HST', 'localhost');
define('DBN', 'your_database_name');
define('USR', 'your_username');
define('PWD', 'your_password');
define('DISPLAY_DEBUG', true);

require_once 'pdo_wrapper.php';

try {
    $db = PDO_Wrapper::get_instance(DBTYPE, HST, DBN, USR, PWD, [], DISPLAY_DEBUG);
} catch (PDOException $e) {
    die("Database connection failed.");
}

Database Schema:
Create the necessary tables in your database. Here are example schemas for users and products tables:

For MySQL:

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `status` VARCHAR(50) DEFAULT 'active',
    `skills` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL
);

For PostgreSQL:

CREATE TABLE IF NOT EXISTS "users" (
    "id" SERIAL PRIMARY KEY,
    "name" VARCHAR(255) NOT NULL,
    "email" VARCHAR(255) UNIQUE NOT NULL,
    "status" VARCHAR(50) DEFAULT 'active',
    "skills" VARCHAR(255) DEFAULT NULL,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "products" (
    "product_id" SERIAL PRIMARY KEY,
    "product_name" VARCHAR(255) NOT NULL,
    "price" DECIMAL(10, 2) NOT NULL
);

For SQLite:

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    status TEXT DEFAULT 'active',
    skills TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    product_id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_name TEXT NOT NULL,
    price REAL NOT NULL
);

Usage Examples
Once configured, you can use the $db object to interact with your database.

1. Getting the Database Instance (Singleton)
The get_instance() method ensures you always work with the same database connection throughout your script.

<?php
require_once 'db.php'; // This initializes $db

// $db is already the single instance
$another_db_instance = PDO_Wrapper::get_instance(DBTYPE, HST, DBN, USR, PWD, [], DISPLAY_DEBUG);
// $db === $another_db_instance will be true
?>

2. Basic CRUD Operations
Insert Single Record
<?php
$user_data = [
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'status' => 'active',
];
$inserted_id = $db->insert('users', $user_data);
if ($inserted_id) {
    echo "User inserted with ID: " . $inserted_id;
}
?>

Insert Multiple Records
<?php
$products_data = [
    ['product_name' => 'Laptop', 'price' => 1200.00],
    ['product_name' => 'Mouse', 'price' => 25.50],
];
$affected_rows = $db->insert_multi('products', $products_data);
echo "Inserted {$affected_rows} products.";
?>

Update Records
<?php
// Simple update
$update_data = ['status' => 'inactive'];
$conditions = ['email' => 'john.doe@example.com'];
$affected_rows = $db->update('users', $update_data, $conditions);
echo "Updated {$affected_rows} user(s).";

// Update with IN clause
$update_data_in = ['status' => 'pending'];
$conditions_in = ['id' => [1, 2, 3]];
$affected_rows_in = $db->update('users', $update_data_in, $conditions_in);
echo "Updated {$affected_rows_in} user(s).";
?>

Delete Records
<?php
// Simple delete
$conditions = ['email' => 'john.doe@example.com'];
$affected_rows = $db->delete('users', $conditions);
echo "Deleted {$affected_rows} user(s).";

// Delete with IN clause
$conditions_in = ['product_id' => [1, 2]];
$affected_rows_in = $db->delete('products', $conditions_in);
echo "Deleted {$affected_rows_in} product(s).";
?>

3. Query Builder
The Query Builder allows you to construct complex SELECT queries fluently.

<?php
// Select all active users, ordered by name
$active_users = $db->select()
                   ->from('users')
                   ->where('status', 'active')
                   ->orderBy('name', 'ASC')
                   ->get();

// Select specific product details, limit to 5
$products_info = $db->select('product_name AS name, price')
                    ->from('products')
                    ->limit(5)
                    ->get();

// Find a user by ID or email
$user = $db->select()
           ->from('users')
           ->where('id', 1)
           ->or_where('email', 'another@example.com')
           ->first();

// Select users with 'PHP' in their skills (MySQL FIND_IN_SET example)
$php_devs = $db->select()
               ->from('users')
               ->where_like('skills', '%PHP%') // Or using FIND_IN_SET for MySQL: ->where('FIND_IN_SET(skills)', 'PHP')
               ->get();

// Get paginated results (e.g., page 2, 10 items per page)
$page = 2;
$perPage = 10;
$paginated_users = $db->select()
                      ->from('users')
                      ->limit($perPage)
                      ->offset(($page - 1) * $perPage)
                      ->get();
?>

4. Raw Queries and Results
For more direct control, use query() to execute any SQL and get_results()/get_row() to fetch data.

<?php
// Execute a raw SELECT query
$stmt = $db->query("SELECT id, name, email FROM users WHERE status = :status", [':status' => 'active']);
$users = $db->get_results($stmt);

// Fetch a single row
$single_user_stmt = $db->query("SELECT * FROM users WHERE id = :id", [':id' => 1]);
$user_data = $db->get_row($single_user_stmt);

// Get number of rows/columns
echo "Rows affected by last query: " . $db->num_rows($stmt);
echo "Columns in result set: " . $db->num_cols($stmt);
echo "Last inserted ID: " . $db->last_insert_id();
?>

5. Object Mapping (ORM-like)
Define simple model classes to map database rows to objects.

<?php
// In pdo_wrapper.php or a separate models.php file:
/*
class BaseModel {
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

class User extends BaseModel {
    public $id;
    public $name;
    public $email;
    public $status;
    public function getFullName() { return $this->name; }
}

class Product extends BaseModel {
    public $product_id;
    public $product_name;
    public $price;
    public function getFormattedPrice() { return '$' . number_format($this->price, 2); }
}
*/

// Using Query Builder with object mapping
$user_objects = $db->select()->from('users')->where('status', 'active')->as_object('User')->get();
foreach ($user_objects as $user) {
    echo "User: " . $user->getFullName() . " (Email: " . $user->email . ")";
}

// Using raw query with object mapping
$stmt = $db->query("SELECT * FROM products WHERE product_id = :id", [':id' => 1]);
$product_object = $db->get_row($stmt, PDO::FETCH_ASSOC, 'Product');
if ($product_object instanceof Product) {
    echo "Product: " . $product_object->product_name . ", Price: " . $product_object->getFormattedPrice();
}
?>

6. Helpers and Utilities
Input Filtering
<?php
$raw_input = "<p>Hello &lt;script&gt;alert('xss')&lt;/script&gt; World!</p>";
$filtered_input = $db->filter($raw_input);
echo "Filtered: " . htmlspecialchars($filtered_input); // Output: Hello &lt;script&gt;alert('xss')&lt;/script&gt; World!

$email_input = "test@example.com; DROP TABLE users;";
$filtered_email = $db->filter($email_input, FILTER_SANITIZE_EMAIL);
echo "Filtered Email: " . $filtered_email; // Output: test@example.com
?>

Check Table/Record Existence
<?php
if ($db->table_exists('users')) {
    echo "Table 'users' exists.";
}

if ($db->record_exists('users', ['email' => 'john.doe@example.com'])) {
    echo "User 'john.doe@example.com' exists.";
}
?>

Truncate Table
<?php
if ($db->truncate('products')) {
    echo "Table 'products' truncated.";
}
?>

Execute Stored Procedure
<?php
// Assuming a MySQL stored procedure:
// DELIMITER //
// CREATE PROCEDURE GetUserById(IN userId INT)
// BEGIN
//     SELECT id, name, email FROM users WHERE id = userId;
// END //
// DELIMITER ;

$stmt_proc = $db->call_procedure('GetUserById', ['userId' => 1]);
$user_from_proc = $db->get_row($stmt_proc);
print_r($user_from_proc);
?>

7. Configuration and Debugging
Set Error Email Configuration
<?php
$db->set_error_email_config([
    'send_on_error' => true,
    'to_email' => 'your_admin_email@example.com',
    'from_email' => 'app-errors@yourdomain.com',
    'subject' => 'URGENT: Application Database Error',
]);
// Now, if a PDOException occurs, an email will be sent.
?>

Get Query Count
<?php
// Perform some queries...
$db->query("SELECT 1 FROM users");
$db->insert('products', ['product_name' => 'New Gadget', 'price' => 50.00]);

echo "Total queries executed: " . $db->get_query_count();
?>

Contributing
Feel free to contribute to this PDO Wrapper class by opening issues or submitting pull requests.

License
[Specify your license here, e.g., MIT License]