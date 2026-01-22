<?php
/**
 * User Class
 * Handles user-related database operations
 */

class User
{
    private $db;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
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
     * Find user by email address
     * 
     * @param string $email User email
     * @return array|false User data or false if not found
     */
    public function findByEmail(string $email)
    {
        try {
            $sql = "SELECT user_id, first_name, last_name, email, password_hash, role_id, status, created_at, updated_at 
                    FROM users 
                    WHERE email = :email 
                    LIMIT 1";
            
            $params = [':email' => trim(strtolower($email))];
            
            return $this->db->fetch($sql, $params);

        } catch (Exception $e) {
            error_log("Find user by email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|false User data or false if not found
     */
    public function findById(int $id)
    {
        try {
            $sql = "SELECT user_id, first_name, last_name, email, password_hash, role_id, status, created_at, updated_at 
                    FROM users 
                    WHERE user_id = :user_id 
                    LIMIT 1";
            
            $params = [':user_id' => $id];
            
            return $this->db->fetch($sql, $params);

        } catch (Exception $e) {
            error_log("Find user by ID failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user information
     * 
     * @param int $id User ID
     * @param array $data Data to update
     * @return array Success or error response
     */
    public function update(int $id, array $data)
    {
        try {
            $allowedFields = ['first_name', 'last_name', 'email', 'role_id', 'status'];
            $updates = [];
            $params = [':user_id' => $id];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }

            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ];
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE user_id = :user_id";
            
            $affectedRows = $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'User updated successfully',
                'affected_rows' => $affectedRows
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
     * Delete user (soft delete by setting status to 'inactive')
     * 
     * @param int $id User ID
     * @return array Success or error response
     */
    public function delete(int $id)
    {
        try {
            $sql = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE user_id = :user_id";
            $params = [':user_id' => $id];
            
            $affectedRows = $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'User deleted successfully',
                'affected_rows' => $affectedRows
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
     * Get all users with optional filters
     * 
     * @param array $filters Optional filters (role_id, status)
     * @return array List of users
     */
    public function getAll(array $filters = [])
    {
        try {
            $sql = "SELECT user_id, first_name, last_name, email, role_id, status, created_at, updated_at 
                    FROM users WHERE 1=1";
            
            $params = [];

            if (!empty($filters['role_id'])) {
                $sql .= " AND role_id = :role_id";
                $params[':role_id'] = $filters['role_id'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY created_at DESC";

            return $this->db->fetchAll($sql, $params);

        } catch (Exception $e) {
            error_log("Get all users failed: " . $e->getMessage());
            return [];
        }
    }
}
