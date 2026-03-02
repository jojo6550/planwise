<?php
/**
 * AuthController
 * Handles authentication-related requests (login, logout, registration)
 */

// Load environment variables if not already loaded
if (!isset($_ENV['DB_NAME'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }
}

// Require necessary classes
require_once __DIR__ . '/../classes/BaseController.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/PasswordReset.php';
require_once __DIR__ . '/../classes/Mail.php';

class AuthController extends BaseController
{
    private $auth;
    private $activityLog;
    private $passwordReset;

    /**
     * Constructor - Initialize Auth class
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->activityLog = new ActivityLog();
        $this->passwordReset = new PasswordReset();
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
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Attempt login
        $result = $this->auth->login($email, $password);

        if ($result['success']) {
            // Login successful - redirect to dashboard based on role
            $user = $this->auth->user();

            // Log successful login
            $this->activityLog->log(
                $user['user_id'],
                'user_login',
                "User logged in: {$user['email']}"
            );

            // Store debug info in session if in development mode
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }

            // Role ID 1 = Admin, Role ID 2 = Teacher
            if ($user['role_id'] == 1) {
                $this->redirectWithSuccess($result['message'], 'admin/dashboard');
            } else {
                $this->redirectWithSuccess($result['message'], 'teacher/dashboard');
            }
        } else {
            // Login failed - redirect back with error and debug info
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }
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
        // Get user info before logout for logging
        $user = $this->auth->user();
        $userId = $user['user_id'] ?? null;
        $userEmail = $user['email'] ?? 'unknown';

        $result = $this->auth->logout();

        // Log logout if we had a valid user
        if ($userId) {
            $this->activityLog->log(
                $userId,
                'user_logout',
                "User logged out: {$userEmail}"
            );
        }

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
        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'register');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'register');
            return;
        }

        // Get and sanitize input
        $data = [
            'first_name' => $this->sanitize($_POST['first_name'] ?? ''),
            'last_name' => $this->sanitize($_POST['last_name'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role_id' => 2, // Default to teacher role
            'status' => 'active'
        ];

        // Validate required fields
        if (empty($data['first_name'])) {
            $this->redirectWithError('First name is required', 'register');
            return;
        }

        if (empty($data['last_name'])) {
            $this->redirectWithError('Last name is required', 'register');
            return;
        }

        if (empty($data['email'])) {
            $this->redirectWithError('Email is required', 'register');
            return;
        }

        if (empty($data['password'])) {
            $this->redirectWithError('Password is required', 'register');
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Invalid email format', 'register');
            return;
        }

        // Validate password confirmation
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if ($data['password'] !== $passwordConfirm) {
            $this->redirectWithError('Passwords do not match', 'register');
            return;
        }

        // Validate password strength (minimum 8 characters)
        if (strlen($data['password']) < 8) {
            $this->redirectWithError('Password must be at least 8 characters long', 'register');
            return;
        }

        // Create user using User class
        $user = new User();
        $result = $user->create($data);

        if ($result['success']) {
            // Send welcome email
            $mail = new Mail();
            $userData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email']
            ];
            $mailResult = $mail->sendRegistrationEmail($userData);
            if (!$mailResult['success']) {
                error_log("Failed to send registration email: " . $mailResult['message']);
                // Don't fail registration if email fails
            }

            // Log successful registration
            $this->activityLog->log(
                $result['user_id'] ?? 0, // Assuming the result includes user_id
                'user_registered',
                "New user registered: {$data['email']}"
            );

            // Store debug info in session if in development mode
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }
            // Registration successful - redirect to login
            $this->redirectWithSuccess('Registration successful. Please login.', 'login');
        } else {
            error_log("Registration failed: " . $result['message']);
            $this->redirectWithError($result['message'], 'register');
        }
    }

    /**
     * Handle forgot password request
     *
     * @return void
     */
    public function forgotPassword()
    {
        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'forgot-password');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'forgot-password');
            return;
        }

        // Get and sanitize email
        $email = $this->sanitize($_POST['email'] ?? '');

        if (empty($email)) {
            $this->redirectWithError('Email is required', 'forgot-password');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Invalid email format', 'forgot-password');
            return;
        }

        // Generate reset token
        $result = $this->passwordReset->generateToken($email);

        if ($result['success']) {
            // Send password reset email
            $mail = new Mail();
            $user = new User();
            $userData = $user->findByEmail($email);

            if ($userData) {
                $mailResult = $mail->sendPasswordResetEmail($userData, $result['token']);
                if (!$mailResult['success']) {
                    // Log internally; do not expose to the user (prevents email enumeration)
                    error_log("Password reset email failed for account: " . $mailResult['message']);
                }
            }

            // In DEBUG MODE, expose the reset URL in session only (never in logs)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $resetUrl = ($_ENV['APP_URL'] ?? 'http://localhost/planwise/public/')
                    . 'index.php?page=reset-password&token=' . $result['token'];
                $_SESSION['debug_reset_link'] = $resetUrl;
            }

            // Always show a generic success message to prevent email enumeration
            $this->redirectWithSuccess('If that email exists in our system, a password reset link has been sent to it.', 'login');
        } else {
            // Show same generic message to prevent email enumeration
            $this->redirectWithSuccess('If that email exists in our system, a password reset link has been sent to it.', 'login');
        }
    }

    /**
     * Handle reset password request
     *
     * @return void
     */
    public function resetPassword()
    {
        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'reset-password');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'reset-password');
            return;
        }

        // Get input
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token)) {
            $this->redirectWithError('Invalid reset token', 'reset-password&token=' . urlencode($token));
            return;
        }

        if (empty($password)) {
            $this->redirectWithError('Password is required', 'reset-password&token=' . urlencode($token));
            return;
        }

        if (strlen($password) < 8) {
            $this->redirectWithError('Password must be at least 8 characters long', 'reset-password&token=' . urlencode($token));
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->redirectWithError('Passwords do not match', 'reset-password&token=' . urlencode($token));
            return;
        }

        // Reset password
        $result = $this->passwordReset->resetPassword($token, $password);

        if ($result['success']) {
            // Log successful password reset
            $this->activityLog->log(
                $result['user_id'] ?? 0, // Assuming the result includes user_id
                'password_reset_completed',
                "Password reset completed for user ID: " . ($result['user_id'] ?? 'unknown')
            );

            $this->redirectWithSuccess('Password has been reset successfully. Please login with your new password.', 'login');
        } else {
            $this->redirectWithError($result['message'], 'reset-password&token=' . urlencode($token));
        }
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
        case 'forgot-password':
            $controller->forgotPassword();
            break;
        case 'reset-password':
            $controller->resetPassword();
            break;
        default:
            header('Location: /planwise/public/index.php');
            exit();
    }
}
