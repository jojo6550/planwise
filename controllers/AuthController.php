<?php
/**
 * AuthController
 * Handles authentication-related requests (login, logout, registration)
 */

// Require necessary classes
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';

class AuthController
{
    private $auth;

    /**
     * Constructor - Initialize Auth class
     */
    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Handle login request
     * 
     * @return void
     */
    public function login()
    {
        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'login');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'login');
            return;
        }

        // Get and sanitize input
        $email = $this->sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Attempt login
        $result = $this->auth->login($email, $password);

        if ($result['success']) {
            // Login successful - redirect to dashboard based on role
            $user = $this->auth->user();
            
            // Role ID 1 = Admin, Role ID 2 = Teacher
            if ($user['role_id'] == 1) {
                $this->redirectWithSuccess($result['message'], 'admin/dashboard');
            } else {
                $this->redirectWithSuccess($result['message'], 'teacher/dashboard');
            }
        } else {
            // Login failed - redirect back with error
            $this->redirectWithError($result['message'], 'login');
        }
    }

    /**
     * Handle logout request
     *
     * @return void
     */
    public function logout()
    {
        $result = $this->auth->logout();

        // Redirect to login page with success message via session
        $_SESSION['success'] = $result['message'];
        header('Location: /planwise/public/index.php?page=login');
        exit();
    }

    /**
     * Handle registration request
     * 
     * @return void
     */
    public function register()
    {
        error_log("AuthController::register() called with method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));

        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Registration failed: Invalid request method");
            $this->redirectWithError('Invalid request method', 'register');
            return;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            error_log("Registration failed: Invalid CSRF token");
            $this->redirectWithError('Invalid security token', 'register');
            return;
        }

        // Get and sanitize input
        $data = [
            'first_name' => $this->sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitizeInput($_POST['last_name'] ?? ''),
            'email' => $this->sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role_id' => 2, // Default to teacher role
            'status' => 'active'
        ];

        error_log("Registration input data: " . json_encode([
            'first_name' => !empty($data['first_name']),
            'last_name' => !empty($data['last_name']),
            'email' => !empty($data['email']),
            'password_length' => strlen($data['password']),
            'role_id' => $data['role_id'],
            'status' => $data['status']
        ]));

        // Validate required fields
        if (empty($data['first_name'])) {
            error_log("Registration failed: First name is required");
            $this->redirectWithError('First name is required', 'register');
            return;
        }

        if (empty($data['last_name'])) {
            error_log("Registration failed: Last name is required");
            $this->redirectWithError('Last name is required', 'register');
            return;
        }

        if (empty($data['email'])) {
            error_log("Registration failed: Email is required");
            $this->redirectWithError('Email is required', 'register');
            return;
        }

        if (empty($data['password'])) {
            error_log("Registration failed: Password is required");
            $this->redirectWithError('Password is required', 'register');
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Registration failed: Invalid email format for '{$data['email']}'");
            $this->redirectWithError('Invalid email format', 'register');
            return;
        }

        // Validate password confirmation
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if ($data['password'] !== $passwordConfirm) {
            error_log("Registration failed: Passwords do not match");
            $this->redirectWithError('Passwords do not match', 'register');
            return;
        }

        // Validate password strength (minimum 8 characters)
        if (strlen($data['password']) < 8) {
            error_log("Registration failed: Password too short");
            $this->redirectWithError('Password must be at least 8 characters long', 'register');
            return;
        }

        error_log("Registration validation passed, attempting to create user");

        // Create user using User class
        $user = new User();
        $result = $user->create($data);

        if ($result['success']) {
            error_log("Registration successful for email '{$data['email']}'");
            // Registration successful - redirect to login
            $this->redirectWithSuccess('Registration successful. Please login.', 'login');
        } else {
            error_log("Registration failed: " . $result['message']);
            // Registration failed - redirect back with error
            $this->redirectWithError($result['message'], 'register');
        }
    }

    /**
     * Sanitize input data
     * 
     * @param string $data Input data
     * @return string Sanitized data
     */
    private function sanitizeInput(string $data): string
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Redirect with error message
     * 
     * @param string $message Error message
     * @param string $page Page to redirect to
     * @return void
     */
    private function redirectWithError(string $message, string $page)
    {
        $_SESSION['error'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Redirect with success message
     * 
     * @param string $message Success message
     * @param string $page Page to redirect to
     * @return void
     */
    private function redirectWithSuccess(string $message, string $page)
    {
        $_SESSION['success'] = $message;
        header("Location: /public/index.php?page={$page}");
        exit();
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if valid, false otherwise
     */
    private function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate CSRF token
     * 
     * @return string Generated token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

// Handle direct requests to this controller
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $controller = new AuthController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'register':
            $controller->register();
            break;
        default:
            header('Location: /planwise/public/index.php');
            exit();
    }
}
