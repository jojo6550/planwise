<?php
/**
 * Role Class
 * Handles user role management and role-based access control
 * CS334 Module 3 - Custom PHP Classes, RBAC
 */

require_once __DIR__ . '/Database.php';

class Role
{
    private $db;

    // Role Constants
    public const ROLE_ADMIN = 1;
    public const ROLE_TEACHER = 2;

    public const ROLE_NAMES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_TEACHER => 'Teacher'
    ];

    /**
     * Constructor - Initialize Database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all roles
     *
     * @return array All roles
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT role_id, role_name FROM roles ORDER BY role_name";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get roles failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get role by ID
     *
     * @param int $roleId Role ID
     * @return array|null Role data
     */
    public function getById(int $roleId): ?array
    {
        try {
            $sql = "SELECT role_id, role_name FROM roles WHERE role_id = :role_id";
            $result = $this->db->fetch($sql, [':role_id' => $roleId]);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get role by ID failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if role is admin
     *
     * @param int $roleId Role ID
     * @return bool True if admin role
     */
    public function isAdmin(int $roleId): bool
    {
        return $roleId === self::ROLE_ADMIN;
    }

    /**
     * Check if role is teacher
     *
     * @param int $roleId Role ID
     * @return bool True if teacher role
     */
    public function isTeacher(int $roleId): bool
    {
        return $roleId === self::ROLE_TEACHER;
    }

    /**
     * Get role name by ID
     *
     * @param int $roleId Role ID
     * @return string Role name
     */
    public function getName(int $roleId): string
    {
        return self::ROLE_NAMES[$roleId] ?? 'Unknown';
    }

    /**
     * Check if user has permission for action (extensible for future permissions)
     *
     * @param int $roleId Role ID
     * @param string $action Action to check
     * @return bool True if user has permission
     */
    public function hasPermission(int $roleId, string $action): bool
    {
        $permissions = [
            self::ROLE_ADMIN => [
                'view_users',
                'create_user',
                'edit_user',
                'delete_user',
                'view_activity_logs',
                'view_all_lesson_plans',
                'delete_any_lesson_plan',
                'manage_system'
            ],
            self::ROLE_TEACHER => [
                'create_lesson_plan',
                'edit_own_lesson_plan',
                'delete_own_lesson_plan',
                'export_lesson_plan',
                'view_own_profile'
            ]
        ];

        return in_array($action, $permissions[$roleId] ?? []);
    }

    /**
     * Create a new role (admin only)
     *
     * @param string $roleName Role name
     * @return array Result
     */
    public function create(string $roleName): array
    {
        try {
            // Validate role name
            if (empty($roleName)) {
                return [
                    'success' => false,
                    'message' => 'Role name is required'
                ];
            }

            // Check if role already exists
            $sql = "SELECT role_id FROM roles WHERE role_name = :role_name";
            $existing = $this->db->fetch($sql, [':role_name' => $roleName]);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Role already exists'
                ];
            }

            // Insert new role
            $sql = "INSERT INTO roles (role_name) VALUES (:role_name)";
            $this->db->insert($sql, [':role_name' => trim($roleName)]);

            return [
                'success' => true,
                'message' => 'Role created successfully'
            ];

        } catch (Exception $e) {
            error_log("Role creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create role'
            ];
        }
    }

    /**
     * Get users by role ID
     *
     * @param int $roleId Role ID
     * @return array Users with this role
     */
    public function getUsersByRole(int $roleId): array
    {
        try {
            $sql = "SELECT user_id, first_name, last_name, email, status, created_at
                    FROM users
                    WHERE role_id = :role_id
                    ORDER BY first_name, last_name";
            return $this->db->fetchAll($sql, [':role_id' => $roleId]);
        } catch (Exception $e) {
            error_log("Get users by role failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count users by role
     *
     * @param int $roleId Role ID
     * @return int Count of users
     */
    public function countUsersByRole(int $roleId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE role_id = :role_id";
            $result = $this->db->fetch($sql, [':role_id' => $roleId]);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Count users by role failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get role statistics
     *
     * @return array Role statistics
     */
    public function getStatistics(): array
    {
        try {
            $stats = [];
            $roles = $this->getAll();

            foreach ($roles as $role) {
                $stats[$role['role_name']] = [
                    'role_id' => $role['role_id'],
                    'user_count' => $this->countUsersByRole($role['role_id'])
                ];
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Get role statistics failed: " . $e->getMessage());
            return [];
        }
    }
}
