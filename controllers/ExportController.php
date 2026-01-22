<?php
/**
 * ExportController
 * Handles PDF export operations
 * CS334 Module 2 - Generate PDF reports (22), Use of Files (10)
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/PDFExporter.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class ExportController
{
    private $auth;
    private $pdfExporter;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->pdfExporter = new PDFExporter();
        $this->activityLog = new ActivityLog();

        // Require authentication
        if (!$this->auth->check()) {
            http_response_code(401);
            echo 'Unauthorized access';
            exit();
        }
    }

    /**
     * Export lesson plan to PDF
     */
    public function exportPDF()
    {
        $lessonPlanId = (int)($_GET['id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            http_response_code(400);
            echo 'Invalid lesson plan ID';
            exit();
        }

        // Generate PDF (download mode)
        $result = $this->pdfExporter->generateLessonPlanPDF($lessonPlanId, $userId, true);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'pdf_exported',
                "Exported lesson plan ID: {$lessonPlanId} to PDF"
            );
        } else {
            http_response_code(500);
            echo 'Failed to generate PDF: ' . $result['message'];
            exit();
        }
    }

    /**
     * Save lesson plan PDF to server
     */
    public function savePDF()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $lessonPlanId = (int)($input['lesson_plan_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid lesson plan ID'], 400);
            return;
        }

        // Generate PDF (save mode)
        $result = $this->pdfExporter->generateLessonPlanPDF($lessonPlanId, $userId, false);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'pdf_saved',
                "Saved lesson plan ID: {$lessonPlanId} to PDF"
            );
        }

        $this->jsonResponse($result);
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
if (basename($_SERVER['PHP_SELF']) === 'ExportController.php') {
    $controller = new ExportController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'exportPDF':
            $controller->exportPDF();
            break;
        case 'savePDF':
            $controller->savePDF();
            break;
        default:
            http_response_code(404);
            echo 'Invalid action';
            exit();
    }
}
