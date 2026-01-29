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

    // Activity Action Constants
    public const ACTION_USER_LOGIN = 'user_login';
    public const ACTION_USER_LOGOUT = 'user_logout';
    public const ACTION_USER_REGISTERED = 'user_registered';
    public const ACTION_USER_CREATED = 'user_created';
    public const ACTION_USER_UPDATED = 'user_updated';
    public const ACTION_USER_DELETED = 'user_deleted';
    public const ACTION_USER_STATUS_UPDATED = 'user_status_updated';
    public const ACTION_PASSWORD_RESET = 'password_reset_completed';
    public const ACTION_LESSON_PLAN_CREATED = 'lesson_plan_created';
    public const ACTION_LESSON_PLAN_UPDATED = 'lesson_plan_updated';
    public const ACTION_LESSON_PLAN_DELETED = 'lesson_plan_deleted';
    public const ACTION_LESSON_PLAN_VIEWED = 'lesson_plan_viewed';
    public const ACTION_LESSON_PLAN_EXPORTED_PDF = 'pdf_exported';
    public const ACTION_LESSON_PLAN_EXPORTED_WORD = 'word_exported';
    public const ACTION_LESSON_PLAN_SAVED_PDF = 'pdf_saved';
    public const ACTION_LESSON_PLAN_SAVED_WORD = 'word_saved';
    public const ACTION_LESSON_PLAN_IMPORTED = 'lesson_plan_imported';
    public const ACTION_QR_CODE_GENERATED = 'qr_code_generated';
    public const ACTION_FILE_UPLOADED = 'file_uploaded';
    public const ACTION_FILE_DOWNLOADED = 'file_downloaded';
    public const ACTION_FILE_DELETED = 'file_deleted';

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
     * @param string $action Action performed (use constants for standard actions)
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
            
            // Also log to file for backup
            $this->logToFile($userId, $action, $description);
            
            return true;

        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all activity logs with optional filtering (admin only)
     *
     * @param array $filters Filter options (user_id, action, date_from, date_to, search)
     * @param int $limit Number of records to retrieve
     * @param int $offset Offset for pagination
     * @return array Activity logs
     */
    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $where = [];
            $params = [];

            // Filter by user ID
            if (!empty($filters['user_id'])) {
                $where[] = "al.user_id = :user_id";
                $params[':user_id'] = (int)$filters['user_id'];
            }

            // Filter by action type
            if (!empty($filters['action'])) {
                $where[] = "al.action = :action";
                $params[':action'] = $filters['action'];
            }

            // Filter by date range
            if (!empty($filters['date_from'])) {
                $where[] = "al.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "al.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            // Search in action or description
            if (!empty($filters['search'])) {
                $where[] = "(al.action LIKE :search OR al.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->getConnection()->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
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
     * Get total count of activity logs with optional filtering
     *
     * @param array $filters Filter options
     * @return int Total count
     */
    public function getTotalCount(array $filters = []): int
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = "user_id = :user_id";
                $params[':user_id'] = (int)$filters['user_id'];
            }

            if (!empty($filters['action'])) {
                $where[] = "action = :action";
                $params[':action'] = $filters['action'];
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT COUNT(*) as total FROM activity_logs {$whereClause}";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);

        } catch (Exception $e) {
            error_log("Get activity log count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent activity for dashboard widget
     *
     * @param int $limit Number of records
     * @return array Recent activity logs
     */
    public function getRecentActivity(int $limit = 10): array
    {
        try {
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get recent activity failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity statistics for dashboard
     *
     * @return array Statistics data
     */
    public function getActivityStats(): array
    {
        try {
            $stats = [];

            // Total logs
            $stats['total'] = $this->getTotalCount();

            // Today's logs
            $sql = "SELECT COUNT(*) as today_count FROM activity_logs 
                    WHERE DATE(created_at) = CURDATE()";
            $result = $this->db->fetch($sql);
            $stats['today'] = (int)($result['today_count'] ?? 0);

            // Logs by action (top 5)
            $sql = "SELECT action, COUNT(*) as count 
                    FROM activity_logs 
                    GROUP BY action 
                    ORDER BY count DESC 
                    LIMIT 5";
            $stats['by_action'] = $this->db->fetchAll($sql);

            // Logs by user (top 5)
            $sql = "SELECT al.user_id, u.first_name, u.last_name, COUNT(*) as count
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    GROUP BY al.user_id
                    ORDER BY count DESC
                    LIMIT 5";
            $stats['by_user'] = $this->db->fetchAll($sql);

            // Last 7 days activity
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM activity_logs
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            $stats['last_7_days'] = $this->db->fetchAll($sql);

            return $stats;

        } catch (Exception $e) {
            error_log("Get activity stats failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unique action types for filtering
     *
     * @return array List of unique actions
     */
    public function getActionTypes(): array
    {
        try {
            $sql = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
            $results = $this->db->fetchAll($sql);
            return array_column($results, 'action');
        } catch (Exception $e) {
            error_log("Get action types failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cleanup old activity logs
     *
     * @param int $days Number of days to keep (default 90)
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(int $days = 90): int
    {
        try {
            $sql = "DELETE FROM activity_logs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();

        } catch (Exception $e) {
            error_log("Cleanup old logs failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Log activity to file for backup
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
