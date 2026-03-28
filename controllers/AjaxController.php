<?php
/**
 * AjaxController
 * Handles AJAX search endpoints returning JSON
 * CS334 Module 1 - AJAX (10 marks)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/BaseController.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/LessonPlan.php';

class AjaxController extends BaseController
{
    private $auth;
    private $userModel;
    private $lpModel;

    /**
     * Constructor - require authentication for all AJAX endpoints
     */
    public function __construct()
    {
        $this->auth      = new Auth();
        $this->userModel = new User();
        $this->lpModel   = new LessonPlan();

        if (!$this->auth->check()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    }

    /**
     * Search users by name or email (admin only)
     * GET ?action=searchUsers&q=<query>
     */
    public function searchUsers(): void
    {
        if (!$this->auth->hasRole(1)) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        $q = trim($_GET['q'] ?? '');
        $allUsers = $this->userModel->getAll();

        if ($q === '') {
            $this->jsonResponse(['success' => true, 'data' => $allUsers, 'count' => count($allUsers)]);
        }

        $filtered = array_values(array_filter($allUsers, function ($u) use ($q) {
            return stripos($u['first_name'] ?? '', $q) !== false
                || stripos($u['last_name'] ?? '', $q) !== false
                || stripos($u['email'] ?? '', $q) !== false;
        }));

        $this->jsonResponse(['success' => true, 'data' => $filtered, 'count' => count($filtered)]);
    }

    /**
     * Search lesson plans by title or subject
     * GET ?action=searchLessonPlans&q=<query>
     */
    public function searchLessonPlans(): void
    {
        $user = $this->auth->user();
        $q    = trim($_GET['q'] ?? '');

        if ($user['role_id'] == 1) {
            // Admin: search across all lesson plans
            if ($q === '') {
                $results = Database::getInstance()->fetchAll("SELECT * FROM lesson_plans ORDER BY updated_at DESC");
            } else {
                $like    = '%' . $q . '%';
                $results = Database::getInstance()->fetchAll(
                    "SELECT * FROM lesson_plans WHERE title LIKE ? OR subject LIKE ? ORDER BY updated_at DESC",
                    [$like, $like]
                );
            }
        } else {
            // Teacher: search within own lesson plans
            $all = $this->lpModel->getByUser($this->auth->id());
            if ($q === '') {
                $results = $all;
            } else {
                $results = array_values(array_filter($all, function ($lp) use ($q) {
                    return stripos($lp['title'] ?? '', $q) !== false
                        || stripos($lp['subject'] ?? '', $q) !== false;
                }));
            }
        }

        $this->jsonResponse(['success' => true, 'data' => $results ?? [], 'count' => count($results ?? [])]);
    }
}

// Handle direct requests
if (basename($_SERVER['PHP_SELF']) === 'AjaxController.php') {
    $controller = new AjaxController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'searchUsers':
            $controller->searchUsers();
            break;
        case 'searchLessonPlans':
            $controller->searchLessonPlans();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
