<?php
/**
 * User Class
 * Handles user-related database operations
 */

require_once __DIR__ . '/Database.php';

class User
{
    private $db;

    /**
     * Constructor - Initialize Database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by email
     *
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email)
    {
        try {
        $sql = "SELECT user_id, first_name, last_name, email, password_hash, role_id, status, profile_picture, profile_thumbnail, created_at
                    FROM users WHERE email = :email LIMIT 1";

            $stmt = $this->db->query($sql, [':email' => strtolower($email)]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (Exception $e) {
            error_log("Find user by email failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new user
     *
     * @param array $data User data (first_name, last_name, email, password, role_id, status)
     * @return array Success or error response
     */
    public function create(array $data)
    {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            // Check if email already exists
            $existingUser = $this->findByEmail($data['email']);
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }

            // Hash password before storing
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Set default values if not provided
            $roleId = $data['role_id'] ?? 2; // Default to teacher role
            $status = $data['status'] ?? 'active';

            // Insert user into database
$sql = "INSERT INTO users (first_name, last_name, email, password_hash, role_id, status, profile_picture, profile_thumbnail, created_at)
                    VALUES (:first_name, :last_name, :email, :password_hash, :role_id, :status, :profile_picture, :profile_thumbnail, NOW())";

            $params = [
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => trim(strtolower($data['email'])),
                ':password_hash' => $passwordHash,
                ':role_id' => $roleId,
                ':status' => $status,
                ':profile_picture' => $data['profile_picture'] ?? null,
                ':profile_thumbnail' => $data['profile_thumbnail'] ?? null
            ];

            $userId = $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
        }
    }

    /**
     * Get all users (admin only)
     *
     * @return array All users
     */
    public function getAll(): array
    {
        try {
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    ORDER BY u.created_at DESC";

            return $this->db->fetchAll($sql);

        } catch (Exception $e) {
            error_log("Get all users failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user by ID
     *
     * @param int $userId User ID
     * @return array|null User data
     */
    public function findById(int $userId): ?array
    {
        try {
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, u.updated_at, COALESCE(r.role_name, CONCAT('Role_', u.role_id)) as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.role_id
                    WHERE u.user_id = :user_id";

            $result = $this->db->fetch($sql, [':user_id' => $userId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Find user by ID {$userId} failed: " . $e->getMessage());
            // Fallback: try without JOIN
            $fallback = $this->db->fetch("SELECT * FROM users WHERE user_id = :user_id", [':user_id' => $userId]);
            if ($fallback) {
                $fallback['role_name'] = 'Role_' . $fallback['role_id'];
                error_log("findById fallback success for user {$userId}");
                return $fallback;
            }
            return null;
        }
    }

    /**
     * Update user
     *
     * @param int $userId User ID
     * @param array $data Updated data
     * @return array Result
     */
    public function update(int $userId, array $data): array
    {
        try {
            // Check if user exists
            $existing = $this->db->fetch("SELECT * FROM users WHERE user_id = :user_id", [':user_id' => $userId]);
            if (!$existing) {
                error_log("User update failed: User ID {$userId} does not exist in users table");
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            // Check roles separately if needed
            $roleCheck = $this->db->fetch("SELECT role_name FROM roles WHERE role_id = :role_id", [':role_id' => $existing['role_id']]);
            error_log("User {$userId} update check - Role exists: " . ($roleCheck ? 'yes' : 'no'));

            // Validate email format if provided
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            // Check if email is being changed and already exists
            if (isset($data['email']) && strtolower($data['email']) !== strtolower($existing['email'])) {
                $existingUser = $this->findByEmail($data['email']);
                if ($existingUser) {
                    return [
                        'success' => false,
                        'message' => 'Email already exists'
                    ];
                }
            }

$sql = "UPDATE users SET
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    role_id = :role_id,
                    status = :status,
                    profile_picture = :profile_picture,
                    profile_thumbnail = :profile_thumbnail,
                    updated_at = NOW()
                    WHERE user_id = :user_id";

            // Validate profile image paths if provided
            $profilePicture = $data['profile_picture'] ?? $existing['profile_picture'] ?? null;
            $profileThumbnail = $data['profile_thumbnail'] ?? $existing['profile_thumbnail'] ?? null;
            
            if ($profilePicture && !file_exists(__DIR__ . '/../' . $profilePicture)) {
                return ['success' => false, 'message' => 'Profile image file not found'];
            }
            if ($profileThumbnail && !file_exists(__DIR__ . '/../' . $profileThumbnail)) {
                return ['success' => false, 'message' => 'Profile thumbnail not found'];
            }
            
            $params = [
                ':user_id' => $userId,
                ':first_name' => trim($data['first_name'] ?? $existing['first_name']),
                ':last_name' => trim($data['last_name'] ?? $existing['last_name']),
                ':email' => trim(strtolower($data['email'] ?? $existing['email'])),
                ':role_id' => (int)($data['role_id'] ?? $existing['role_id']),
                ':status' => $data['status'] ?? $existing['status'],
                ':profile_picture' => $profilePicture,
                ':profile_thumbnail' => $profileThumbnail
            ];

            $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];

        } catch (PDOException $e) {
            error_log("User update failed (PDO): " . $e->getMessage());
            $errorCode = $e->getCode();
            $errorInfo = $e->errorInfo[1] ?? 0; // MySQL error code
            
            if ($errorCode === '23000' || $errorInfo === 1062) { // Duplicate entry
                if (stripos($e->getMessage(), 'email') !== false) {
                    return ['success' => false, 'message' => 'Email address already in use'];
                }
                return ['success' => false, 'message' => 'Data already exists. Please check your inputs'];
            } elseif ($errorInfo === 1452) { // Foreign key constraint
                return ['success' => false, 'message' => 'Invalid role selected'];
            }
            return [
                'success' => false,
                'message' => 'Database error. Please try again or contact support'
            ];
        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Update failed. Please try again'
            ];
        }
    }

    /**
     * Delete user
     *
     * @param int $userId User ID
     * @return array Result
     */
    public function delete(int $userId): array
    {
        try {
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $this->db->delete($sql, [':user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete user'
            ];
        }
    }

    /**
     * Update user status
     *
     * @param int $userId User ID
     * @param string $status New status (active/inactive)
     * @return array Result
     */
    public function updateStatus(int $userId, string $status): array
    {
        try {
            if (!in_array($status, ['active', 'inactive'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value'
                ];
            }

            $sql = "UPDATE users SET status = :status WHERE user_id = :user_id";
            $this->db->update($sql, [':user_id' => $userId, ':status' => $status]);

            return [
                'success' => true,
                'message' => 'User status updated successfully'
            ];

        } catch (Exception $e) {
            error_log("User status update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user status'
            ];
        }
    }

    /**
     * Get all teachers (role_id = 2)
     *
     * @return array All teacher users
     */
    public function getTeachers(): array
    {
        try {
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.role_id = 2
                    ORDER BY u.created_at DESC";

            return $this->db->fetchAll($sql);

        } catch (Exception $e) {
            error_log("Get teachers failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get specific teachers by IDs
     *
     * @param array $userIds Array of user IDs
     * @return array Teacher users
     */
    public function getTeachersByIds(array $userIds): array
    {
        try {
            if (empty($userIds)) {
                return [];
            }

            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.role_id = 2 AND u.user_id IN ({$placeholders})
                    ORDER BY u.created_at DESC";

            // Note: PDO doesn't directly support IN clause with named parameters in a loop
            // We'll use manual query building instead
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($userIds);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log("Get teachers by IDs failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get teachers matching a wildcard or regex pattern
     * Wildcards: * = any chars, ? = single char
     * Regex: pattern starting with / (e.g. /^john/i)
     * Matched against full name and email
     *
     * @param string $pattern Wildcard or regex pattern
     * @return array Matching teacher users
     */
    public function getTeachersByPattern(string $pattern): array
    {
        try {
            $pattern = trim($pattern);
            if ($pattern === '' || $pattern === '*') {
                return $this->getTeachers();
            }

            // Regex mode: pattern starts with /
            if (str_starts_with($pattern, '/')) {
                $all = $this->getTeachers();
                return array_values(array_filter($all, function ($t) use ($pattern) {
                    $name = $t['first_name'] . ' ' . $t['last_name'];
                    return @preg_match($pattern, $name) === 1
                        || @preg_match($pattern, $t['email'] ?? '') === 1;
                }));
            }

            // Wildcard mode: convert * → % and ? → _ for SQL LIKE
            // Escape existing SQL wildcards first
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $pattern);
            $like = str_replace(['*', '?'], ['%', '_'], $like);

            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status,
                           u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.role_id = 2
                      AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                           OR u.email LIKE ?)
                    ORDER BY u.created_at DESC";

            return $this->db->fetchAll($sql, [$like, $like]);

        } catch (Exception $e) {
            error_log("Get teachers by pattern failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total users count (all statuses)
     */
    public function getTotalUsersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            error_log("Get total users count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active users count
     */
    public function getActiveUsersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active'");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            error_log("Get active users count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active admins count (role_id = 1, status = 'active')
     */
    public function getActiveAdminsCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active' AND role_id = 1");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            error_log("Get active admins count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active teachers count (role_id = 2, status = 'active')
     */
    public function getActiveTeachersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active' AND role_id = 2");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            error_log("Get active teachers count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get profile image path (thumbnail preferred, fallback to full or default)
     * @param int $userId
     * @param bool $thumbnail
     * @return string
     */
    public function getProfileImage(int $userId, bool $thumbnail = true): string
    {
        $user = $this->findById($userId);
        if (!$user) return '/public/css/default-avatar.png';

        if ($thumbnail) {
            if (!empty($user['profile_thumbnail'])) return $user['profile_thumbnail'];
            if (!empty($user['profile_picture'])) return $user['profile_picture'];
        } else {
            if (!empty($user['profile_picture'])) return $user['profile_picture'];
            if (!empty($user['profile_thumbnail'])) return $user['profile_thumbnail'];
        }
        return '/public/css/default-avatar.png';
    }
}

