<?php
// read variables from .env
require_once "config.php";

class Database {
    private $username;
    private $password;
    private $host;
    private $database;
    private $connection = null;
    
    // Singleton instance
    private static $instance = null;

    private function __construct()
    {
        $this->username = USERNAME;
        $this->password = PASSWORD;
        $this->host = HOST;
        $this->database = DATABASE;
    }
    
    /**
     * Get singleton instance of Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get database connection (reuses existing connection)
     */
    public function connect(): PDO
    {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO(
                    "pgsql:host=$this->host;port=5432;dbname=$this->database",
                    $this->username,
                    $this->password,
                    ["sslmode"  => "prefer"]
                );

                // set the PDO error mode to exception
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Use prepared statements by default for security
                $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }
            catch(PDOException $e) {
                // Log error securely without exposing details to user
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection error. Please try again later.");
            }
        }
        return $this->connection;
    }
    
    /**
     * Disconnect from database
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}