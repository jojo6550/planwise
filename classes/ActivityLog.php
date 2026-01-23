<?php
/**
 * ActivityLog Class
 * Handles activity logging for security and audit purposes
 * CS334 Module 3 - Activity logs (10 marks)
 */

require_once __DIR__ . '/Database.php';

class ActivityLog
{
    private $db;

    /**
     * Constructor - Initialize Database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log an activity
     *
     * @param int $userId User ID performing the action
     * @param string $action Action performed
     * @param string $description Detailed description of the action
     * @return bool Success status
     */
    public function log(int $userId, string $action, string $description = ''): bool
    {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at)
                    VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())";

            $params = [
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip_address' => $this->getIpAddress(),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ];

            $this->db->insert($sql, $params);
            return true;

        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all activity logs (admin only)
     *
     * @param int $limit Number of records to retrieve
     * @param int $offset Offset for pagination
     * @return array Activity logs
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get activity logs failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity logs for a specific user
     *
     * @param int $userId User ID
     * @param int $limit Number of records to retrieve
     * @return array Activity logs
     */
    public function getByUser(int $userId, int $limit = 20): array
    {
        try {
            $sql = "SELECT * FROM activity_logs
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get user activity logs failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of activity logs
     *
     * @return int Total count
     */
    public function getTotalCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM activity_logs";
            $result = $this->db->fetch($sql);
            return (int)($result['total'] ?? 0);

        } catch (Exception $e) {
            error_log("Get activity log count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Log activity to file
     *
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $description Description
     */
    private function logToFile(int $userId, string $action, string $description): void
    {
        $logFile = __DIR__ . '/../logs/activity.log';
        $logDir = dirname($logFile);

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getIpAddress();
        $logEntry = "[{$timestamp}] User:{$userId} Action:{$action} IP:{$ip} - {$description}" . PHP_EOL;

        // Append to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function getIpAddress(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'Unknown';
    }
}
