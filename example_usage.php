<?php
// Include the database configuration and wrapper class
require_once 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>PDO Wrapper Class Examples</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #0056b3; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 30px; }
        pre { background: #eee; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>PDO Wrapper Class Usage Examples</h1>
        <p class='info'>
            <strong>IMPORTANT:</strong> Before running this example, ensure you have configured `db.php` with your correct database credentials.
            Also, create a test table in your database for these examples:
        </p>
        <pre>
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `status` VARCHAR(50) DEFAULT 'active',
    `skills` VARCHAR(255) DEFAULT NULL, -- For FIND_IN_SET example
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL
);
        </pre>

        <h2>1. Query Execution (SELECT)</h2>
        <?php
        try {
            // Clean up old data for consistent examples
            $db->query("DELETE FROM `users` WHERE email LIKE 'testuser%'");
            echo "<p class='info'>Cleaned up old 'testuser%' data.</p>";

            $stmt = $db->query("SELECT * FROM `users` LIMIT 5");
            $users = $db->get_results($stmt);
            echo "<p>Fetched " . count($users) . " users:</p>";
            if (!empty($users)) {
                echo "<table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Skills</th></tr></thead><tbody>";
                foreach ($users as $user) {
                    echo "<tr><td>{$user['id']}</td><td>{$user['name']}</td><td>{$user['email']}</td><td>{$user['status']}</td><td>{$user['skills']}</td></tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p class='info'>No users found. Let's add some!</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error fetching users: " . $e->getMessage() . "</p>";
        }
        ?>

        <h2>2. Insert Data (Single)</h2>
        <?php
        $new_user_data = [
            'name' => 'Test User 1',
            'email' => 'testuser1@example.com',
            'status' => 'active',
            'skills' => 'PHP,SQL'
        ];
        $inserted_id = $db->insert('users', $new_user_data);

        if ($inserted_id) {
            echo "<p class='success'>User 'Test User 1' inserted with ID: {$inserted_id}</p>";
        } else {
            echo "<p class='error'>Failed to insert user 'Test User 1'. (Email might already exist)</p>";
        }
        ?>

        <h2>3. Insert Data (Multi)</h2>
        <?php
        $new_products_data = [
            ['product_name' => 'Laptop', 'price' => 1200.00],
            ['product_name' => 'Mouse', 'price' => 25.50],
            ['product_name' => 'Keyboard', 'price' => 75.00]
        ];
        $affected_rows = $db->insert_multi('products', $new_products_data);

        if ($affected_rows) {
            echo "<p class='success'>Inserted {$affected_rows} products.</p>";
        } else {
            echo "<p class='error'>Failed to insert multiple products.</p>";
        }
        ?>

        <h2>4. Update Data</h2>
        <?php
        $update_data = [
            'status' => 'inactive',
            'skills' => 'PHP,Python,JavaScript'
        ];
        $update_conditions = [
            'email' => 'testuser1@example.com'
        ];
        $affected_rows = $db->update('users', $update_data, $update_conditions);

        if ($affected_rows) {
            echo "<p class='success'>Updated {$affected_rows} user(s) to 'inactive' status and added skills.</p>";
            // Verify update
            $stmt = $db->query("SELECT * FROM `users` WHERE email = :email", ['email' => 'testuser1@example.com']);
            $updated_user = $db->get_row($stmt);
            if ($updated_user) {
                echo "<p class='info'>Updated User Details: Name: {$updated_user['name']}, Status: {$updated_user['status']}, Skills: {$updated_user['skills']}</p>";
            }
        } else {
            echo "<p class='error'>Failed to update user 'testuser1@example.com'.</p>";
        }

        // Example: Update using IN clause
        $update_data_in = ['status' => 'pending'];
        $update_conditions_in = ['id' => [1, 2, 3]]; // Assuming these IDs might exist
        $affected_rows_in = $db->update('users', $update_data_in, $update_conditions_in);
        echo "<p class='info'>Attempted to update users with IDs 1, 2, 3 to 'pending' status. Affected rows: {$affected_rows_in}</p>";

        // Example: Update using FIND_IN_SET
        $update_data_find = ['status' => 'verified'];
        $update_conditions_find = ['FIND_IN_SET(skills)' => 'Python']; // Find users with 'Python' in their skills
        $affected_rows_find = $db->update('users', $update_data_find, $update_conditions_find);
        echo "<p class='info'>Attempted to update users with 'Python' skill to 'verified' status. Affected rows: {$affected_rows_find}</p>";
        ?>

        <h2>5. Delete Data</h2>
        <?php
        $delete_conditions = [
            'email' => 'testuser1@example.com'
        ];
        $affected_rows = $db->delete('users', $delete_conditions);

        if ($affected_rows) {
            echo "<p class='success'>Deleted {$affected_rows} user(s).</p>";
        } else {
            echo "<p class='error'>Failed to delete user 'testuser1@example.com'.</p>";
        }

        // Example: Delete using IN clause
        $delete_conditions_in = ['id' => [4, 5, 6]]; // Assuming these IDs might exist
        $affected_rows_delete_in = $db->delete('users', $delete_conditions_in);
        echo "<p class='info'>Attempted to delete users with IDs 4, 5, 6. Affected rows: {$affected_rows_delete_in}</p>";
        ?>

        <h2>6. Sanitize Input Data</h2>
        <?php
        $unsafe_html = "<script>alert('xss');</script>Hello & World! <img src='x' onerror='alert(1)'>";
        $sanitized_html = $db->sanitize($unsafe_html);
        echo "<p>Original: " . htmlspecialchars($unsafe_html) . "</p>";
        echo "<p>Sanitized (htmlspecialchars, strip_tags): " . htmlspecialchars($sanitized_html) . "</p>";

        $unsafe_email = "user@example.com; DROP TABLE users;";
        $sanitized_email = $db->sanitize($unsafe_email, FILTER_SANITIZE_EMAIL);
        echo "<p>Original Email: " . htmlspecialchars($unsafe_email) . "</p>";
        echo "<p>Sanitized Email (FILTER_SANITIZE_EMAIL): " . htmlspecialchars($sanitized_email) . "</p>";
        ?>

        <h2>7. Retrieve Query Metadata</h2>
        <?php
        try {
            $stmt = $db->query("SELECT id, name, email FROM `users`");
            echo "<p>Number of rows: " . $db->num_rows($stmt) . "</p>";
            echo "<p>Number of columns: " . $db->num_cols($stmt) . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error retrieving metadata: " . $e->getMessage() . "</p>";
        }
        ?>

        <h2>8. Last Insert ID</h2>
        <?php
        // Insert another user to get a new ID
        $new_user_data_2 = [
            'name' => 'Test User 2',
            'email' => 'testuser2@example.com',
            'status' => 'active'
        ];
        $inserted_id_2 = $db->insert('users', $new_user_data_2);
        if ($inserted_id_2) {
            echo "<p class='success'>User 'Test User 2' inserted with ID: {$inserted_id_2}</p>";
            echo "<p>Last inserted ID: " . $db->last_insert_id() . "</p>";
        } else {
            echo "<p class='error'>Failed to insert Test User 2.</p>";
        }
        ?>

        <h2>9. Get Single Row</h2>
        <?php
        try {
            $stmt = $db->query("SELECT * FROM `users` WHERE email = :email", ['email' => 'testuser2@example.com']);
            $user_row = $db->get_row($stmt);
            if ($user_row) {
                echo "<p class='success'>Found user by email 'testuser2@example.com':</p>";
                echo "<pre>" . print_r($user_row, true) . "</pre>";
            } else {
                echo "<p class='error'>User 'testuser2@example.com' not found.</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error fetching single row: " . $e->getMessage() . "</p>";
        }
        ?>

        <h2>10. Escape Values (Use with caution, prefer prepared statements)</h2>
        <?php
        $unsafe_string = "O'Reilly's book";
        $escaped_string = $db->escape($unsafe_string);
        echo "<p>Original: " . htmlspecialchars($unsafe_string) . "</p>";
        echo "<p>Escaped: " . htmlspecialchars($escaped_string) . "</p>";

        $unsafe_array = ["value1", "value'2", "value;3"];
        $escaped_array = $db->escape($unsafe_array);
        echo "<p>Original Array: " . htmlspecialchars(implode(', ', $unsafe_array)) . "</p>";
        echo "<p>Escaped Array: " . htmlspecialchars(implode(', ', $escaped_array)) . "</p>";
        ?>

        <h2>11. Check for MySQL Function Calls</h2>
        <?php
        $input1 = "normal string";
        $input2 = "some_value UNION SELECT password FROM users";
        $input3 = "user_name; DROP TABLE accounts;";

        echo "<p>'{$input1}' has MySQL function calls? " . ($db->has_mysql_function_calls($input1) ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        echo "<p>'{$input2}' has MySQL function calls? " . ($db->has_mysql_function_calls($input2) ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        echo "<p>'{$input3}' has MySQL function calls? " . ($db->has_mysql_function_calls($input3) ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        ?>

        <h2>12. Check if Table Exists</h2>
        <?php
        echo "<p>Table 'users' exists? " . ($db->table_exists('users') ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
        echo "<p>Table 'non_existent_table' exists? " . ($db->table_exists('non_existent_table') ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
        ?>

        <h2>13. Check if Record Exists</h2>
        <?php
        echo "<p>Record in 'users' with email 'testuser2@example.com' exists? " . ($db->record_exists('users', ['email' => 'testuser2@example.com']) ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
        echo "<p>Record in 'users' with email 'nonexistent@example.com' exists? " . ($db->record_exists('users', ['email' => 'nonexistent@example.com']) ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
        ?>

        <h2>14. Truncate Table</h2>
        <?php
        // Insert dummy data into products table first
        $db->insert('products', ['product_name' => 'Dummy Product 1', 'price' => 10.00]);
        $db->insert('products', ['product_name' => 'Dummy Product 2', 'price' => 20.00]);
        $stmt_before_truncate = $db->query("SELECT COUNT(*) as count FROM `products`");
        $count_before = $db->get_row($stmt_before_truncate)['count'];
        echo "<p>Products count before truncate: {$count_before}</p>";

        $truncated = $db->truncate('products');
        if ($truncated) {
            echo "<p class='success'>Table 'products' truncated successfully.</p>";
            $stmt_after_truncate = $db->query("SELECT COUNT(*) as count FROM `products`");
            $count_after = $db->get_row($stmt_after_truncate)['count'];
            echo "<p>Products count after truncate: {$count_after}</p>";
        } else {
            echo "<p class='error'>Failed to truncate table 'products'.</p>";
        }
        ?>

        <h2>15. Total Queries Executed</h2>
        <?php
        echo "<p>Total queries executed by this instance: " . $db->get_query_count() . "</p>";
        ?>

        <h2>16. Error Email (Simulated)</h2>
        <?php
        echo "<p class='info'>
            To test error email, uncomment the `set_error_email_config` in `db.php`
            and provide a valid `to_email`. Then, you can try to trigger an error,
            e.g., by attempting to query a non-existent table.
            <br>
            Note: The `mail()` function requires a properly configured mail server on your PHP environment.
        </p>";
        /*
        // Example of triggering an error to test email (uncomment with caution)
        try {
            $db->query("SELECT * FROM `non_existent_table_for_error_test`");
            echo "<p class='success'>No error triggered (this should not happen if table doesn't exist).</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error triggered as expected. Check your configured email for a notification.</p>";
        }
        */
        ?>

    </div>
</body>
</html>
