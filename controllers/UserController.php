<?php
/**
 * UserController
 * Handles user management operations (Admin only)
 * CS334 Module 3 - Different access levels (13), Registered users only (12), Custom classes (10)
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class UserController
{
    private $auth;
    private $user;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->user = new User();
        $this->activityLog = new ActivityLog();

        // Require authentication
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to continue', 'login');
        }

        // Require admin role (role_id = 1)
        if (!$this->auth->hasRole(1)) {
            $this->redirectWithError('Access denied. Admin privileges required.', '403');
        }
    }

    /**
     * Create a new user
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'admin/users');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'admin/users');
            return;
        }

        // Prepare data
        $data = [
            'first_name' => $this->sanitize($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitize($_POST['last_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role_id' => (int)($_POST['role_id'] ?? 2),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validate password confirmation
        if ($data['password'] !== ($_POST['password_confirm'] ?? '')) {
            $this->redirectWithError('Passwords do not match', 'admin/users/create');
            return;
        }

        // Create user
        $result = $this->user->create($data);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $this->auth->id(),
                'user_created',
                "Created user: {$data['email']}"
            );

            $this->redirectWithSuccess('User created successfully', 'admin/users');
        } else {
            $this->redirectWithError($result['message'], 'admin/users/create');
        }
    }

    /**
     * Update a user
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'admin/users');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'admin/users');
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);

        // Prepare data
        $data = [
            'first_name' => $this->sanitize($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitize($_POST['last_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'role_id' => (int)($_POST['role_id'] ?? 2),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Update user
        $result = $this->user->update($userId, $data);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $this->auth->id(),
                'user_updated',
                "Updated user ID: {$userId}"
            );

            $this->redirectWithSuccess('User updated successfully', 'admin/users');
        } else {
            $this->redirectWithError($result['message'], 'admin/users/edit&id=' . $userId);
        }
    }

    /**
     * Delete a user (AJAX)
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($input['user_id'] ?? 0);

        if ($userId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
            return;
        }

        // Prevent deleting self
        if ($userId === $this->auth->id()) {
            $this->jsonResponse(['success' => false, 'message' => 'Cannot delete your own account'], 400);
            return;
        }

        // Delete user
        $result = $this->user->delete($userId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $this->auth->id(),
                'user_deleted',
                "Deleted user ID: {$userId}"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Update user status (AJAX)
     */
    public function updateStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($input['user_id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($userId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
            return;
        }

        // Prevent updating self
        if ($userId === $this->auth->id()) {
            $this->jsonResponse(['success' => false, 'message' => 'Cannot change your own status'], 400);
            return;
        }

        // Update status
        $result = $this->user->updateStatus($userId, $status);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $this->auth->id(),
                'user_status_updated',
                "Updated user ID {$userId} status to: {$status}"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Sanitize input
     */
    private function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Redirect with error message
     */
    private function redirectWithError(string $message, string $page)
    {
        $_SESSION['error'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Redirect with success message
     */
    private function redirectWithSuccess(string $message, string $page)
    {
        $_SESSION['success'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
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
if (basename($_SERVER['PHP_SELF']) === 'UserController.php') {
    $controller = new UserController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'updateStatus':
            $controller->updateStatus();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
