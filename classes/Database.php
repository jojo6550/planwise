<?php
/**
 * Database Class
 * PDO wrapper for secure database operations with SQL injection prevention
 */

require_once __DIR__ . '/../helpers/sanitize.php';

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;
    
    // Track if we're in development mode for enhanced logging
    private $debugMode = false;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->debugMode = defined('DEBUG_MODE') && DEBUG_MODE;
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
     * Log security-related query events
     *
     * @param string $sql
     * @param array $params
     * @param string $eventType
     */
    private function logSecurityEvent($sql, $params, $eventType)
    {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] [{$eventType}] IP:{$ip}" . PHP_EOL;
        $logEntry .= "  SQL: {$sql}" . PHP_EOL;
        $logEntry .= "  Params: " . json_encode($params) . PHP_EOL;
        $logEntry .= "  User-Agent: {$userAgent}" . PHP_EOL . PHP_EOL;

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
     * Sanitize and validate parameters before query execution
     * This provides defense in depth - the primary defense is parameterized queries
     *
     * @param array $params
     * @return array Sanitized parameters
     */
    private function sanitizeParams($params)
    {
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            // Keep the parameter name as-is (should be :param_name format)
            // but sanitize the value
            if (is_int($value)) {
                $sanitized[$key] = $value;
            } elseif (is_float($value)) {
                $sanitized[$key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value ? 1 : 0;
            } elseif (is_null($value)) {
                $sanitized[$key] = null;
            } elseif (is_array($value)) {
                // Arrays should be JSON encoded
                $sanitized[$key] = json_encode($value);
            } else {
                // For strings, trim and remove null bytes
                $sanitized[$key] = str_replace("\0", '', trim($value));
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate that the SQL query doesn't contain suspicious patterns
     *
     * @param string $sql
     * @return bool
     */
    private function validateQuery($sql)
    {
        // Convert to uppercase for pattern matching
        $sqlUpper = strtoupper($sql);
        
        // Check for multiple statements (shouldn't be allowed)
        if (preg_match('/;\s*[a-zA-Z]/', $sql)) {
            $this->logSecurityEvent($sql, [], 'MULTIPLE_STATEMENTS_BLOCKED');
            return false;
        }
        
        // Check for common SQL injection patterns
        $dangerousPatterns = [
            '/\bUNION\s+ALL\b/i',
            '/\bUNION\s+SELECT\b/i',
            '/\bINTO\s+OUTFILE\b/i',
            '/\bLOAD_FILE\b/i',
            '/\bEXEC\b/i',
            '/\bXP_\w+\b/i',
            '/\bWAITFOR\s+DELAY\b/i',
            '/\bBENCHMARK\b/i',
            '/--$/',  // SQL comment at end
            '/#.*$/', // MySQL comment
            '/\/\*.*\*\//', // Block comment
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                $this->logSecurityEvent($sql, [], 'SUSPICIOUS_PATTERN_BLOCKED');
                return false;
            }
        }
        
        return true;
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
            // Validate the query
            if (!$this->validateQuery($sql)) {
                throw new Exception("Invalid query detected. Potential SQL injection attempt blocked.");
            }
            
            // Sanitize parameters for defense in depth
            $params = $this->sanitizeParams($params);
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Log query in debug mode (not production for security)
            if ($this->debugMode) {
                $this->logToFile("Query executed: " . substr($sql, 0, 200));
            }
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            
            // Log failed query attempt
            $this->logSecurityEvent($sql, $params, 'QUERY_FAILED');
            
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
        $this->query($sql, $params);
        $conn = $this->getConnection();
        return $conn->lastInsertId();
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
     * Safely cast a value to integer (for IDs and numeric parameters)
     * Use this for parameters that must be integers
     *
     * @param mixed $value
     * @param int $default
     * @return int
     */
    public static function castInt($value, int $default = 0): int
    {
        return sanitizeInt($value, $default);
    }
    
    /**
     * Safely cast a value to string
     * Use this for text parameters
     *
     * @param mixed $value
     * @return string
     */
    public static function castString($value): string
    {
        return sanitizeString($value);
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
