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
     * Get all activity logs (AJAX)
     */
    public function getAll()
    {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $logs = $this->activityLog->getAll($limit, $offset);
        $total = $this->activityLog->getTotalCount();

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
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
