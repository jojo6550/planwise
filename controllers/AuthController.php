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
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/PasswordReset.php';
require_once __DIR__ . '/../classes/Mail.php';

class AuthController
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
        $email = $this->sanitizeInput($_POST['email'] ?? '');
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
            // Store debug info in session if in development mode
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }
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
        header("Location: /planwise/public/index.php?page={$page}");
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
     * Handle forgot password request
     *
     * @return void
     */
    public function forgotPassword()
    {
        error_log("=== FORGOT PASSWORD REQUEST START ===");
        
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
        $email = $this->sanitizeInput($_POST['email'] ?? '');
        error_log("FORGOT PASSWORD: Email input: " . $email);

        if (empty($email)) {
            $this->redirectWithError('Email is required', 'forgot-password');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Invalid email format', 'forgot-password');
            return;
        }

        // Generate reset token
        error_log("FORGOT PASSWORD: Generating token for: " . $email);
        $result = $this->passwordReset->generateToken($email);

        if ($result['success']) {
            error_log("FORGOT PASSWORD: Token generated successfully");
            error_log("FORGOT PASSWORD: Token: " . substr($result['token'], 0, 10) . "...");
            
            // Send password reset email
            $mail = new Mail();
            
            // Get user info for the email
            $user = new User();
            $userData = $user->findByEmail($email);
            
            if ($userData) {
                error_log("FORGOT PASSWORD: User found, sending email...");
                
                // Send the password reset email
                $mailResult = $mail->sendPasswordResetEmail($userData, $result['token']);
                
                error_log("FORGOT PASSWORD: Mail result: " . json_encode($mailResult));
                
                if (!$mailResult['success']) {
                    // Log the error but don't reveal to user (security)
                    error_log("FORGOT PASSWORD: FAILED TO SEND EMAIL - " . $mailResult['message']);
                    // For debugging - show error in development mode
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        $_SESSION['debug'] = "Email sending failed: " . $mailResult['message'];
                    }
                }
            } else {
                error_log("FORGOT PASSWORD: User not found for email: " . $email);
            }

            // Still store in session for demo purposes (but email should work now)
            $_SESSION['reset_token'] = $result['token'];
            $_SESSION['reset_email'] = $result['email'];
            
            // For DEBUG MODE - show the reset link directly (for testing)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $resetUrl = ($_ENV['APP_URL'] ?? 'http://localhost/planwise/public/') . 'index.php?page=reset-password&token=' . $result['token'];
                $_SESSION['debug_reset_link'] = $resetUrl;
                error_log("FORGOT PASSWORD: DEBUG MODE - Reset URL: " . $resetUrl);
            }

            error_log("FORGOT PASSWORD REQUEST END ===");
            
            // Always show success message to prevent email enumeration
            $this->redirectWithSuccess('Password reset link has been sent to your email. Check your email for the reset link.', 'login');
        } else {
            error_log("FORGOT PASSWORD: Token generation failed - " . ($result['message'] ?? 'Unknown error'));
            // Show same message to prevent email enumeration
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
