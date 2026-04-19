<?php
require_once __DIR__ . '/Database.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email)
    {
        try {
            $sql = "SELECT user_id, first_name, last_name, email, password_hash, role_id, status, profile_picture, profile_thumbnail, created_at
                    FROM users WHERE email = :email LIMIT 1";
            $result = $this->db->fetch($sql, [':email' => strtolower($email)]);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Find user by email failed: " . $e->getMessage());
            return null;
        }
    }

    public function create(array $data)
    {
        try {
            $required = ['first_name', 'last_name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            if ($this->findByEmail($data['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $roleId = $data['role_id'] ?? 2;
            $status = $data['status'] ?? 'active';

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

            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $userId];

        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }

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

    public function findById(int $userId): ?array
    {
        try {
            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
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

    public function update(int $userId, array $data): array
    {
        try {
            $existing = $this->db->fetch("SELECT * FROM users WHERE user_id = :user_id", [':user_id' => $userId]);
            if (!$existing) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            if (isset($data['email']) && strtolower($data['email']) !== strtolower($existing['email'])) {
                if ($this->findByEmail($data['email'])) {
                    return ['success' => false, 'message' => 'Email already exists'];
                }
            }

            $sql = "UPDATE users SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        role_id = :role_id,
                        status = :status,
                        profile_picture = :profile_picture,
                        profile_thumbnail = :profile_thumbnail
                    WHERE user_id = :user_id";

            $profilePicture = $data['profile_picture'] ?? $existing['profile_picture'] ?? null;
            $profileThumbnail = $data['profile_thumbnail'] ?? $existing['profile_thumbnail'] ?? null;

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
            return ['success' => true, 'message' => 'User updated successfully'];

        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed. Please try again'];
        }
    }

    public function delete(int $userId): array
    {
        try {
            $this->db->delete("DELETE FROM users WHERE user_id = :user_id", [':user_id' => $userId]);
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (Exception $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }

    public function updateStatus(int $userId, string $status): array
    {
        try {
            if (!in_array($status, ['active', 'inactive'])) {
                return ['success' => false, 'message' => 'Invalid status value'];
            }
            $this->db->update(
                "UPDATE users SET status = :status WHERE user_id = :user_id",
                [':user_id' => $userId, ':status' => $status]
            );
            return ['success' => true, 'message' => 'User status updated successfully'];
        } catch (Exception $e) {
            error_log("User status update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user status'];
        }
    }

    public function updatePassword(int $userId, string $newPassword): array
    {
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update(
                "UPDATE users SET password_hash = :hash WHERE user_id = :user_id",
                [':hash' => $hash, ':user_id' => $userId]
            );
            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (Exception $e) {
            error_log("User password update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }

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

    public function getTeachersByIds(array $userIds): array
    {
        try {
            if (empty($userIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role_id, u.status, u.profile_picture, u.profile_thumbnail, u.created_at, r.role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.role_id = 2 AND u.user_id IN ({$placeholders})
                    ORDER BY u.created_at DESC";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log("Get teachers by IDs failed: " . $e->getMessage());
            return [];
        }
    }

    // Search teachers by name or email using SQL LIKE wildcards (* and ?)
    public function getTeachersByPattern(string $pattern): array
    {
        try {
            $pattern = trim($pattern);
            if ($pattern === '' || $pattern === '*') {
                return $this->getTeachers();
            }

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

    public function getTotalUsersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getActiveUsersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active'");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getActiveAdminsCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active' AND role_id = 1");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getActiveTeachersCount(): int
    {
        try {
            $row = $this->db->fetch("SELECT COUNT(*) as c FROM users WHERE status = 'active' AND role_id = 2");
            return (int)($row['c'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getProfileImage(int $userId, bool $thumbnail = true): string
    {
        $user = $this->findById($userId);
        if (!$user) return '/planwise/public/css/default-avatar.png';

        $base = '/planwise/';
        if ($thumbnail) {
            if (!empty($user['profile_thumbnail'])) return $base . $user['profile_thumbnail'];
            if (!empty($user['profile_picture'])) return $base . $user['profile_picture'];
        } else {
            if (!empty($user['profile_picture'])) return $base . $user['profile_picture'];
            if (!empty($user['profile_thumbnail'])) return $base . $user['profile_thumbnail'];
        }
        return '/planwise/public/css/default-avatar.png';
    }
}
