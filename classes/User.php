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
}
