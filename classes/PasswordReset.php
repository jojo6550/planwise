<?php
/**
 * PasswordReset Class
 * Handles password reset functionality using secure tokens
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

class PasswordReset
{
    private $db;
    private $user;
    private $tokenExpiryMinutes = 30; // 30 minutes expiry

    /**
     * Constructor - Initialize Database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->user = new User();
    }

    /**
     * Generate a secure reset token for a user
     *
     * @param string $email User email
     * @return array Result with success status
     */
    public function generateToken(string $email): array
    {
        try {
            // Find user by email
            $user = $this->user->findByEmail($email);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email address not found'
                ];
            }

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->tokenExpiryMinutes} minutes"));

            // Clean up expired tokens for this user
            $this->cleanupExpiredTokens($user['user_id']);

            // Insert new reset token
            $sql = "INSERT INTO password_resets (user_id, reset_token, expires_at, created_at)
                    VALUES (:user_id, :reset_token, :expires_at, NOW())";

            $params = [
                ':user_id' => $user['user_id'],
                ':reset_token' => $token,
                ':expires_at' => $expiresAt
            ];

            $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'Password reset token generated successfully',
                'token' => $token,
                'user_id' => $user['user_id'],
                'email' => $user['email']
            ];

        } catch (Exception $e) {
            error_log("Generate reset token failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate reset token'
            ];
        }
    }

    /**
     * Validate a reset token
     *
     * @param string $token Reset token
     * @return array Result with user data if valid
     */
    public function validateToken(string $token): array
    {
        try {
            $sql = "SELECT pr.*, u.email, u.first_name, u.last_name
                    FROM password_resets pr
                    JOIN users u ON pr.user_id = u.user_id
                    WHERE pr.reset_token = :token
                    AND pr.used = FALSE
                    AND pr.expires_at > NOW()
                    LIMIT 1";

            $result = $this->db->fetch($sql, [':token' => $token]);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ];
            }

            return [
                'success' => true,
                'message' => 'Token is valid',
                'user_id' => $result['user_id'],
                'email' => $result['email'],
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name']
            ];

        } catch (Exception $e) {
            error_log("Validate reset token failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate token'
            ];
        }
    }

    /**
     * Reset user password using valid token
     *
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Result
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        try {
            // Validate token first
            $tokenValidation = $this->validateToken($token);
            if (!$tokenValidation['success']) {
                return $tokenValidation;
            }

            $userId = $tokenValidation['user_id'];

            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update user password
            $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW()
                    WHERE user_id = :user_id";

            $this->db->update($sql, [
                ':password_hash' => $passwordHash,
                ':user_id' => $userId
            ]);

            // Mark token as used
            $sql = "UPDATE password_resets SET used = TRUE WHERE reset_token = :token";
            $this->db->update($sql, [':token' => $token]);

            // Clean up expired tokens
            $this->cleanupExpiredTokens($userId);

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];

        } catch (Exception $e) {
            error_log("Reset password failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password'
            ];
        }
    }

    /**
     * Clean up expired tokens for a user
     *
     * @param int $userId User ID
     * @return void
     */
    private function cleanupExpiredTokens(int $userId): void
    {
        try {
            $sql = "DELETE FROM password_resets
                    WHERE user_id = :user_id
                    AND (expires_at < NOW() OR used = TRUE)";

            $this->db->delete($sql, [':user_id' => $userId]);

        } catch (Exception $e) {
            error_log("Cleanup expired tokens failed: " . $e->getMessage());
        }
    }
}
