<?php
/**
 * ImportController
 * Handles CSV data import operations (admin only)
 * CS334 Module 2 - Read .xls/.csv files (22 marks) + Use of Files (10 marks)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/BaseController.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/LessonPlan.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class ImportController extends BaseController
{
    private $auth;
    private $activityLog;

    /**
     * Constructor - require admin authentication
     */
    public function __construct()
    {
        $this->auth        = new Auth();
        $this->activityLog = new ActivityLog();

        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to continue', 'login');
        }

        if (!$this->auth->hasRole(1)) {
            $this->redirectWithError('Access denied. Admin privileges required.', '403');
        }
    }

    /**
     * Handle CSV file upload and import
     * POST ?action=upload
     */
    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
        }

        // Check file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->jsonResponse(['success' => false, 'message' => 'No CSV file uploaded'], 400);
        }

        $file = $_FILES['csv_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'File upload error: ' . $file['error']], 400);
        }

        // Validate file extension (do not trust MIME type)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $this->jsonResponse(['success' => false, 'message' => 'Only CSV files are accepted'], 400);
        }

        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->jsonResponse(['success' => false, 'message' => 'File size exceeds 5MB limit'], 400);
        }

        // Get import options
        $importType   = $_POST['import_type'] ?? 'lesson_plans';
        $validateOnly = isset($_POST['validate_only']) && $_POST['validate_only'] === 'on';

        if (!in_array($importType, ['lesson_plans', 'users'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid import type'], 400);
        }

        // Process the CSV file
        $result = $this->processCSVImport($file['tmp_name'], $importType, $validateOnly);

        // Log activity (only when actually importing, not validate-only)
        if (!$validateOnly && $result['success_count'] > 0) {
            $this->activityLog->log(
                $this->auth->id(),
                ActivityLog::ACTION_LESSON_PLAN_IMPORTED,
                "CSV import ({$importType}): {$result['success_count']} imported, {$result['error_count']} errors"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Download a CSV template for lesson plan imports
     * GET ?action=downloadTemplate
     */
    public function downloadTemplate(): void
    {
        $type = $_GET['type'] ?? 'lesson_plans';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Pragma: no-cache');
        header('Expires: 0');

        if ($type === 'users') {
            header('Content-Disposition: attachment; filename="user_import_template.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($output, ['first_name', 'last_name', 'email', 'password']);
            fputcsv($output, ['Jane', 'Smith', 'jane.smith@school.edu', 'SecurePass123']);
            fclose($output);
        } else {
            header('Content-Disposition: attachment; filename="lesson_plan_template.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($output, ['title', 'subject', 'grade_level', 'duration_minutes', 'objectives', 'materials', 'procedures', 'assessment', 'notes']);
            fputcsv($output, [
                'Introduction to Fractions',
                'Mathematics',
                'Grade 5',
                60,
                'Students will understand basic fraction concepts',
                'Textbook, fraction tiles, whiteboard',
                '1. Introduce fractions. 2. Demonstrate with tiles. 3. Practice problems.',
                'Quiz on fraction identification and equivalence',
                'Differentiate for advanced learners with mixed numbers'
            ]);
            fclose($output);
        }
        exit();
    }

    /**
     * Process a CSV file and import rows into the database
     *
     * @param string $filePath  Temporary file path
     * @param string $type      'lesson_plans' or 'users'
     * @param bool   $validateOnly  If true, validate without writing to DB
     * @return array Result summary
     */
    private function processCSVImport(string $filePath, string $type, bool $validateOnly = false): array
    {
        $successCount = 0;
        $errorCount   = 0;
        $errors       = [];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Could not read uploaded file', 'success_count' => 0, 'error_count' => 0, 'errors' => []];
        }

        // Skip header row
        fgetcsv($handle, 1000, ',');
        $rowNumber = 1;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;

            // Skip completely empty rows
            if (empty(array_filter($data))) {
                continue;
            }

            try {
                if ($type === 'lesson_plans') {
                    $result = $this->importLessonPlan($data, $validateOnly);
                } else {
                    $result = $this->importUser($data, $validateOnly);
                }

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $result['message'];
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $action  = $validateOnly ? 'Validation' : 'Import';
        $message = "{$action} completed. Success: {$successCount}, Errors: {$errorCount}";

        return [
            'success'       => true,
            'message'       => $message,
            'success_count' => $successCount,
            'error_count'   => $errorCount,
            'errors'        => array_slice($errors, 0, 10),
            'validate_only' => $validateOnly,
        ];
    }

    /**
     * Import a single lesson plan row
     * Columns: title, subject, grade_level, duration_minutes, objectives, materials, procedures, assessment, notes
     */
    private function importLessonPlan(array $data, bool $validateOnly = false): array
    {
        $planData = [
            'user_id'     => $this->auth->id(),
            'title'       => trim($data[0] ?? ''),
            'subject'     => trim($data[1] ?? ''),
            'grade_level' => trim($data[2] ?? ''),
            'duration'    => (int)($data[3] ?? 0),
            'objectives'  => trim($data[4] ?? ''),
            'materials'   => trim($data[5] ?? ''),
            'procedures'  => trim($data[6] ?? ''),
            'assessment'  => trim($data[7] ?? ''),
            'notes'       => trim($data[8] ?? ''),
            'status'      => 'draft',
        ];

        if (empty($planData['title'])) {
            return ['success' => false, 'message' => 'Title is required'];
        }

        if ($validateOnly) {
            return ['success' => true];
        }

        $lessonPlan = new LessonPlan();
        return $lessonPlan->create($planData);
    }

    /**
     * Import a single user row
     * Columns: first_name, last_name, email, password
     */
    private function importUser(array $data, bool $validateOnly = false): array
    {
        $userData = [
            'first_name' => trim($data[0] ?? ''),
            'last_name'  => trim($data[1] ?? ''),
            'email'      => trim($data[2] ?? ''),
            'password'   => trim($data[3] ?? ''),
            'role_id'    => 2, // Default to teacher
            'status'     => 'active',
        ];

        if (empty($userData['first_name']) || empty($userData['last_name'])) {
            return ['success' => false, 'message' => 'First name and last name are required'];
        }

        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Valid email is required'];
        }

        if (empty($userData['password']) || strlen($userData['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }

        if ($validateOnly) {
            return ['success' => true];
        }

        $user = new User();
        return $user->create($userData);
    }
}

// Handle direct requests
if (basename($_SERVER['PHP_SELF']) === 'ImportController.php') {
    $controller = new ImportController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'upload':
            $controller->upload();
            break;
        case 'downloadTemplate':
            $controller->downloadTemplate();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
