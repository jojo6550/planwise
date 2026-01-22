<?php
/**
 * QRCode Class
 * Handles QR code generation and management for lesson plans
 */

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

            // Generate QR code image using a library (assuming you have a QR library installed)
            // For now, we'll create a placeholder - you may need to install a QR code library like chillerlan/php-qrcode
            // This is a basic implementation - in production, use a proper QR code library

            // Placeholder: Create a simple image (you should replace this with actual QR generation)
            $image = imagecreatetruecolor(200, 200);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefill($image, 0, 0, $white);

            // Add some basic pattern (placeholder)
            for ($x = 20; $x < 180; $x += 20) {
                for ($y = 20; $y < 180; $y += 20) {
                    if (($x + $y) % 40 == 0) {
                        imagefilledrectangle($image, $x, $y, $x + 10, $y + 10, $black);
                    }
                }
            }

            // Add text
            imagestring($image, 5, 50, 90, 'QR Code', $black);
            imagestring($image, 3, 30, 110, 'Lesson Plan ' . $lessonPlanId, $black);

            // Save image
            imagepng($image, $filePath);
            imagedestroy($image);

            // Store QR code data in database
            $sql = "INSERT INTO qr_codes (lesson_plan_id, qr_code_data, qr_image_path, created_at)
                    VALUES (:lesson_plan_id, :qr_code_data, :qr_image_path, NOW())";

            $params = [
                ':lesson_plan_id' => $lessonPlanId,
                ':qr_code_data' => $qrData,
                ':qr_image_path' => $filePath
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
                'message' => 'Failed to generate QR code'
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
            $sql = "SELECT * FROM qr_codes WHERE lesson_plan_id = :lesson_plan_id ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->fetch($sql, [':lesson_plan_id' => $lessonPlanId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Get QR code failed: " . $e->getMessage());
            return null;
        }
    }
}
