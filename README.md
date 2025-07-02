# PDO-Wrapper-Class
PDO Wrapper Class For More Easy Coding

Key Security Features


Prepared Statements: All query executions (via the query() method) use prepared statements, which automatically handle escaping of values, preventing SQL injection.

Easy Insert, Update, Delete, Num Rows, Get Results etc Functions.

Error Handling: Exceptions are thrown on database errors, which are caught, logged, and can optionally trigger email notifications. This prevents sensitive error details from being displayed to end-users.

Input Sanitization: The sanitize() method provides a basic layer of input filtering, though prepared statements are the primary defense for query values.

MySQL Function Call Detection: A has_mysql_function_calls() method is included as an extra layer to detect common malicious function names in user-supplied strings, which could be useful for dynamic parts of queries (though again, prepared statements are preferred).

How to Use the PDO Wrapper Class
This setup provides a robust and secure way to interact with your MySQL database using PHP's PDO extension.

Configure db.php
Open db.php and edit the database connection constants with your actual MySQL database credentials:


define('DB_NAME', 'your_database_name'); // IMPORTANT: Change this

define('DB_USER', 'your_username');     // IMPORTANT: Change this

define('DB_PASS', 'your_password');     // IMPORTANT: Change this


You can also optionally configure error email notifications by uncommenting and setting the set_error_email_config section in db.php.



Important Notes
Error Reporting: In a production environment, ensure that PHP's display_errors is set to Off in your php.ini to prevent sensitive information from being shown to users. Errors should be logged to a file.

escape() Method: The escape() method should be used sparingly and primarily for escaping non-value parts of a query (like table or column names if they are dynamically generated and not whitelisted). Always prefer prepared statements for data values.

num_rows() for SELECT: While rowCount() is used, be aware that its behavior for SELECT statements can vary across different PDO drivers and database systems. For accurate row counts on SELECT queries, consider using SELECT COUNT(*) in your SQL.
