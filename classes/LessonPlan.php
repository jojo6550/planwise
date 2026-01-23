<?php
/**
 * LessonPlan Class
 * Handles lesson plan CRUD operations
 * CS334 Module 1 + Module 3 - DB manipulation, OOP, Control structures
 */

require_once __DIR__ . '/Database.php';

class LessonPlan
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
     * Create a new lesson plan
     *
     * @param array $data Lesson plan data
     * @return array Result with success status and lesson plan ID
     */
    public function create(array $data): array
    {
        try {
            error_log("LessonPlan::create - Input data: " . json_encode($data));

            // Validate required fields
            $required = ['user_id', 'title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    error_log("LessonPlan::create - Validation failed: Field '{$field}' is empty");
                    return [
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ];
                }
            }

            // Validate title length
            if (strlen($data['title']) < 3) {
                return [
                    'success' => false,
                    'message' => 'Title must be at least 3 characters long'
                ];
            }

            // Validate duration if provided
            if (isset($data['duration']) && $data['duration'] !== '' && (!is_numeric($data['duration']) || $data['duration'] < 0)) {
                return [
                    'success' => false,
                    'message' => 'Duration must be a positive number'
                ];
            }

            $sql = "INSERT INTO lesson_plans (user_id, title, subject, grade_level, duration, objectives, materials, procedures, assessment, notes, status, created_at, updated_at)
                    VALUES (:user_id, :title, :subject, :grade_level, :duration, :objectives, :materials, :procedures, :assessment, :notes, :status, NOW(), NOW())";

            $params = [
                ':user_id' => $data['user_id'],
                ':title' => trim($data['title']),
                ':subject' => trim($data['subject'] ?? ''),
                ':grade_level' => trim($data['grade_level'] ?? ''),
                ':duration' => $data['duration'] ?? null,
                ':objectives' => trim($data['objectives'] ?? ''),
                ':materials' => trim($data['materials'] ?? ''),
                ':procedures' => trim($data['procedures'] ?? ''),
                ':assessment' => trim($data['assessment'] ?? ''),
                ':notes' => trim($data['notes'] ?? ''),
                ':status' => $data['status'] ?? 'draft'
            ];

            $lessonPlanId = $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'Lesson plan created successfully',
                'lesson_plan_id' => $lessonPlanId
            ];

        } catch (Exception $e) {
            error_log("Lesson plan creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create lesson plan'
            ];
        }
    }

    /**
     * Get lesson plan by ID
     *
     * @param int $lessonPlanId Lesson plan ID
     * @param int|null $userId User ID for authorization (optional)
     * @return array|null Lesson plan data
     */
    public function getById(int $lessonPlanId, ?int $userId = null): ?array
    {
        try {
            $sql = "SELECT lp.*, u.first_name, u.last_name, u.email
                    FROM lesson_plans lp
                    JOIN users u ON lp.user_id = u.user_id
                    WHERE lp.lesson_plan_id = :lesson_plan_id";

            $params = [':lesson_plan_id' => $lessonPlanId];

            // Add user restriction if provided
            if ($userId !== null) {
                $sql .= " AND lp.user_id = :user_id";
                $params[':user_id'] = $userId;
            }

            $result = $this->db->fetch($sql, $params);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Get lesson plan failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all lesson plans for a user
     *
     * @param int $userId User ID
     * @param string|null $status Filter by status (optional)
     * @return array Lesson plans
     */
    public function getByUser(int $userId, ?string $status = null): array
    {
        try {
            $sql = "SELECT * FROM lesson_plans WHERE user_id = :user_id";
            $params = [':user_id' => $userId];

            if ($status !== null) {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }

            $sql .= " ORDER BY updated_at DESC";

            return $this->db->fetchAll($sql, $params);

        } catch (Exception $e) {
            error_log("Get lesson plans by user failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update a lesson plan
     *
     * @param int $lessonPlanId Lesson plan ID
     * @param array $data Updated data
     * @param int $userId User ID for authorization
     * @return array Result
     */
    public function update(int $lessonPlanId, array $data, int $userId): array
    {
        try {
            // Check if lesson plan exists and belongs to user
            $existing = $this->getById($lessonPlanId, $userId);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Lesson plan not found or unauthorized'
                ];
            }

            // Validate title if provided
            if (isset($data['title']) && strlen($data['title']) < 3) {
                return [
                    'success' => false,
                    'message' => 'Title must be at least 3 characters long'
                ];
            }

            // Validate duration if provided
            if (isset($data['duration']) && $data['duration'] !== '' && (!is_numeric($data['duration']) || $data['duration'] < 0)) {
                return [
                    'success' => false,
                    'message' => 'Duration must be a positive number'
                ];
            }

            $sql = "UPDATE lesson_plans SET
                    title = :title,
                    subject = :subject,
                    grade_level = :grade_level,
                    duration = :duration,
                    objectives = :objectives,
                    materials = :materials,
                    procedures = :procedures,
                    assessment = :assessment,
                    notes = :notes,
                    status = :status,
                    updated_at = NOW()
                    WHERE lesson_plan_id = :lesson_plan_id AND user_id = :user_id";

            $params = [
                ':lesson_plan_id' => $lessonPlanId,
                ':user_id' => $userId,
                ':title' => trim($data['title'] ?? $existing['title']),
                ':subject' => trim($data['subject'] ?? $existing['subject']),
                ':grade_level' => trim($data['grade_level'] ?? $existing['grade_level']),
                ':duration' => $data['duration'] ?? $existing['duration'],
                ':objectives' => trim($data['objectives'] ?? $existing['objectives']),
                ':materials' => trim($data['materials'] ?? $existing['materials']),
                ':procedures' => trim($data['procedures'] ?? $existing['procedures']),
                ':assessment' => trim($data['assessment'] ?? $existing['assessment']),
                ':notes' => trim($data['notes'] ?? $existing['notes']),
                ':status' => $data['status'] ?? $existing['status']
            ];

            $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'Lesson plan updated successfully'
            ];

        } catch (Exception $e) {
            error_log("Lesson plan update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update lesson plan'
            ];
        }
    }

    /**
     * Delete a lesson plan
     *
     * @param int $lessonPlanId Lesson plan ID
     * @param int $userId User ID for authorization
     * @return array Result
     */
    public function delete(int $lessonPlanId, int $userId): array
    {
        try {
            // Check if lesson plan exists and belongs to user
            $existing = $this->getById($lessonPlanId, $userId);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Lesson plan not found or unauthorized'
                ];
            }

            $sql = "DELETE FROM lesson_plans WHERE lesson_plan_id = :lesson_plan_id AND user_id = :user_id";
            $this->db->delete($sql, [':lesson_plan_id' => $lessonPlanId, ':user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'Lesson plan deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("Lesson plan deletion failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete lesson plan'
            ];
        }
    }

    /**
     * Get statistics for a user's lesson plans
     *
     * @param int $userId User ID
     * @return array Statistics
     */
    public function getStats(int $userId): array
    {
        try {
            $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
                    FROM lesson_plans
                    WHERE user_id = :user_id";

            $result = $this->db->fetch($sql, [':user_id' => $userId]);

            return [
                'total' => (int)($result['total'] ?? 0),
                'published' => (int)($result['published'] ?? 0),
                'drafts' => (int)($result['drafts'] ?? 0),
                'archived' => (int)($result['archived'] ?? 0)
            ];

        } catch (Exception $e) {
            error_log("Get lesson plan stats failed: " . $e->getMessage());
            return [
                'total' => 0,
                'published' => 0,
                'drafts' => 0,
                'archived' => 0
            ];
        }
    }

    /**
     * Get all lesson plans (admin only)
     *
     * @return array All lesson plans
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT lp.*, u.first_name, u.last_name, u.email
                    FROM lesson_plans lp
                    JOIN users u ON lp.user_id = u.user_id
                    ORDER BY lp.updated_at DESC";

            return $this->db->fetchAll($sql);

        } catch (Exception $e) {
            error_log("Get all lesson plans failed: " . $e->getMessage());
            return [];
        }
    }
}
