<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/sanitize.php';

class ActivityLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Log an activity to DB and file
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
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];

            $this->db->insert($sql, $params);
            $this->logToFile($userId, $action, $description ?: $action);
            return true;

        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $userId = (int)$filters['user_id'];
                if ($userId > 0) {
                    $where[] = "al.user_id = :user_id";
                    $params[':user_id'] = $userId;
                }
            }

            if (!empty($filters['action'])) {
                $where[] = "al.action = :action";
                $params[':action'] = trim($filters['action']);
            }

            if (!empty($filters['date_from'])) {
                $dateFrom = trim($filters['date_from']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                    $where[] = "al.created_at >= :date_from";
                    $params[':date_from'] = $dateFrom;
                }
            }

            if (!empty($filters['date_to'])) {
                $dateTo = trim($filters['date_to']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                    $where[] = "al.created_at <= :date_to";
                    $params[':date_to'] = $dateTo;
                }
            }

            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                if ($search !== '') {
                    $where[] = "(al.action LIKE :search OR al.description LIKE :search)";
                    $params[':search'] = '%' . $search . '%';
                }
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $limit = min(max($limit, 1), 100);
            $offset = max($offset, 0);

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

    public function getActivityStats(): array
    {
        try {
            $stats = [];
            $stats['total'] = $this->getTotalCount();

            $result = $this->db->fetch("SELECT COUNT(*) as today_count FROM activity_logs WHERE DATE(created_at) = CURDATE()");
            $stats['today'] = (int)($result['today_count'] ?? 0);

            $stats['by_action'] = $this->db->fetchAll(
                "SELECT action, COUNT(*) as count FROM activity_logs GROUP BY action ORDER BY count DESC LIMIT 5"
            );

            $stats['by_user'] = $this->db->fetchAll(
                "SELECT al.user_id, u.first_name, u.last_name, COUNT(*) as count
                 FROM activity_logs al
                 JOIN users u ON al.user_id = u.user_id
                 GROUP BY al.user_id
                 ORDER BY count DESC
                 LIMIT 5"
            );

            $stats['last_7_days'] = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM activity_logs
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC"
            );

            return $stats;

        } catch (Exception $e) {
            error_log("Get activity stats failed: " . $e->getMessage());
            return [];
        }
    }

    public function getActionTypes(): array
    {
        try {
            $results = $this->db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
            return array_column($results, 'action');
        } catch (Exception $e) {
            error_log("Get action types failed: " . $e->getMessage());
            return [];
        }
    }

    public function cleanupOldLogs(int $days = 90): int
    {
        try {
            $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Cleanup old logs failed: " . $e->getMessage());
            return 0;
        }
    }

    private function logToFile(int $userId, string $action, string $description): void
    {
        $logFile = __DIR__ . '/../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getIpAddress();
        $entry = "[{$timestamp}] User:{$userId} Action:{$action} IP:{$ip} - {$description}" . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private function getIpAddress(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        return 'Unknown';
    }
}
