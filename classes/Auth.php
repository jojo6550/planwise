<?php
class Auth
{
    private $user;

    public function __construct(User $user = null)
    {
        $this->user = $user ?: new User();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password, bool $rememberMe = false)
    {
        try {
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            $userData = $this->user->findByEmail($email);

            if (!$userData || $userData['status'] !== 'active') {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            if (!password_verify($password, $userData['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['first_name'] = $userData['first_name'];
            $_SESSION['last_name'] = $userData['last_name'];
            $_SESSION['role_id'] = $userData['role_id'];
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();

            if ($rememberMe) {
                $this->setRememberToken((int)$userData['user_id']);
            }

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
            error_log("Login failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    public function logout()
    {
        $this->clearRememberToken();

        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }

        session_destroy();

        return ['success' => true, 'message' => 'Logout successful'];
    }

    public function check(): bool
    {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || !isset($_SESSION['user_id'])) {
            return $this->checkRememberToken();
        }

        // Session timeout (30 minutes)
        $sessionTimeout = 30 * 60;
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    private function setRememberToken(int $userId): void
    {
        try {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 30 * 24 * 3600);
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

            $db = Database::getInstance();
            $db->insert(
                "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)",
                [$userId, $tokenHash, $expiresAt]
            );

            setcookie('remember_token', $rawToken, [
                'expires'  => time() + 30 * 24 * 3600,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } catch (Exception $e) {
            error_log("Remember token set failed: " . $e->getMessage());
        }
    }

    private function clearRememberToken(): void
    {
        try {
            if (isset($_COOKIE['remember_token'])) {
                $tokenHash = hash('sha256', $_COOKIE['remember_token']);
                $db = Database::getInstance();
                $db->delete("DELETE FROM remember_tokens WHERE token_hash = ?", [$tokenHash]);

                $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                setcookie('remember_token', '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        } catch (Exception $e) {
            error_log("Remember token clear failed: " . $e->getMessage());
        }
    }

    private function checkRememberToken(): bool
    {
        if (empty($_COOKIE['remember_token'])) {
            return false;
        }

        try {
            $tokenHash = hash('sha256', $_COOKIE['remember_token']);
            $db = Database::getInstance();

            $db->delete("DELETE FROM remember_tokens WHERE expires_at < NOW()");

            $rows = $db->fetchAll(
                "SELECT rt.token_id, u.user_id, u.email, u.first_name, u.last_name, u.role_id, u.status
                 FROM remember_tokens rt
                 JOIN users u ON rt.user_id = u.user_id
                 WHERE rt.token_hash = ? AND rt.expires_at > NOW()
                 LIMIT 1",
                [$tokenHash]
            );

            if (empty($rows) || $rows[0]['status'] !== 'active') {
                $this->clearRememberToken();
                return false;
            }

            $userData = $rows[0];

            session_regenerate_id(true);
            $_SESSION['user_id']       = $userData['user_id'];
            $_SESSION['email']         = $userData['email'];
            $_SESSION['first_name']    = $userData['first_name'];
            $_SESSION['last_name']     = $userData['last_name'];
            $_SESSION['role_id']       = $userData['role_id'];
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time']    = time();

            return true;
        } catch (Exception $e) {
            error_log("Remember token check failed: " . $e->getMessage());
            return false;
        }
    }

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

    public function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function hasRole(int $roleId): bool
    {
        if (!$this->check()) {
            return false;
        }
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] === $roleId;
    }

    public function requireAuth(string $redirectUrl = null)
    {
        $url = $redirectUrl ?: BASE_URL . '/index.php?page=login';
        if (!$this->check()) {
            header("Location: {$url}");
            exit();
        }
    }

    public function requireRole(int $roleId, string $redirectUrl = null)
    {
        $url = $redirectUrl ?: BASE_URL . '/index.php?page=403';
        if (!$this->hasRole($roleId)) {
            header("Location: {$url}");
            exit();
        }
    }
}
