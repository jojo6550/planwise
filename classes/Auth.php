<?php
/**
 * Auth Class
 * Handles user authentication and session management
 */

class Auth
{
    private $user;

    /**
     * Constructor - Initialize User class and start session if not started
     */
    public function __construct()
    {
        $this->user = new User();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authenticate user with email and password
     *
     * @param string $email User email
     * @param string $password User password
     * @return array Success or error response
     */
    public function login(string $email, string $password)
    {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                error_log("Login failed: Email or password empty");
                return [
                    'success' => false,
                    'message' => 'Email and password are required'
                ];
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Login failed: Invalid email format - {$email}");
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            // Find user by email
            $userData = $this->user->findByEmail($email);

            if (!$userData) {
                error_log("Login failed: User not found for email - " . strtolower($email));
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Check if user is active
            if ($userData['status'] !== 'active') {
                error_log("Login failed: User inactive for email - " . strtolower($email));
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Verify password
            if (!password_verify($password, $userData['password_hash'])) {
                error_log("Login failed: Invalid password for email - " . strtolower($email));
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Store user data in session (excluding password hash)
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['first_name'] = $userData['first_name'];
            $_SESSION['last_name'] = $userData['last_name'];
            $_SESSION['role_id'] = $userData['role_id'];
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();

            error_log("Login successful for email - " . strtolower($email));

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'user_id' => $userData['user_id'],
                    'email' => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'role_id' => $userData['role_id']
                ]
            ];

        } catch (Exception $e) {
            error_log("Login failed: Exception - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }

    /**
     * Log out current user
     * 
     * @return array Success response
     */
    public function logout()
    {
        try {
            // Unset all session variables
            $_SESSION = [];

            // Destroy session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    '/',
                    '',
                    isset($_SERVER['HTTPS']),
                    true
                );
            }

            // Destroy session
            session_destroy();

            return [
                'success' => true,
                'message' => 'Logout successful'
            ];

        } catch (Exception $e) {
            error_log("Logout failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Logout failed'
            ];
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated, false otherwise
     */
    public function check(): bool
    {
        // Check basic authentication
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || !isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout (30 minutes)
        $sessionTimeout = 30 * 60; // 30 minutes in seconds
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
            // Session expired, logout user
            $this->logout();
            return false;
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Get current authenticated user data
     * 
     * @return array|null User data or null if not authenticated
     */
    public function user()
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'role_id' => $_SESSION['role_id'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }

    /**
     * Get current user's ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    public function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if current user has a specific role
     * 
     * @param int $roleId Role ID to check
     * @return bool True if user has the role, false otherwise
     */
    public function hasRole(int $roleId): bool
    {
        if (!$this->check()) {
            return false;
        }

        return isset($_SESSION['role_id']) && $_SESSION['role_id'] === $roleId;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     * 
     * @param string $redirectUrl URL to redirect if not authenticated
     * @return void
     */
    public function requireAuth(string $redirectUrl = '/planwise/public/index.php?page=login')
    {
        if (!$this->check()) {
            header("Location: {$redirectUrl}");
            exit();
        }
    }

    /**
     * Require specific role - redirect if user doesn't have the role
     *
     * @param int $roleId Required role ID
     * @param string $redirectUrl URL to redirect if role check fails
     * @return void
     */
    public function requireRole(int $roleId, string $redirectUrl = '/planwise/public/index.php?page=403')
    {
        if (!$this->hasRole($roleId)) {
            header("Location: {$redirectUrl}");
            exit();
        }
    }
}
