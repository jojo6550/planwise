<?php
/**
 * ActivityLogController
 * Handles activity log retrieval (Admin only)
 * CS334 Module 3 - Activity logs (10), Different access levels (13)
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class ActivityLogController
{
    private $auth;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->activityLog = new ActivityLog();

        // Require authentication
        if (!$this->auth->check()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Require admin role (role_id = 1)
        if (!$this->auth->hasRole(1)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }
    }

    /**
     * Get all activity logs with filtering (AJAX)
     */
    public function getAll()
    {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        // Build filters from query parameters
        $filters = [];
        
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        
        if (!empty($_GET['action'])) {
            $filters['action'] = $_GET['action'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $logs = $this->activityLog->getAll($filters, $limit, $offset);
        $total = $this->activityLog->getTotalCount($filters);

        $this->jsonResponse([
            'success' => true,
            'data' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get activity logs by user (AJAX)
     */
    public function getByUser()
    {
        $userId = (int)($_GET['user_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 20);

        if ($userId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
            return;
        }

        $logs = $this->activityLog->getByUser($userId, $limit);

        $this->jsonResponse([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get activity statistics (AJAX)
     */
    public function getStats()
    {
        $stats = $this->activityLog->getActivityStats();

        $this->jsonResponse([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available action types for filtering (AJAX)
     */
    public function getActionTypes()
    {
        $actionTypes = $this->activityLog->getActionTypes();

        $this->jsonResponse([
            'success' => true,
            'data' => $actionTypes
        ]);
    }

    /**
     * Get recent activity for dashboard (AJAX)
     */
    public function getRecent()
    {
        $limit = (int)($_GET['limit'] ?? 10);
        
        $logs = $this->activityLog->getRecentActivity($limit);

        $this->jsonResponse([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Cleanup old activity logs (AJAX - Admin only)
     */
    public function cleanup()
    {
        $days = (int)($_POST['days'] ?? 90);
        
        if ($days < 1 || $days > 365) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid number of days'], 400);
            return;
        }

        $deletedCount = $this->activityLog->cleanupOldLogs($days);

        // Log the cleanup action
        $this->activityLog->log(
            $this->auth->id(),
            'activity_logs_cleaned',
            "Cleaned up activity logs older than {$days} days. Deleted {$deletedCount} records."
        );

        $this->jsonResponse([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} old activity logs",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

// Handle direct requests
if (basename($_SERVER['PHP_SELF']) === 'ActivityLogController.php') {
    $controller = new ActivityLogController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'getAll':
            $controller->getAll();
            break;
        case 'getByUser':
            $controller->getByUser();
            break;
        case 'getStats':
            $controller->getStats();
            break;
        case 'getActionTypes':
            $controller->getActionTypes();
            break;
        case 'getRecent':
            $controller->getRecent();
            break;
        case 'cleanup':
            $controller->cleanup();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
