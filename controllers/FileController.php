<?php
/**
 * FileController
 * Handles file upload and management operations
 * CS334 Module 2 - Use of Files (10), Upload images (10), Built-in PHP functions (5)
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/File.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class FileController
{
    private $auth;
    private $fileHandler;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->fileHandler = new File();
        $this->activityLog = new ActivityLog();

        // Require authentication
        if (!$this->auth->check()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }
    }

    /**
     * Upload file
     */
    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->jsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
            return;
        }

        $userId = $this->auth->id();
        $lessonPlanId = isset($_POST['lesson_plan_id']) && $_POST['lesson_plan_id'] !== '' ? (int)$_POST['lesson_plan_id'] : null;

        // Upload file
        $result = $this->fileHandler->upload($_FILES['file'], $userId, $lessonPlanId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'file_uploaded',
                "Uploaded file: {$result['original_name']}" . ($lessonPlanId ? " to lesson plan ID: {$lessonPlanId}" : '')
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Delete file
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $fileId = (int)($input['file_id'] ?? 0);
        $userId = $this->auth->id();

        if ($fileId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid file ID'], 400);
            return;
        }

        // Delete file
        $result = $this->fileHandler->delete($fileId, $userId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'file_deleted',
                "Deleted file ID: {$fileId}"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Download file
     */
    public function download()
    {
        $fileId = (int)($_GET['id'] ?? 0);

        if ($fileId <= 0) {
            http_response_code(400);
            echo 'Invalid file ID';
            exit();
        }

        // Get file data
        $file = $this->fileHandler->getById($fileId);

        if (!$file || !file_exists($file['file_path'])) {
            http_response_code(404);
            echo 'File not found';
            exit();
        }

        // Log activity
        $this->activityLog->log(
            $this->auth->id(),
            'file_downloaded',
            "Downloaded file: {$file['original_name']}"
        );

        // Set headers for download
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        // Output file
        readfile($file['file_path']);
        exit();
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
if (basename($_SERVER['PHP_SELF']) === 'FileController.php') {
    $controller = new FileController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'upload':
            $controller->upload();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'download':
            $controller->download();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
