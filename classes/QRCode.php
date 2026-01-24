<?php
/**
 * QRCode Class
 * Handles QR code generation and management for lesson plans
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

class QRCode
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
     * Generate QR code for a lesson plan
     * Uses chillerlan/php-qrcode library for proper QR code generation
     *
     * @param int $lessonPlanId Lesson plan ID
     * @return array Result with success status
     */
    public function generate(int $lessonPlanId): array
    {
        try {
            // Generate QR code data (URL to view lesson plan)
            $qrData = "/planwise/public/index.php?page=teacher/lesson-plans/view&id=" . $lessonPlanId;

            // Generate unique filename for QR image
            $fileName = 'qr_' . $lessonPlanId . '_' . time() . '.png';
            $filePath = __DIR__ . '/../public/qr/' . $fileName;

            // Ensure QR directory exists
            $qrDir = dirname($filePath);
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }

            // Generate QR code using chillerlan/php-qrcode library
            $qrCode = new \chillerlan\QRCode\QRCode();
            $qrCode->render($qrData, $filePath);

            // Verify file was created
            if (!file_exists($filePath)) {
                throw new Exception('QR code image file was not created');
            }

            // Store QR code data in database
            $sql = "INSERT INTO qr_codes (lesson_id, qr_path, generated_at)
                    VALUES (:lesson_id, :qr_path, NOW())";

            $params = [
                ':lesson_id' => $lessonPlanId,
                ':qr_path' => $filePath
            ];

            $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'QR code generated successfully',
                'qr_image_path' => $filePath,
                'qr_data' => $qrData
            ];

        } catch (Exception $e) {
            error_log("QR code generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code by lesson plan ID
     *
     * @param int $lessonPlanId Lesson plan ID
     * @return array|null QR code data
     */
    public function getByLessonPlanId(int $lessonPlanId): ?array
    {
        try {
            $sql = "SELECT * FROM qr_codes WHERE lesson_id = :lesson_id ORDER BY generated_at DESC LIMIT 1";
            $result = $this->db->fetch($sql, [':lesson_id' => $lessonPlanId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Get QR code failed: " . $e->getMessage());
            return null;
        }
    }
}
