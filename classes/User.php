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
            $sql = "SELECT user_id, first_name, last_name, email, password_hash, role_id, status, created_at
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
            $sql = "INSERT INTO users (first_name, last_name, email, password_hash, role_id, status, created_at)
                    VALUES (:first_name, :last_name, :email, :password_hash, :role_id, :status, NOW())";

            $params = [
                ':first_name' => trim($data['first_name']),
                ':last_name' => trim($data['last_name']),
                ':email' => trim(strtolower($data['email'])),
                ':password_hash' => $passwordHash,
                ':role_id' => $roleId,
                ':status' => $status
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
            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.created_at, u.updated_at, r.role_name
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
            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.created_at, u.updated_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.user_id = :user_id";

            $result = $this->db->fetch($sql, [':user_id' => $userId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Find user by ID failed: " . $e->getMessage());
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
            $existing = $this->findById($userId);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

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
                    updated_at = NOW()
                    WHERE user_id = :user_id";

            $params = [
                ':user_id' => $userId,
                ':first_name' => trim($data['first_name'] ?? $existing['first_name']),
                ':last_name' => trim($data['last_name'] ?? $existing['last_name']),
                ':email' => trim(strtolower($data['email'] ?? $existing['email'])),
                ':role_id' => $data['role_id'] ?? $existing['role_id'],
                ':status' => $data['status'] ?? $existing['status']
            ];

            $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];

        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user'
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

            $sql = "UPDATE users SET status = :status, updated_at = NOW() WHERE user_id = :user_id";
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
}
