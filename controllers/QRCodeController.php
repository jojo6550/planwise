<?php
/**
 * QRCodeController
 * Handles QR code generation and management for lesson plans
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/QRCode.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class QRCodeController
{
    private $auth;
    private $qrCode;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->qrCode = new QRCode();
        $this->activityLog = new ActivityLog();

        // Require authentication
        if (!$this->auth->check()) {
            http_response_code(401);
            echo 'Unauthorized access';
            exit();
        }
    }

    /**
     * Generate QR code for a lesson plan (AJAX POST)
     */
    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $lessonPlanId = (int)($input['lesson_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid lesson plan ID'], 400);
            return;
        }

        // Check if user owns the lesson plan
        $lessonPlan = $this->getLessonPlan($lessonPlanId, $userId);
        if (!$lessonPlan) {
            $this->jsonResponse(['success' => false, 'message' => 'Lesson plan not found or access denied'], 404);
            return;
        }

        // Generate QR code
        $result = $this->qrCode->generate($lessonPlanId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'qr_code_generated',
                "Generated QR code for lesson plan ID: {$lessonPlanId}"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Get QR code data for a lesson plan (AJAX GET)
     */
    public function get()
    {
        $lessonPlanId = (int)($_GET['lesson_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid lesson plan ID'], 400);
            return;
        }

        // Check if user owns the lesson plan
        $lessonPlan = $this->getLessonPlan($lessonPlanId, $userId);
        if (!$lessonPlan) {
            $this->jsonResponse(['success' => false, 'message' => 'Lesson plan not found or access denied'], 404);
            return;
        }

        // Get QR code data
        $qrData = $this->qrCode->getByLessonPlanId($lessonPlanId);

        if ($qrData) {
            $this->jsonResponse([
                'success' => true,
                'qr_code' => $qrData
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No QR code found for this lesson plan'
            ], 404);
        }
    }

    /**
     * Display QR code image
     */
    public function display()
    {
        $lessonPlanId = (int)($_GET['lesson_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            http_response_code(400);
            echo 'Invalid lesson plan ID';
            exit();
        }

        // Check if user owns the lesson plan
        $lessonPlan = $this->getLessonPlan($lessonPlanId, $userId);
        if (!$lessonPlan) {
            http_response_code(404);
            echo 'Lesson plan not found or access denied';
            exit();
        }

        // Get QR code data
        $qrData = $this->qrCode->getByLessonPlanId($lessonPlanId);

        if (!$qrData || empty($qrData['qr_image_path'])) {
            http_response_code(404);
            echo 'QR code not found';
            exit();
        }

        $imagePath = $qrData['qr_image_path'];

        // Check if file exists
        if (!file_exists($imagePath)) {
            http_response_code(404);
            echo 'QR code image file not found';
            exit();
        }

        // Set appropriate headers for image display
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($imagePath));
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

        // Output the image
        readfile($imagePath);
        exit();
    }

    /**
     * Get lesson plan by ID and user ID
     */
    private function getLessonPlan(int $lessonPlanId, int $userId): ?array
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM lesson_plans WHERE lesson_id = :lesson_id AND user_id = :user_id";
        return $db->fetch($sql, [':lesson_id' => $lessonPlanId, ':user_id' => $userId]);
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

// Handle direct requests
if (basename($_SERVER['PHP_SELF']) === 'QRCodeController.php') {
    $controller = new QRCodeController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'generate':
            $controller->generate();
            break;
        case 'get':
            $controller->get();
            break;
        case 'display':
            $controller->display();
            break;
        default:
            http_response_code(404);
            echo 'Invalid action';
            exit();
    }
}
