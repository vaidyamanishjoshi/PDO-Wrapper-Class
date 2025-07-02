<?php

/**
 * PDO_Wrapper Class
 *
 https://github.com/vaidyamanishjoshi/PDO-Wrapper-Class
 
 * A robust and secure PDO wrapper for MySQL database interactions.
 * This class provides methods for connecting, executing queries,
 * sanitizing input, retrieving results, and performing CRUD operations
 * with built-in security features like prepared statements.
 */
class PDO_Wrapper {
    /**
     * @var PDO The PDO database connection instance.
     */
    private $pdo;

    /**
     * @var int The total number of queries executed by this instance.
     */
    private $query_count = 0;

    /**
     * @var string|null The last executed query string (for debugging/logging).
     */
    private $last_query = null;

    /**
     * @var array Configuration for sending error emails.
     */
    private $error_email_config = [
        'send_on_error' => false,
        'to_email' => '',
        'from_email' => 'no-reply@example.com',
        'subject' => 'Database Error Alert',
    ];

    /**
     * Constructor: Initializes the database connection.
     *
     * @param string $host The database host.
     * @param string $db_name The database name.
     * @param string $username The database username.
     * @param string $password The database password.
     * @param array $options Optional PDO connection options.
     * @throws PDOException If the connection fails.
     */
    public function __construct($host, $db_name, $username, $password, $options = []) {
        try {
            $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
            $default_options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security and performance
            ];
            $this->pdo = new PDO($dsn, $username, $password, array_merge($default_options, $options));
        } catch (PDOException $e) {
            $this->handle_error($e, "Failed to connect to the database.");
            throw $e; // Re-throw the exception after handling
        }
    }

    /**
     * Sets the error email configuration.
     *
     * @param array $config An associative array with 'send_on_error', 'to_email', 'from_email', 'subject'.
     */
    public function set_error_email_config(array $config) {
        $this->error_email_config = array_merge($this->error_email_config, $config);
    }

    /**
     * Private helper method to handle PDO exceptions.
     * Logs the error, and optionally sends an email.
     *
     * @param PDOException $e The PDOException object.
     * @param string $message A custom message to prepend to the error.
     */
    private function handle_error(PDOException $e, $message = "") {
        $error_message = ($message ? $message . " " : "") . "PDO Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
        error_log($error_message); // Log the error to the server's error log

        if ($this->error_email_config['send_on_error']) {
            $this->send_error_email($error_message);
        }

        // For development, you might want to display the error, but for production, avoid this.
        // echo "Database Error: " . $error_message;
    }

    /**
     * @usage
     * Connect to a given MySQL server.
     * This function is implicitly called by the constructor.
     * No direct usage example needed as it's part of object instantiation.
     *
     * Example:
     * $db = new PDO_Wrapper('localhost', 'your_db', 'your_user', 'your_password');
     */

    /**
     * @usage
     * Execute arbitrary SQL queries using prepared statements.
     * This is the core method for executing any SQL query.
     *
     * @param string $sql The SQL query string (with placeholders like :param or ?).
     * @param array $params An associative array of parameters for the prepared statement (e.g., ['param' => 'value'] or [1 => 'value']).
     * @return PDOStatement The PDOStatement object on success.
     * @throws PDOException If the query execution fails.
     */
    public function query($sql, $params = []) {
        $this->query_count++;
        $this->last_query = $sql;
        try {
            $stmt = $this->pdo->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    // Determine PDO type based on value type
                    $type = PDO::PARAM_STR; // Default to string
                    if (is_int($value)) {
                        $type = PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $type = PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $type = PDO::PARAM_NULL;
                    }
                    // Bind by name for associative arrays, by position for indexed arrays
                    if (is_string($key)) {
                        $stmt->bindValue(":$key", $value, $type);
                    } else {
                        $stmt->bindValue($key + 1, $value, $type); // +1 for 1-based indexing in PDO
                    }
                }
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            $this->handle_error($e, "Failed to execute query: " . $sql);
            throw $e;
        }
    }

    /**
     * @usage
     * Sanitize / filter input data function.
     * This function uses filter_var for basic sanitization. For more complex scenarios,
     * consider specific validation rules or custom regex.
     *
     * @param mixed $data The data to sanitize.
     * @param int $filter The filter to apply (e.g., FILTER_SANITIZE_STRING, FILTER_SANITIZE_EMAIL).
     * Note: FILTER_SANITIZE_STRING is deprecated in PHP 8.1+. Use htmlspecialchars or similar for HTML output.
     * For general input, FILTER_UNSAFE_RAW with appropriate flags, or specific filters are better.
     * @param mixed $options Options for the filter.
     * @return mixed The sanitized data.
     */
    public function sanitize($data, $filter = FILTER_UNSAFE_RAW, $options = []) {
        // For string data, consider HTML escaping if it's going to be outputted to HTML
        if (is_string($data) && $filter === FILTER_UNSAFE_RAW) {
            return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
        }
        // For other types, use filter_var with specified filter
        return filter_var($data, $filter, $options);
    }

    /**
     * @usage
     * Retrieve the number of query result rows.
     * This function should be called after a SELECT query has been executed using `query()`.
     * Note: `rowCount()` on SELECT statements might not be reliable across all drivers.
     * For accurate row counts for SELECT, it's often better to use `COUNT(*)` in the SQL query itself.
     *
     * @param PDOStatement $stmt The PDOStatement object returned by `query()`.
     * @return int The number of rows affected by the last SQL statement or 0 if not applicable.
     */
    public function num_rows(PDOStatement $stmt) {
        return $stmt->rowCount();
    }

    /**
     * @usage
     * Retrieve the number of query result columns.
     * This function should be called after a SELECT query has been executed using `query()`.
     *
     * @param PDOStatement $stmt The PDOStatement object returned by `query()`.
     * @return int The number of columns in the result set.
     */
    public function num_cols(PDOStatement $stmt) {
        return $stmt->columnCount();
    }

    /**
     * @usage
     * Retrieve the last inserted table identifier (ID).
     * This is typically used after an INSERT query on a table with an AUTO_INCREMENT primary key.
     *
     * @param string|null $name The name of the sequence object from which the ID should be returned.
     * Not generally used with MySQL, leave as null.
     * @return string The ID of the last inserted row.
     */
    public function last_insert_id($name = null) {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * @usage
     * Retrieve the query results in a single array.
     * This function fetches all rows from a PDOStatement object.
     *
     * @param PDOStatement $stmt The PDOStatement object returned by `query()`.
     * @param int $fetch_mode The fetch mode (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ).
     * @return array An array containing all of the result set rows.
     */
    public function get_results(PDOStatement $stmt, $fetch_mode = PDO::FETCH_ASSOC) {
        return $stmt->fetchAll($fetch_mode);
    }

    /**
     * @usage
     * Return a query result that has just one row.
     * This function fetches a single row from a PDOStatement object.
     *
     * @param PDOStatement $stmt The PDOStatement object returned by `query()`.
     * @param int $fetch_mode The fetch mode (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ).
     * @return mixed The next row from a result set as an array or object, or false if there are no more rows.
     */
    public function get_row(PDOStatement $stmt, $fetch_mode = PDO::FETCH_ASSOC) {
        return $stmt->fetch($fetch_mode);
    }

    /**
     * @usage
     * Escape a single string or an array of literal text values to use in queries.
     * This method is primarily for escaping values that are NOT part of prepared statements
     * (e.g., table names, column names, or complex SQL fragments where prepared statements aren't feasible).
     * For values, ALWAYS use prepared statements.
     *
     * @param string|array $value The string or array of strings to escape.
     * @return string|array The escaped string(s).
     */
    public function escape($value) {
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        }
        // Quote method is used for escaping. It adds quotes around the string.
        // This is generally safe for literal values, but not for identifiers.
        // For identifiers, you should use backticks or ensure they are whitelisted.
        return $this->pdo->quote($value);
    }

    /**
     * @usage
     * Determine if one value or an array of values contain common MySQL function calls.
     * This is a basic security check to prevent injection of malicious function calls
     * into parts of the query where prepared statements might not apply (e.g., dynamic column names).
     *
     * @param string|array $value The value(s) to check.
     * @return bool True if any common MySQL function call is found, false otherwise.
     */
    public function has_mysql_function_calls($value) {
        $functions = [
            'SLEEP(', 'BENCHMARK(', 'LOAD_FILE(', 'OUTFILE(', 'INFILE(',
            'UNION SELECT', 'OR 1=1', 'AND 1=1', 'DROP TABLE', 'DELETE FROM',
            'UPDATE ', 'INSERT INTO', 'CREATE TABLE', 'ALTER TABLE',
            'GRANT ', 'REVOKE ', 'SHOW DATABASES', 'SHOW TABLES', 'INFORMATION_SCHEMA',
            'CONCAT(', 'GROUP_CONCAT(', 'CAST(', 'CONVERT(', 'MD5(', 'SHA1(',
            'HEX(', 'UNHEX(', 'FROM_BASE64(', 'TO_BASE64(', 'ASCII(', 'CHAR(',
            'ORD(', 'CHR(', 'SUBSTRING(', 'MID(', 'LEFT(', 'RIGHT(', 'INSTR(',
            'LOCATE(', 'LPAD(', 'RPAD(', 'REPLACE(', 'REVERSE(', 'SOUNDEX(',
            'TRIM(', 'LOWER(', 'UPPER(', 'NOW(', 'CURDATE(', 'CURTIME(',
            'UNIX_TIMESTAMP(', 'FROM_UNIXTIME(', 'DATE_FORMAT(', 'DATE_ADD(',
            'DATE_SUB(', 'ADDDATE(', 'SUBDATE(', 'DATEDIFF(', 'TIMEDIFF(',
            'VERSION(', 'DATABASE(', 'USER(', 'CURRENT_USER(', 'SESSION_USER(',
            'SYSTEM_USER(', '@@VERSION', '@@DATADIR', '@@HOSTNAME', '@@PORT',
            '@@SOCKET', '@@CHARACTER_SET_CLIENT', '@@CHARACTER_SET_RESULTS',
            '@@COLLATION_CONNECTION', '@@SQL_MODE', '@@AUTOCOMMIT',
            '-- ', '#', '/*', '*/', ';',
        ];

        $value_to_check = is_array($value) ? implode(' ', $value) : (string)$value;
        $value_to_check = strtoupper($value_to_check); // Convert to uppercase for case-insensitive check

        foreach ($functions as $func) {
            if (strpos($value_to_check, strtoupper($func)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @usage
     * Check if a table exists in the connected database.
     *
     * @param string $table_name The name of the table to check.
     * @return bool True if the table exists, false otherwise.
     */
    public function table_exists($table_name) {
        try {
            $stmt = $this->query("SHOW TABLES LIKE :table_name", [':table_name' => $table_name]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Log the error but don't re-throw if just checking existence
            error_log("Error checking table existence for '$table_name': " . $e->getMessage());
            return false;
        }
    }

    /**
     * @usage
     * Check if a given table record exists based on a condition.
     *
     * @param string $table The table name.
     * @param array $conditions An associative array of conditions (e.g., ['id' => 1, 'status' => 'active']).
     * @return bool True if the record exists, false otherwise.
     */
    public function record_exists($table, array $conditions) {
        if (empty($conditions)) {
            return false; // No conditions provided, cannot check for specific record
        }

        $where_clauses = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            $where_clauses[] = "`{$field}` = :{$field}";
            $params[":{$field}"] = $value;
        }
        $sql = "SELECT 1 FROM `{$table}` WHERE " . implode(' AND ', $where_clauses) . " LIMIT 1";

        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking record existence in '$table': " . $e->getMessage());
            return false;
        }
    }

    /**
     * @usage
     * Execute INSERT query from values that define tables, field names, and field values.
     *
     * @param string $table The table name.
     * @param array $data An associative array of data to insert (field => value).
     * @return int The ID of the last inserted row, or 0 on failure.
     */
    public function insert($table, array $data) {
        if (empty($data)) {
            return 0;
        }

        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ":{$field}"; }, $fields);

        $sql = "INSERT INTO `{$table}` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $placeholders) . ")";

        $params = [];
        foreach ($data as $field => $value) {
            $params[":{$field}"] = $value;
        }

        try {
            $this->query($sql, $params);
            return (int)$this->last_insert_id();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * @usage
     * Execute INSERT MULTI query from values that define tables, field names, and field values.
     *
     * @param string $table The table name.
     * @param array $data An array of associative arrays, where each inner array is a row to insert.
     * @return int The number of affected rows, or 0 on failure.
     */
    public function insert_multi($table, array $data) {
        if (empty($data) || !is_array($data[0])) {
            return 0;
        }

        $fields = array_keys($data[0]);
        $placeholders_template = "(" . implode(", ", array_map(function($field) { return ":{$field}"; }, $fields)) . ")";

        $values_clauses = [];
        $all_params = [];
        $param_counter = 0;

        foreach ($data as $row) {
            $current_placeholders = [];
            $current_params = [];
            foreach ($fields as $field) {
                $unique_placeholder = ":{$field}_{$param_counter}";
                $current_placeholders[] = $unique_placeholder;
                $current_params[$unique_placeholder] = $row[$field] ?? null; // Handle missing keys
            }
            $values_clauses[] = "(" . implode(", ", $current_placeholders) . ")";
            $all_params = array_merge($all_params, $current_params);
            $param_counter++;
        }

        $sql = "INSERT INTO `{$table}` (`" . implode("`, `", $fields) . "`) VALUES " . implode(", ", $values_clauses);

        try {
            $stmt = $this->query($sql, $all_params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }


    /**
     * @usage
     * Execute UPDATE query from values that define tables, field names, field values and conditions.
     *
     * @param string $table The table name.
     * @param array $data An associative array of data to update (field => value).
     * @param array $conditions An associative array of conditions (e.g., ['id' => 1]).
     * @return int The number of affected rows, or 0 on failure.
     */
    public function update($table, array $data, array $conditions) {
        if (empty($data) || empty($conditions)) {
            return 0;
        }

        $set_clauses = [];
        $params = [];
        foreach ($data as $field => $value) {
            $set_clauses[] = "`{$field}` = :set_{$field}";
            $params[":set_{$field}"] = $value;
        }

        $where_clauses = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) { // Handle IN clause
                list($in_clause, $in_params) = $this->in($field, $value);
                $where_clauses[] = $in_clause;
                $params = array_merge($params, $in_params);
            } elseif (strpos($field, 'FIND_IN_SET') === 0) { // Handle FIND_IN_SET
                // Expected format: 'FIND_IN_SET(field)' => 'value'
                $original_field = str_replace('FIND_IN_SET(', '', $field);
                $original_field = rtrim($original_field, ')');
                list($find_in_set_clause, $find_in_set_params) = $this->find_in_set($original_field, $value);
                $where_clauses[] = $find_in_set_clause;
                $params = array_merge($params, $find_in_set_params);
            } else {
                $where_clauses[] = "`{$field}` = :where_{$field}";
                $params[":where_{$field}"] = $value;
            }
        }

        $sql = "UPDATE `{$table}` SET " . implode(", ", $set_clauses) . " WHERE " . implode(" AND ", $where_clauses);

        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * @usage
     * Execute DELETE query from values that define tables and conditions.
     *
     * @param string $table The table name.
     * @param array $conditions An associative array of conditions (e.g., ['id' => 1]).
     * @return int The number of affected rows, or 0 on failure.
     */
    public function delete($table, array $conditions) {
        if (empty($conditions)) {
            return 0; // Prevent accidental full table delete
        }

        $where_clauses = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) { // Handle IN clause
                list($in_clause, $in_params) = $this->in($field, $value);
                $where_clauses[] = $in_clause;
                $params = array_merge($params, $in_params);
            } elseif (strpos($field, 'FIND_IN_SET') === 0) { // Handle FIND_IN_SET
                $original_field = str_replace('FIND_IN_SET(', '', $field);
                $original_field = rtrim($original_field, ')');
                list($find_in_set_clause, $find_in_set_params) = $this->find_in_set($original_field, $value);
                $where_clauses[] = $find_in_set_clause;
                $params = array_merge($params, $find_in_set_params);
            } else {
                $where_clauses[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        $sql = "DELETE FROM `{$table}` WHERE " . implode(" AND ", $where_clauses);

        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * @usage
     * Helper function to generate an SQL 'IN' clause and its parameters for prepared statements.
     * This is intended to be used internally by update/delete functions or directly for custom queries.
     *
     * @param string $field The field name.
     * @param array $values An array of values for the IN clause.
     * @return array An array containing the SQL clause string and an associative array of parameters.
     */
    public function in($field, array $values) {
        if (empty($values)) {
            return ["1=0", []]; // Return a false condition if values array is empty
        }
        $placeholders = [];
        $params = [];
        foreach ($values as $index => $value) {
            $placeholder = ":{$field}_in_{$index}";
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }
        return ["`{$field}` IN (" . implode(", ", $placeholders) . ")", $params];
    }

    /**
     * @usage
     * Helper function to generate an SQL 'FIND_IN_SET' clause and its parameters for prepared statements.
     * This is intended to be used internally by update/delete functions or directly for custom queries.
     *
     * @param string $field The field name (the comma-separated string column).
     * @param string $value The value to find in the set.
     * @return array An array containing the SQL clause string and an associative array of parameters.
     */
    public function find_in_set($field, $value) {
        $placeholder = ":{$field}_find_in_set";
        return ["FIND_IN_SET({$placeholder}, `{$field}`)", [$placeholder => $value]];
    }

    /**
     * @usage
     * Truncate a table.
     * This operation removes all rows from a table, effectively resetting it.
     *
     * @param string $table_name The name of the table to truncate.
     * @return bool True on success, false on failure.
     */
    public function truncate($table_name) {
        try {
            $this->query("TRUNCATE TABLE `{$table_name}`");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @usage
     * Send email messages with MySQL access and query errors.
     * This function is called internally by `handle_error` if configured.
     * You can also call it directly for custom error notifications.
     *
     * @param string $error_message The error message to send.
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function send_error_email($error_message) {
        if (empty($this->error_email_config['to_email'])) {
            error_log("Error email not sent: 'to_email' is not configured.");
            return false;
        }

        $to = $this->error_email_config['to_email'];
        $subject = $this->error_email_config['subject'];
        $message = "A database error occurred on your application:\n\n" . $error_message . "\n\nLast Query: " . ($this->last_query ?? 'N/A');
        $headers = "From: " . $this->error_email_config['from_email'] . "\r\n" .
                   "Reply-To: " . $this->error_email_config['from_email'] . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        // Use @mail to suppress warnings if mail function fails
        if (@mail($to, $subject, $message, $headers)) {
            return true;
        } else {
            error_log("Failed to send error email to " . $to);
            return false;
        }
    }

    /**
     * @usage
     * Display the total number of queries performed during all instances of the class.
     * Note: This counts queries per instance. For a global count across multiple instances,
     * you would need a static property or a singleton pattern.
     *
     * @return int The total number of queries executed by this specific instance.
     */
    public function get_query_count() {
        return $this->query_count;
    }

    /**
     * Returns the PDO instance.
     * Useful for advanced operations not covered by the wrapper.
     *
     * @return PDO The internal PDO object.
     */
    public function get_pdo() {
        return $this->pdo;
    }
}
?>
