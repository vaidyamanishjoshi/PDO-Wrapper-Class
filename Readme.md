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
   
   Set DISPLAY_DEBUG to 'true' during development to see database errors directly, but set it to false in production.  


##  db.php will be like below
   
		define('DBTYPE', 'mysql'); // or 'pgsql', 'sqlite', 'sqlsrv'  
		define('HST', 'localhost');  
		define('DBN', 'your_database_name'); // IMPORTANT: Change this to your actual database name or SQLite file path  
		define('USR', 'your_username'); // IMPORTANT: Change this to your actual database username (ignored for SQLite)  
		define('PWD', 'your_password'); // IMPORTANT: Change this to your actual database password (ignored for SQLite)

		define('DISPLAY_DEBUG', true); // Set to false in production

		require_once 'pdo_wrapper.php';

			   try {  
					// Create a new instance for your primary database  
					$db = new PDO_Wrapper(DBTYPE, HST, DBN, USR, PWD, [], DISPLAY_DEBUG);

					
## Example: Create another instance for a different database (e.g., logs)  
 			
		define('DBTYPE_LOGS', 'mysql');  
		define('HST_LOGS', 'localhost');  
		define('DBN_LOGS', 'your_logs_database');  
		define('USR_LOGS', 'your_logs_username');  
		define('PWD_LOGS', 'your_logs_password');  
		$db_logs = new PDO_Wrapper(DBTYPE_LOGS, HST_LOGS, DBN_LOGS, USR_LOGS, PWD_LOGS, [], DISPLAY_DEBUG);  
			   

			   } catch (PDOException $e) {  
				  die("Database connection failed.");  
			  }
			 

3. Database Schema:  

   Create the necessary tables in your database. Here are example schemas for users and products tables: 

   
##   **For MySQL:**

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
##   **For PostgreSQL:**
   
			
		CREATE TABLE IF NOT EXISTS "users" (  
					   "id" SERIAL PRIMARY KEY,  
					   "name" VARCHAR(255) NOT NULL,  
					   "email" VARCHAR(255) UNIQUE NOT NULL,  
					   "status" VARCHAR(50) DEFAULT 'active',  
					   "skills" VARCHAR(255) DEFAULT NULL,  
					   "created_at" TIMESTAMP DEFAULT CURRENT\_TIMESTAMP  
				);

		CREATE TABLE IF NOT EXISTS "products" (  
					   "product_id" SERIAL PRIMARY KEY,  
					   "product_name" VARCHAR(255) NOT NULL,  
					   "price" DECIMAL(10, 2) NOT NULL  
				);
				

##   **For SQLite:** 
				
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
				

## **Usage Examples**

Once configured, you can use your $db object (or any other PDO_Wrapper instance) to interact with your database.

Examples are gicen in documentation example html file



## **License**

All Examples provided in html file

## **Contributing**

Feel free to contribute to this PDO Wrapper class by opening issues or submitting pull requests.

## **License**

		\[Specify your license here, e.g., MIT License\]