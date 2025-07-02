<?php

/**
 * PDO_Wrapper Class
 *
 * A robust and secure PDO wrapper for various database interactions (MySQL, PostgreSQL, SQLite, SQL Server).
 * This class provides methods for connecting, executing queries,
 * sanitizing input, retrieving results, and performing CRUD operations
 * with built-in security features like prepared statements.
 *
 * This version includes a basic Query Builder, Object Mapping capabilities,
 * dynamic identifier quoting for multi-database support, and a singleton
 * pattern for basic connection pooling.
 */
class PDO_Wrapper {
    /**
     * @var PDO The PDO database connection instance.
     */
    private $pdo;

    /**
     * @var string The name of the database driver being used (e.g., 'mysql', 'pgsql', 'sqlite', 'sqlsrv').
     */
    private $driver;

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
     * @var bool Flag to determine if debug messages should be displayed.
     */
    private $display_debug = false;

    /**
     * @var PDO_Wrapper The single instance of the PDO_Wrapper class (for singleton pattern).
     */
    private static $instance = null;

    // Query Builder properties
    private $query_parts = [];
    private $query_bindings = [];
    private $model_class = null;

    /**
     * Private Constructor: Initializes the database connection for a specified driver.
     * Implemented as private for the Singleton pattern.
     *
     * @param string $driver The database driver (e.g., 'mysql', 'pgsql', 'sqlite', 'sqlsrv').
     * @param string $host The database host (ignored for SQLite).
     * @param string $db_name The database name or path for SQLite.
     * @param string $username The database username (ignored for SQLite).
     * @param string $password The database password (ignored for SQLite).
     * @param array $options Optional PDO connection options.
     * @param bool $display_debug Flag to display debug error messages directly.
     * @throws PDOException If the connection fails or driver is unsupported.
     */
    private function __construct($driver, $host, $db_name, $username, $password, $options = [], $display_debug = false) {
        $this->driver = strtolower($driver);
        $this->display_debug = $display_debug; // Set the debug flag
        $dsn = '';

        // Set default timezone for consistency
        date_default_timezone_set('Asia/Kolkata');

        switch ($this->driver) {
            case 'mysql':
                $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
                break;
            case 'pgsql':
                $dsn = "pgsql:host=$host;dbname=$db_name";
                break;
            case 'sqlite':
                // For SQLite, $db_name is the path to the database file
                $dsn = "sqlite:$db_name";
                // Username and password are not applicable for SQLite
                $username = null;
                $password = null;
                break;
            case 'sqlsrv': // SQL Server
                $dsn = "sqlsrv:Server=$host;Database=$db_name";
                break;
            default:
                throw new PDOException("Unsupported database driver: " . $driver);
        }

        try {
            $default_options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security and performance
                PDO::ATTR_PERSISTENT         => true,                   // Enable persistent connections for basic pooling
            ];
            $this->pdo = new PDO($dsn, $username, $password, array_merge($default_options, $options));
        } catch (PDOException $e) {
            $this->handle_error($e, "Failed to connect to the database using driver '{$this->driver}'.");
            throw $e; // Re-throw the exception after handling
        }
    }

    /**
     * Prevents cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevents deserialization of the instance.
     */
    private function __wakeup() {}

    /**
     * Returns the single instance of the PDO_Wrapper class (Singleton pattern).
     *
     * @param string $driver The database driver (e.g., 'mysql', 'pgsql', 'sqlite', 'sqlsrv').
     * @param string $host The database host (ignored for SQLite).
     * @param string $db_name The database name or path for SQLite.
     * @param string $username The database username (ignored for SQLite).
     * @param string $password The database password (ignored for SQLite).
     * @param array $options Optional PDO connection options.
     * @param bool $display_debug Flag to display debug error messages directly.
     * @return PDO_Wrapper The single instance of the PDO_Wrapper.
     */
    public static function get_instance($driver, $host, $db_name, $username, $password, $options = [], $display_debug = false) {
        if (self::$instance === null) {
            self::$instance = new self($driver, $host, $db_name, $username, $password, $options, $display_debug);
        }
        return self::$instance;
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

        // Display error only if DISPLAY_DEBUG is true
        if ($this->display_debug) {
            echo "<p class='error'>Database Error: " . htmlspecialchars($error_message) . "</p>";
        }
    }

    /**
     * @usage
     * Connect to a given MySQL server.
     * This function is implicitly called by the constructor via the singleton `get_instance` method.
     * No direct usage example needed as it's part of object instantiation.
     *
     * Example:
     * $db = PDO_Wrapper::get_instance('mysql', 'localhost', 'your_db', 'your_user', 'your_password', [], true);
     */

    /**
     * @usage
     * Quote an identifier (table name, column name) based on the current database driver.
     * This ensures cross-database compatibility for identifiers.
     * For MySQL, it allows identifiers without backticks unless they contain special characters.
     *
     * @param string $identifier The identifier to quote.
     * @return string The quoted identifier.
     */
    private function quote_identifier($identifier) {
        // Check if the identifier already contains a dot (e.g., 'table.column')
        // This is a simple heuristic; more robust parsing might be needed for complex cases.
        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return implode('.', array_map([$this, 'quote_identifier'], $parts));
        }

        switch ($this->driver) {
            case 'mysql':
                // For MySQL, only quote if the identifier contains special characters or is a reserved word.
                // Simple alphanumeric and underscore identifiers do not require quoting.
                if (preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
                    return $identifier;
                }
                return "`" . str_replace("`", "``", $identifier) . "`";
            case 'pgsql':
            case 'sqlsrv':
                return "\"" . str_replace("\"", "\"\"", $identifier) . "\"";
            case 'sqlite':
                // SQLite generally doesn't require quoting unless identifiers have special characters.
                // For simplicity, we'll quote them with double quotes if they contain special chars.
                if (preg_match('/[^a-zA-Z0-9_]/', $identifier)) {
                    return "\"" . str_replace("\"", "\"\"", $identifier) . "\"";
                }
                return $identifier;
            default:
                return $identifier; // Fallback
        }
    }

    /**
     * Resets the query builder parts.
     */
    private function reset_query_builder() {
        $this->query_parts = [
            'select' => ['*'],
            'from' => null,
            'join' => [],
            'where' => [],
            'group_by' => [],
            'having' => [],
            'order_by' => [],
            'limit' => null,
            'offset' => null,
            'union' => [],
        ];
        $this->query_bindings = [];
        $this->model_class = null;
        return $this;
    }

    /**
     * @usage
     * Start a SELECT query.
     *
     * @param string|array $columns The columns to select. Defaults to '*'.
     * @return PDO_Wrapper
     */
    public function select($columns = '*') {
        $this->reset_query_builder();
        $this->query_parts['select'] = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * @usage
     * Specify the table for the query.
     *
     * @param string $table The table name.
     * @param string|null $alias Optional alias for the table.
     * @return PDO_Wrapper
     */
    public function from($table, $alias = null) {
        $from_clause = $this->quote_identifier($table);
        if ($alias) {
            $from_clause .= " AS " . $this->quote_identifier($alias);
        }
        $this->query_parts['from'] = $from_clause;
        return $this;
    }

    /**
     * @usage
     * Add a JOIN clause to the query.
     *
     * @param string $table The table to join.
     * @param string $on_clause The ON clause for the join (e.g., 'users.id = posts.user_id').
     * @param string $type The type of join (e.g., 'INNER', 'LEFT', 'RIGHT', 'FULL').
     * @return PDO_Wrapper
     */
    public function join($table, $on_clause, $type = 'INNER') {
        $this->query_parts['join'][] = strtoupper($type) . " JOIN " . $this->quote_identifier($table) . " ON " . $on_clause;
        return $this;
    }

    /**
     * @usage
     * Add a WHERE clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $operator The operator (e.g., '=', '>', '<', 'LIKE', 'IN').
     * @param mixed $value The value to compare against.
     * @return PDO_Wrapper
     */
    public function where($column, $operator, $value = null) {
        // Handle cases where operator is omitted (e.g., where('id', 1))
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = ":where_" . count($this->query_bindings);
        $this->query_parts['where'][] = $this->quote_identifier($column) . " {$operator} {$placeholder}";
        $this->query_bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * @usage
     * Add an AND WHERE clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $operator The operator.
     * @param mixed $value The value.
     * @return PDO_Wrapper
     */
    public function and_where($column, $operator, $value = null) {
        return $this->where($column, $operator, $value); // Simply calls where, as where implies AND by default
    }

    /**
     * @usage
     * Add an OR WHERE clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $operator The operator.
     * @param mixed $value The value.
     * @return PDO_Wrapper
     */
    public function or_where($column, $operator, $value = null) {
        // Handle cases where operator is omitted (e.g., or_where('id', 1))
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = ":or_where_" . count($this->query_bindings);
        $this->query_parts['where'][] = "OR " . $this->quote_identifier($column) . " {$operator} {$placeholder}";
        $this->query_bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * @usage
     * Add a WHERE IN clause.
     *
     * @param string $column The column name.
     * @param array $values An array of values.
     * @return PDO_Wrapper
     */
    public function where_in($column, array $values) {
        if (empty($values)) {
            $this->query_parts['where'][] = "1=0"; // Always false
            return $this;
        }
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ":where_in_" . count($this->query_bindings) . "_" . $index;
            $placeholders[] = $placeholder;
            $this->query_bindings[$placeholder] = $value;
        }
        $this->query_parts['where'][] = $this->quote_identifier($column) . " IN (" . implode(", ", $placeholders) . ")";
        return $this;
    }

    /**
     * @usage
     * Add a WHERE NOT IN clause.
     *
     * @param string $column The column name.
     * @param array $values An array of values.
     * @return PDO_Wrapper
     */
    public function where_not_in($column, array $values) {
        if (empty($values)) {
            $this->query_parts['where'][] = "1=1"; // Always true
            return $this;
        }
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ":where_not_in_" . count($this->query_bindings) . "_" . $index;
            $placeholders[] = $placeholder;
            $this->query_bindings[$placeholder] = $value;
        }
        $this->query_parts['where'][] = $this->quote_identifier($column) . " NOT IN (" . implode(", ", $placeholders) . ")";
        return $this;
    }

    /**
     * @usage
     * Add a WHERE LIKE clause.
     *
     * @param string $column The column name.
     * @param string $value The value with wildcards (e.g., '%search%').
     * @return PDO_Wrapper
     */
    public function where_like($column, $value) {
        $placeholder = ":where_like_" . count($this->query_bindings);
        $this->query_parts['where'][] = $this->quote_identifier($column) . " LIKE {$placeholder}";
        $this->query_bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * @usage
     * Add a WHERE NULL clause.
     *
     * @param string $column The column name.
     * @return PDO_Wrapper
     */
    public function where_null($column) {
        $this->query_parts['where'][] = $this->quote_identifier($column) . " IS NULL";
        return $this;
    }

    /**
     * @usage
     * Add a WHERE NOT NULL clause.
     *
     * @param string $column The column name.
     * @return PDO_Wrapper
     */
    public function where_not_null($column) {
        $this->query_parts['where'][] = $this->quote_identifier($column) . " IS NOT NULL";
        return $this;
    }

    /**
     * @usage
     * Add a GROUP BY clause.
     *
     * @param string|array $columns The column(s) to group by.
     * @return PDO_Wrapper
     */
    public function group_by($columns) {
        $this->query_parts['group_by'] = array_merge($this->query_parts['group_by'], (array)$columns);
        return $this;
    }

    /**
     * @usage
     * Add a HAVING clause.
     *
     * @param string $column The column name.
     * @param mixed $operator The operator.
     * @param mixed $value The value.
     * @return PDO_Wrapper
     */
    public function having($column, $operator, $value = null) {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        $placeholder = ":having_" . count($this->query_bindings);
        $this->query_parts['having'][] = $this->quote_identifier($column) . " {$operator} {$placeholder}";
        $this->query_bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * @usage
     * Add an ORDER BY clause.
     *
     * @param string $column The column to order by.
     * @param string $direction The direction ('ASC' or 'DESC').
     * @return PDO_Wrapper
     */
    public function order_by($column, $direction = 'ASC') {
        $this->query_parts['order_by'][] = $this->quote_identifier($column) . " " . strtoupper($direction);
        return $this;
    }

    /**
     * @usage
     * Set the LIMIT for the query.
     *
     * @param int $limit The maximum number of rows to return.
     * @return PDO_Wrapper
     */
    public function limit($limit) {
        $this->query_parts['limit'] = (int)$limit;
        return $this;
    }

    /**
     * @usage
     * Set the OFFSET for the query.
     *
     * @param int $offset The offset from the beginning of the result set.
     * @return PDO_Wrapper
     */
    public function offset($offset) {
        $this->query_parts['offset'] = (int)$offset;
        return $this;
    }

    /**
     * @usage
     * Add a UNION clause.
     *
     * @param string $sql The SQL query string for the UNION.
     * @param array $params Parameters for the UNION query.
     * @param bool $all Whether to use UNION ALL.
     * @return PDO_Wrapper
     */
    public function union($sql, $params = [], $all = false) {
        $this->query_parts['union'][] = [
            'sql' => $sql,
            'params' => $params,
            'all' => $all
        ];
        return $this;
    }

    /**
     * @usage
     * Set the model class for object mapping.
     *
     * @param string $class_name The name of the class to map results to.
     * @return PDO_Wrapper
     */
    public function as_object($class_name) {
        if (!class_exists($class_name)) {
            throw new InvalidArgumentException("Class '{$class_name}' does not exist for object mapping.");
        }
        $this->model_class = $class_name;
        return $this;
    }

    /**
     * @usage
     * Execute the built query and return all results.
     *
     * @return array An array of results (associative arrays or objects if `as_object()` was called).
     */
    public function get() {
        $sql = $this->build_select_query();
        $params = $this->query_bindings;

        $this->reset_query_builder(); // Reset builder state after execution

        try {
            $stmt = $this->query($sql, $params);
            if ($this->model_class) {
                return $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->model_class);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Error handled by query()
            return [];
        }
    }

    /**
     * @usage
     * Execute the built query and return the first row.
     *
     * @return mixed A single row (associative array or object) or false if no results.
     */
    public function first() {
        $this->limit(1); // Ensure only one row is fetched
        $sql = $this->build_select_query();
        $params = $this->query_bindings;

        $this->reset_query_builder(); // Reset builder state after execution

        try {
            $stmt = $this->query($sql, $params);
            if ($this->model_class) {
                $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->model_class);
                return $stmt->fetch();
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Error handled by query()
            return false;
        }
    }

    /**
     * Helper to build the SELECT query string from query parts.
     *
     * @return string The compiled SQL query.
     * @throws LogicException If 'from' table is not specified.
     */
    private function build_select_query() {
        if (!$this->query_parts['from']) {
            throw new LogicException("No table specified for SELECT query. Use from() method.");
        }

        $columns = implode(', ', array_map([$this, 'quote_identifier'], $this->query_parts['select']));
        $sql = "SELECT {$columns} FROM {$this->query_parts['from']}";

        if (!empty($this->query_parts['join'])) {
            $sql .= " " . implode(" ", $this->query_parts['join']);
        }

        if (!empty($this->query_parts['where'])) {
            // Ensure first WHERE condition doesn't start with OR
            $first_where = array_shift($this->query_parts['where']);
            if (strpos(trim(strtoupper($first_where)), 'OR') === 0) {
                $first_where = substr(trim($first_where), 2); // Remove leading 'OR'
            }
            array_unshift($this->query_parts['where'], $first_where);

            $sql .= " WHERE " . implode(" ", $this->query_parts['where']);
        }

        if (!empty($this->query_parts['group_by'])) {
            $sql .= " GROUP BY " . implode(', ', array_map([$this, 'quote_identifier'], $this->query_parts['group_by']));
        }

        if (!empty($this->query_parts['having'])) {
            $sql .= " HAVING " . implode(" AND ", $this->query_parts['having']);
        }

        if (!empty($this->query_parts['order_by'])) {
            $sql .= " ORDER BY " . implode(', ', $this->query_parts['order_by']);
        }

        if ($this->query_parts['limit'] !== null) {
            $sql .= " LIMIT " . $this->query_parts['limit'];
        }

        if ($this->query_parts['offset'] !== null) {
            $sql .= " OFFSET " . $this->query_parts['offset'];
        }

        // Handle UNION clauses
        if (!empty($this->query_parts['union'])) {
            foreach ($this->query_parts['union'] as $union_clause) {
                $union_type = $union_clause['all'] ? 'UNION ALL' : 'UNION';
                $sql .= " {$union_type} ({$union_clause['sql']})";
                $this->query_bindings = array_merge($this->query_bindings, $union_clause['params']);
            }
        }

        return $sql;
    }

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
                        $stmt->bindValue($key, $value, $type);
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
     * Filter input data function.
     * This function uses filter_var for basic filtering. For more complex scenarios,
     * consider specific validation rules or custom regex.
     *
     * @param mixed $data The data to filter.
     * @param int $filter The filter to apply (e.g., FILTER_SANITIZE_STRING, FILTER_SANITIZE_EMAIL).
     * Note: FILTER_SANITIZE_STRING is deprecated in PHP 8.1+. Use htmlspecialchars or similar for HTML output.
     * For general input, FILTER_UNSAFE_RAW with appropriate flags, or specific filters are better.
     * @param mixed $options Options for the filter.
     * @return mixed The filtered data.
     */
    public function filter($data, $filter = FILTER_UNSAFE_RAW, $options = []) {
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
     * (Relevant for some databases like PostgreSQL, leave null for MySQL).
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
     * @param string|null $model_class Optional class name for object mapping.
     * @return array An array containing all of the result set rows.
     */
    public function get_results(PDOStatement $stmt, $fetch_mode = PDO::FETCH_ASSOC, $model_class = null) {
        if ($model_class) {
            if (!class_exists($model_class)) {
                throw new InvalidArgumentException("Class '{$model_class}' does not exist for object mapping.");
            }
            return $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $model_class);
        }
        return $stmt->fetchAll($fetch_mode);
    }

    /**
     * @usage
     * Return a query result that has just one row.
     * This function fetches a single row from a PDOStatement object.
     *
     * @param PDOStatement $stmt The PDOStatement object returned by `query()`.
     * @param int $fetch_mode The fetch mode (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ).
     * @param string|null $model_class Optional class name for object mapping.
     * @return mixed The next row from a result set as an array or object, or false if there are no more rows.
     */
    public function get_row(PDOStatement $stmt, $fetch_mode = PDO::FETCH_ASSOC, $model_class = null) {
        if ($model_class) {
            if (!class_exists($model_class)) {
                throw new InvalidArgumentException("Class '{$model_class}' does not exist for object mapping.");
            }
            $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $model_class);
            return $stmt->fetch();
        }
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
        // This list is primarily for MySQL-specific functions and common SQL injection patterns.
        // For other databases, this list might need to be expanded or modified.
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
     * This method uses driver-specific queries for better compatibility.
     *
     * @param string $table_name The name of the table to check.
     * @return bool True if the table exists, false otherwise.
     */
    public function table_exists($table_name) {
        try {
            $sql = "";
            $params = [':table_name' => $table_name];

            switch ($this->driver) {
                case 'mysql':
                    $sql = "SHOW TABLES LIKE :table_name";
                    break;
                case 'pgsql':
                    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table_name";
                    break;
                case 'sqlite':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:table_name";
                    break;
                case 'sqlsrv': // SQL Server
                    $sql = "SELECT 1 FROM sys.tables WHERE name = :table_name";
                    break;
                default:
                    error_log("Unsupported PDO driver for table_exists: " . $this->driver);
                    return false;
            }
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking table existence for '$table_name' with driver '{$this->driver}': " . $e->getMessage());
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
            $where_clauses[] = $this->quote_identifier($field) . " = :{$field}";
            $params[":{$field}"] = $value;
        }
        $sql = "SELECT 1 FROM " . $this->quote_identifier($table) . " WHERE " . implode(' AND ', $where_clauses) . " LIMIT 1";

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
        $quoted_fields = array_map([$this, 'quote_identifier'], $fields);
        $placeholders = array_map(function($field) { return ":{$field}"; }, $fields);

        $sql = "INSERT INTO " . $this->quote_identifier($table) . " (" . implode(", ", $quoted_fields) . ") VALUES (" . implode(", ", $placeholders) . ")";

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
        $quoted_fields = array_map([$this, 'quote_identifier'], $fields);
        $values_clauses = [];
        $all_params = [];
        $param_counter = 0;

        foreach ($data as $row) {
            $current_placeholders = [];
            $current_params = [];
            foreach ($fields as $field) {
                $unique_placeholder = ":{$field}_{$param_counter}";
                $current_placeholders[] = $unique_placeholder;
                $current_params[$unique_placeholder] = $row[$field] ?? null;
            }
            $values_clauses[] = "(" . implode(", ", $current_placeholders) . ")";
            $all_params = array_merge($all_params, $current_params);
            $param_counter++;
        }

        $sql = "INSERT INTO " . $this->quote_identifier($table) . " (" . implode(", ", $quoted_fields) . ") VALUES " . implode(", ", $values_clauses);

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
            $set_clauses[] = $this->quote_identifier($field) . " = :set_{$field}";
            $params[":set_{$field}"] = $value;
        }

        $where_clauses = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) { // Handle IN clause
                list($in_clause, $in_params) = $this->in($field, $value);
                $where_clauses[] = $in_clause;
                $params = array_merge($params, $in_params);
            } elseif (strpos($field, 'FIND_IN_SET') === 0) { // Handle FIND_IN_SET (MySQL specific)
                $original_field = str_replace('FIND_IN_SET(', '', $field);
                $original_field = rtrim($original_field, ')');
                list($find_in_set_clause, $find_in_set_params) = $this->find_in_set($original_field, $value);
                $where_clauses[] = $find_in_set_clause;
                $params = array_merge($params, $find_in_set_params);
            } else {
                $where_clauses[] = $this->quote_identifier($field) . " = :where_{$field}";
                $params[":where_{$field}"] = $value;
            }
        }

        $sql = "UPDATE " . $this->quote_identifier($table) . " SET " . implode(", ", $set_clauses) . " WHERE " . implode(" AND ", $where_clauses);

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
            } elseif (strpos($field, 'FIND_IN_SET') === 0) { // Handle FIND_IN_SET (MySQL specific)
                $original_field = str_replace('FIND_IN_SET(', '', $field);
                $original_field = rtrim($original_field, ')');
                list($find_in_set_clause, $find_in_set_params) = $this->find_in_set($original_field, $value);
                $where_clauses[] = $find_in_set_clause;
                $params = array_merge($params, $find_in_set_params);
            } else {
                $where_clauses[] = $this->quote_identifier($field) . " = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        $sql = "DELETE FROM " . $this->quote_identifier($table) . " WHERE " . implode(" AND ", $where_clauses);

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
        return [$this->quote_identifier($field) . " IN (" . implode(", ", $placeholders) . ")", $params];
    }

    /**
     * @usage
     * Helper function to generate an SQL 'FIND_IN_SET' clause and its parameters for prepared statements.
     * This is intended to be used internally by update/delete functions or directly for custom queries.
     * Note: This function is MySQL-specific. For other databases, consider alternative data structures.
     *
     * @param string $field The field name (the comma-separated string column).
     * @param string $value The value to find in the set.
     * @return array An array containing the SQL clause string and an associative array of parameters.
     */
    public function find_in_set($field, $value) {
        $placeholder = ":{$field}_find_in_set";
        return ["FIND_IN_SET({$placeholder}, " . $this->quote_identifier($field) . ")", [$placeholder => $value]];
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
            $this->query("TRUNCATE TABLE " . $this->quote_identifier($table_name));
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @usage
     * Execute a stored procedure.
     *
     * @param string $procedure_name The name of the stored procedure.
     * @param array $params An associative array of parameters for the procedure.
     * @return PDOStatement The PDOStatement object on success.
     * @throws PDOException If the procedure execution fails.
     */
    public function call_procedure($procedure_name, $params = []) {
        $placeholders = [];
        $call_params = [];
        foreach ($params as $key => $value) {
            $placeholder = ":param_{$key}";
            $placeholders[] = $placeholder;
            $call_params[$placeholder] = $value;
        }
        $sql = "CALL " . $this->quote_identifier($procedure_name) . "(" . implode(', ', $placeholders) . ")";
        return $this->query($sql, $call_params);
    }

    /**
     * @usage
     * Create a new table based on a schema definition.
     * This is a basic helper, not a full migration system.
     *
     * @param string $table_name The name of the table to create.
     * @param array $columns An associative array defining columns (column_name => definition_string).
     * Example: ['id' => 'INT AUTO_INCREMENT PRIMARY KEY', 'name' => 'VARCHAR(255) NOT NULL']
     * Note: AUTO_INCREMENT/SERIAL/PRIMARY KEY syntax varies by DB.
     * @return bool True on success, false on failure.
     */
    public function create_table($table_name, array $columns) {
        if (empty($columns)) {
            return false;
        }

        $column_definitions = [];
        foreach ($columns as $column_name => $definition) {
            $column_definitions[] = $this->quote_identifier($column_name) . " " . $definition;
        }

        $sql = "CREATE TABLE " . $this->quote_identifier($table_name) . " (" . implode(", ", $column_definitions) . ")";

        try {
            $this->query($sql);
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

/**
 * Base Model Class for Object Mapping.
 * Extend this class for your database entities.
 */
class BaseModel {
    // You can define common properties or methods here
    // For example, a constructor to hydrate properties from an array
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

// Example User Model (extend BaseModel)
class User extends BaseModel {
    public $id;
    public $name;
    public $email;
    public $status;
    public $skills;
    public $created_at;

    // You can add custom methods specific to the User model here
    public function getFullName() {
        return $this->name; // Simple example
    }
}

// Example Product Model (extend BaseModel)
class Product extends BaseModel {
    public $product_id;
    public $product_name;
    public $price;

    public function getFormattedPrice() {
        return '$' . number_format($this->price, 2);
    }
}
