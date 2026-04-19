<?php
// Database connection (singleton)

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            error_log("DB connection failed: " . $e->getMessage());
            throw new Exception("Could not connect to database.");
        }
    }

    public function getConnection()
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed.");
        }
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($sql, $params = [])
    {
        $this->query($sql, $params);
        return $this->getConnection()->lastInsertId();
    }

    public function update($sql, $params = [])
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($sql, $params = [])
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }
}
