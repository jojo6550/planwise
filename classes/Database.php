<?php
/**
 * Database Class
 * PDO wrapper for secure database operations
 */

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        // Lazy loading: connect only when needed
    }

    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish PDO connection
     */
    private function connect()
    {
        // Check for required configuration
        if (empty($this->config['host']) || empty($this->config['database']) ||
            empty($this->config['username'])) {
            $errorMsg = "Database configuration error: Missing required environment variables (DB_HOST, DB_NAME, DB_USER)";
            $this->logToFile($errorMsg);
            throw new Exception("Database configuration error");
        }

        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['driver'],
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Log successful connection
            $this->logToFile("Database connection established successfully to {$this->config['host']}:{$this->config['port']}/{$this->config['database']}");

        } catch (PDOException $e) {
            $errorMsg = "Database connection failed: " . $e->getMessage() . " (Host: {$this->config['host']}, Port: {$this->config['port']}, DB: {$this->config['database']})";
            $this->logToFile($errorMsg);
            error_log($errorMsg);
            throw new Exception("Could not connect to database. Please check your database configuration and ensure the server is running.");
        }
    }

    /**
     * Log message to database log file
     *
     * @param string $message
     */
    private function logToFile($message)
    {
        $logFile = __DIR__ . '/../logs/database.log';
        $logDir = dirname($logFile);

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get PDO instance
     *
     * @return PDO
     */
    public function getConnection()
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Execute a query and return statement
     * 
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch all rows
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     * 
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Insert record and return last insert ID
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    public function insert($sql, $params = [])
    {
        error_log("Database::insert - SQL: " . $sql);
        error_log("Database::insert - Params: " . json_encode($params));
        $this->query($sql, $params);
        $conn = $this->getConnection();
        $lastId = $conn->lastInsertId();
        error_log("Database::insert - Last insert ID: " . $lastId);
        return $lastId;
    }

    /**
     * Update records
     * 
     * @param string $sql
     * @param array $params
     * @return int Number of affected rows
     */
    public function update($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     * 
     * @param string $sql
     * @param array $params
     * @return int Number of affected rows
     */
    public function delete($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $conn = $this->getConnection();
        return $conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        $conn = $this->getConnection();
        return $conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $conn = $this->getConnection();
        return $conn->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
