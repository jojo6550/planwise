<?php
/**
 * LessonSection Class
 * Handles lesson plan sections
 * CS334 Module 3 - OOP, DB manipulation
 */

require_once __DIR__ . '/Database.php';

class LessonSection
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
     * Create a new lesson section
     *
     * @param array $data Section data
     * @return array Result
     */
    public function create(array $data): array
    {
        try {
            $sql = "INSERT INTO lesson_sections (lesson_plan_id, section_type, title, content, duration, order_position, created_at, updated_at)
                    VALUES (:lesson_plan_id, :section_type, :title, :content, :duration, :order_position, NOW(), NOW())";

            $params = [
                ':lesson_plan_id' => $data['lesson_plan_id'],
                ':section_type' => $data['section_type'],
                ':title' => trim($data['title']),
                ':content' => trim($data['content'] ?? ''),
                ':duration' => $data['duration'] ?? null,
                ':order_position' => $data['order_position'] ?? 0
            ];

            $sectionId = $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'Section created successfully',
                'section_id' => $sectionId
            ];

        } catch (Exception $e) {
            error_log("Section creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create section'
            ];
        }
    }

    /**
     * Get sections by lesson plan ID
     *
     * @param int $lessonPlanId Lesson plan ID
     * @return array Sections
     */
    public function getByLessonPlan(int $lessonPlanId): array
    {
        try {
            $sql = "SELECT * FROM lesson_sections
                    WHERE lesson_id = :lesson_plan_id
                    ORDER BY order_position ASC";

            return $this->db->fetchAll($sql, [':lesson_plan_id' => $lessonPlanId]);

        } catch (Exception $e) {
            error_log("Get sections failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update a section
     *
     * @param int $sectionId Section ID
     * @param array $data Updated data
     * @return array Result
     */
    public function update(int $sectionId, array $data): array
    {
        try {
            $sql = "UPDATE lesson_sections SET
                    section_type = :section_type,
                    title = :title,
                    content = :content,
                    duration = :duration,
                    order_position = :order_position,
                    updated_at = NOW()
                    WHERE section_id = :section_id";

            $params = [
                ':section_id' => $sectionId,
                ':section_type' => $data['section_type'],
                ':title' => trim($data['title']),
                ':content' => trim($data['content'] ?? ''),
                ':duration' => $data['duration'] ?? null,
                ':order_position' => $data['order_position'] ?? 0
            ];

            $this->db->update($sql, $params);

            return [
                'success' => true,
                'message' => 'Section updated successfully'
            ];

        } catch (Exception $e) {
            error_log("Section update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update section'
            ];
        }
    }

    /**
     * Delete a section
     *
     * @param int $sectionId Section ID
     * @return array Result
     */
    public function delete(int $sectionId): array
    {
        try {
            $sql = "DELETE FROM lesson_sections WHERE section_id = :section_id";
            $this->db->delete($sql, [':section_id' => $sectionId]);

            return [
                'success' => true,
                'message' => 'Section deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("Section deletion failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete section'
            ];
        }
    }
}
