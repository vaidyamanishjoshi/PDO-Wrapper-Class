# **PDO Wrapper Class**

A robust, secure, and easy-to-use PHP PDO wrapper designed to simplify database interactions (MySQL, PostgreSQL, SQLite, SQL Server). This class provides a clean API for common database operations, including a fluent Query Builder, basic ORM-like object mapping, and built-in security features like prepared statements and input filtering.

## **Features**

* **Multi-Database Support:** Works seamlessly with MySQL, PostgreSQL, SQLite, and SQL Server.  
* **Flexible Connections:** Supports creating single or multiple database connections by directly instantiating the PDO\_Wrapper class.  
* **Prepared Statements:** All queries use prepared statements to prevent SQL injection attacks.  
* **Basic CRUD Operations:** Simple methods for insert(), insert\_multi(), update(), and delete().  
* **Fluent Query Builder:** Chainable methods like select(), from(), where(), join(), orderBy(), limit(), offset(), group\_by(), having(), and union() for constructing complex queries programmatically.  
* **Object Mapping (ORM-like):** Map query results directly to custom PHP objects for an object-oriented data access layer.  
* **Input Filtering:** Basic data sanitization using filter\_var.  
* **Error Handling:** Configurable error logging and email notifications on database errors.  
* **Utility Methods:** Functions to check table/record existence, get last insert ID, count rows/columns, and execute stored procedures.  
* **Dynamic Identifier Quoting:** Automatically quotes table and column names based on the database driver, allowing unquoted simple identifiers for MySQL.

## **Installation**

1. Download Files:  
   Download pdo\_wrapper.php and db.php into your project directory.  
2. Configure db.php:  
   
   Open db.php and update the database connection constants (DBTYPE, HST, DBN, USR, PWD) with your actual database credentials.  
   
   Set DISPLAY\_DEBUG to true during development to see database errors directly, but set it to false in production.  
   db.php
##   
			 
 			   define('DBTYPE', 'mysql'); // or 'pgsql', 'sqlite', 'sqlsrv'  
			   define('HST', 'localhost');  
			   define('DBN', 'your\_database\_name'); // IMPORTANT: Change this to your actual database name or SQLite file path  
			   define('USR', 'your\_username'); // IMPORTANT: Change this to your actual database username (ignored for SQLite)  
			   define('PWD', 'your\_password'); // IMPORTANT: Change this to your actual database password (ignored for SQLite)

			   define('DISPLAY\_DEBUG', true); // Set to false in production

			   require\_once 'pdo\_wrapper.php';

			   try {  
					// Create a new instance for your primary database  
					$db \= new PDO\_Wrapper(DBTYPE, HST, DBN, USR, PWD, \[\], DISPLAY\_DEBUG);
					

	Example: Create another instance for a different database (e.g., logs)  
			  ## 
					   define('DBTYPE\_LOGS', 'mysql');  
					   define('HST\_LOGS', 'localhost');  
					   define('DBN\_LOGS', 'your\_logs\_database');  
					   define('USR\_LOGS', 'your\_logs\_username');  
					   define('PWD\_LOGS', 'your\_logs\_password');  
					   $db\_logs \= new PDO\_Wrapper(DBTYPE\_LOGS, HST\_LOGS, DBN\_LOGS, USR\_LOGS, PWD\_LOGS, \[\], DISPLAY\_DEBUG);  
			   

			   } catch (PDOException $e) {  
				  die("Database connection failed.");  
			  }
			 

3. Database Schema:  
   Create the necessary tables in your database. Here are example schemas for users and products tables:  
   **For MySQL:**  
			    /\*
				CREATE TABLE IF NOT EXISTS \`users\` (  
					   \`id\` INT AUTO\_INCREMENT PRIMARY KEY,  
					   \`name\` VARCHAR(255) NOT NULL,  
					   \`email\` VARCHAR(255) UNIQUE NOT NULL,  
					   \`status\` VARCHAR(50) DEFAULT 'active',  
					   \`skills\` VARCHAR(255) DEFAULT NULL,  
					   \`created\_at\` TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
				);

				CREATE TABLE IF NOT EXISTS \`products\` (  
					   \`product\_id\` INT AUTO\_INCREMENT PRIMARY KEY,  
					   \`product\_name\` VARCHAR(255) NOT NULL,  
					   \`price\` DECIMAL(10, 2\) NOT NULL  
				);
				 \*/

   **For PostgreSQL:** 
				/\*
				CREATE TABLE IF NOT EXISTS "users" (  
					   "id" SERIAL PRIMARY KEY,  
					   "name" VARCHAR(255) NOT NULL,  
					   "email" VARCHAR(255) UNIQUE NOT NULL,  
					   "status" VARCHAR(50) DEFAULT 'active',  
					   "skills" VARCHAR(255) DEFAULT NULL,  
					   "created\_at" TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
				);

				CREATE TABLE IF NOT EXISTS "products" (  
					   "product\_id" SERIAL PRIMARY KEY,  
					   "product\_name" VARCHAR(255) NOT NULL,  
					   "price" DECIMAL(10, 2\) NOT NULL  
				);
				 \*/

   **For SQLite:** 
				/\*
				CREATE TABLE IF NOT EXISTS users (  
					   id INTEGER PRIMARY KEY AUTOINCREMENT,  
					   name TEXT NOT NULL,  
					   email TEXT UNIQUE NOT NULL,  
					   status TEXT DEFAULT 'active',  
					   skills TEXT DEFAULT NULL,  
					   created\_at TEXT DEFAULT CURRENT\_TIMESTAMP  
				);

				CREATE TABLE IF NOT EXISTS products (  
					   product\_id INTEGER PRIMARY KEY AUTOINCREMENT,  
					   product\_name TEXT NOT NULL,  
					   price REAL NOT NULL  
				);
				 \*/

## **Usage Examples**

Once configured, you can use your $db object (or any other PDO\_Wrapper instance) to interact with your database.

### **1\. Getting the Database Instance**

Simply instantiate the PDO\_Wrapper class:
			/\*
			<?php  
				require_once 'db.php'; // This initializes $db

			// If you need another connection:  
			 $db_secondary = new PDO\_Wrapper('pgsql', 'another_host', 'another\_db', 'user', 'pass', [], true);  
			?>
			\*/

### **2\. Basic CRUD Operations**

#### **Insert Single Record**
			/\*
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
			\*/

#### **Insert Multiple Records**
			/\*
			<?php  
			$products_data = [  
				['product\_name' => 'Laptop', 'price' => 1200.00],  
				['product\_name' => 'Mouse', 'price' => 25.50],  
			];  
			$affected_rows \= $db->insert_multi('products', $products_data);  
			echo "Inserted {$affected_rows} products.";  
			?\>
			\*/

#### **Update Records**

			\<?php  
			// Simple update  
			$update\_data \= \['status' \=\> 'inactive'\];  
			$conditions \= \['email' \=\> 'john.doe@example.com'\];  
			$affected\_rows \= $db-\>update('users', $update\_data, $conditions);  
			echo "Updated {$affected\_rows} user(s).";

			// Update with IN clause  
			$update\_data\_in \= \['status' \=\> 'pending'\];  
			$conditions\_in \= \['id' \=\> \[1, 2, 3\]\];  
			$affected\_rows\_in \= $db-\>update('users', $update\_data\_in, $conditions\_in);  
			echo "Updated {$affected\_rows\_in} user(s).";  
			?\>

#### **Delete Records**

			\<?php  
			// Simple delete  
			$conditions \= \['email' \=\> 'john.doe@example.com'\];  
			$affected\_rows \= $db-\>delete('users', $conditions);  
			echo "Deleted {$affected\_rows} user(s).";

			// Delete with IN clause  
			$conditions\_in \= \['product\_id' \=\> \[1, 2\]\];  
			$affected\_rows\_in \= $db-\>delete('products', $conditions\_in);  
			echo "Deleted {$affected\_rows\_in} product(s).";  
			?\>

### **3\. Query Builder**

The Query Builder allows you to construct complex SELECT queries fluently.

			\<?php  
			// Select all active users, ordered by name  
			$active\_users \= $db-\>select()  
							   \-\>from('users')  
							   \-\>where('status', 'active')  
							   \-\>orderBy('name', 'ASC')  
							   \-\>get();

			// Select specific product details, limit to 5  
			$products\_info \= $db-\>select('product\_name AS name, price')  
								\-\>from('products')  
								\-\>limit(5)  
								\-\>get();

			// Find a user by ID or email  
			$user \= $db-\>select()  
					   \-\>from('users')  
					   \-\>where('id', 1\)  
					   \-\>or\_where('email', 'another@example.com')  
					   \-\>first();

			// Select users with 'PHP' in their skills (MySQL FIND\_IN\_SET example)  
			$php\_devs \= $db-\>select()  
						   \-\>from('users')  
						   \-\>where\_like('skills', '%PHP%') // Or using FIND\_IN\_SET for MySQL: \-\>where('FIND\_IN\_SET(skills)', 'PHP')  
						   \-\>get();

			// Get paginated results (e.g., page 2, 10 items per page)  
			$page \= 2;  
			$perPage \= 10;  
			$paginated\_users \= $db-\>select()  
								  \-\>from('users')  
								  \-\>limit($perPage)  
								  \-\>offset(($page \- 1\) \* $perPage)  
								  \-\>get();  
			?\>

### **4\. Raw Queries and Results**

For more direct control, use query() to execute any SQL and get\_results()/get\_row() to fetch data.

			\<?php  
			// Execute a raw SELECT query  
			$stmt \= $db-\>query("SELECT id, name, email FROM users WHERE status \= :status", \[':status' \=\> 'active'\]);  
			$users \= $db-\>get\_results($stmt);

			// Fetch a single row  
			$single\_user\_stmt \= $db-\>query("SELECT \* FROM users WHERE id \= :id", \[':id' \=\> 1\]);  
			$user\_data \= $db-\>get\_row($single\_user\_stmt);

			// Get number of rows/columns  
			echo "Rows affected by last query: " . $db-\>num\_rows($stmt);  
			echo "Columns in result set: " . $db-\>num\_cols($stmt);  
			echo "Last inserted ID: " . $db-\>last\_insert\_id();  
			?\>

### **5\. Object Mapping (ORM-like)**

Define simple model classes to map database rows to objects.

			\<?php  
			// In pdo\_wrapper.php or a separate models.php file:  
			/\*  
			class BaseModel {  
				public function \_\_construct(array $data \= \[\]) {  
					foreach ($data as $key \=\> $value) {  
						if (property\_exists($this, $key)) {  
							$this-\>$key \= $value;  
						}  
					}  
				}  
			}

			class User extends BaseModel {  
				public $id;  
				public $name;  
				public $email;  
				public $status;  
				public function getFullName() { return $this-\>name; }  
			}

			class Product extends BaseModel {  
				public $product\_id;  
				public $product\_name;  
				public $price;  
				public function getFormattedPrice() { return '$' . number\_format($this-\>price, 2); }  
			}  
			\*/

			// Using Query Builder with object mapping  
			$user\_objects \= $db-\>select()-\>from('users')-\>where('status', 'active')-\>as\_object('User')-\>get();  
			foreach ($user\_objects as $user) {  
				echo "User: " . $user-\>getFullName() . " (Email: " . $user-\>email . ")";  
			}

			// Using raw query with object mapping  
			$stmt \= $db-\>query("SELECT \* FROM products WHERE product\_id \= :id", \[':id' \=\> 1\]);  
			$product\_object \= $db-\>get\_row($stmt, PDO::FETCH\_ASSOC, 'Product');  
			if ($product\_object instanceof Product) {  
				echo "Product: " . $product\_object-\>product\_name . ", Price: " . $product\_object-\>getFormattedPrice();  
			}  
			?\>

### **6\. Helpers and Utilities**

#### **Input Filtering**

			\<?php  
			$raw\_input \= "\<p\>Hello \<script\>alert('xss')\</script\> World\!\</p\>";  
			$filtered\_input \= $db-\>filter($raw\_input);  
			echo "Filtered: " . htmlspecialchars($filtered\_input); // Output: Hello \<script\>alert('xss')\</script\> World\!

			$email\_input \= "test@example.com; DROP TABLE users;";  
			$filtered\_email \= $db-\>filter($email\_input, FILTER\_SANITIZE\_EMAIL);  
			echo "Filtered Email: " . $filtered\_email; // Output: test@example.com  
			?\>

#### **Check Table/Record Existence**

			\<?php  
			if ($db-\>table\_exists('users')) {  
				echo "Table 'users' exists.";  
			}

			if ($db-\>record\_exists('users', \['email' \=\> 'john.doe@example.com'\])) {  
				echo "User 'john.doe@example.com' exists.";  
			}  
			?\>

#### **Truncate Table**

			\<?php  
			if ($db-\>truncate('products')) {  
				echo "Table 'products' truncated.";  
			}  
			?\>

#### **Execute Stored Procedure**

			\<?php  
			// Assuming a MySQL stored procedure:  
			// DELIMITER //  
			// CREATE PROCEDURE GetUserById(IN userId INT)  
			// BEGIN  
			//     SELECT id, name, email FROM users WHERE id \= userId;  
			// END //  
			// DELIMITER ;

			$stmt\_proc \= $db-\>call\_procedure('GetUserById', \['userId' \=\> 1\]);  
			$user\_from\_proc \= $db-\>get\_row($stmt\_proc);  
			print\_r($user\_from\_proc);  
			?\>

### **7\. Configuration and Debugging**

#### **Set Error Email Configuration**

			\<?php  
			$db-\>set\_error\_email\_config(\[  
				'send\_on\_error' \=\> true,  
				'to\_email' \=\> 'your\_admin\_email@example.com',  
				'from\_email' \=\> 'app-errors@yourdomain.com',  
				'subject' \=\> 'URGENT: Application Database Error',  
			\]);  
			// Now, if a PDOException occurs, an email will be sent.  
			?\>

#### **Get Query Count**

			\<?php  
			// Perform some queries...  
			$db-\>query("SELECT 1 FROM users");  
			$db-\>insert('products', \['product\_name' \=\> 'New Gadget', 'price' \=\> 50.00\]);

			echo "Total queries executed: " . $db-\>get\_query\_count();  
			?\>

## **License**

All Examples provided in html file

## **Contributing**

Feel free to contribute to this PDO Wrapper class by opening issues or submitting pull requests.

## **License**

		\[Specify your license here, e.g., MIT License\]