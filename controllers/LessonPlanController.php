<?php
/**
 * LessonPlanController
 * Handles lesson plan CRUD operations
 * CS334 Module 1 + Module 3 - Input validation (40), AJAX (10), Control structures (18)
 */

session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/LessonPlan.php';
require_once __DIR__ . '/../classes/LessonSection.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/QRCode.php';

class LessonPlanController
{
    private $auth;
    private $lessonPlan;
    private $lessonSection;
    private $activityLog;
    private $qrCode;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->lessonPlan = new LessonPlan();
        $this->lessonSection = new LessonSection();
        $this->activityLog = new ActivityLog();
        $this->qrCode = new QRCode();
    }

    /**
     * Create a new lesson plan
     */
    public function create()
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to create lesson plans', 'login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'teacher/lesson-plans');
            return;
        }

        // Handle "Add Section" logic before processing save
        if (isset($_POST['add_section'])) {
            $this->handleSessionState($_POST);
            header("Location: /planwise/public/index.php?page=teacher/lesson-plans/create");
            exit();
        }

        $inputData = $_POST;

        // Validate CSRF token
        if (!$this->validateCsrfToken($inputData['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'teacher/lesson-plans');
            return;
        }

        // Get user ID
        $userId = $this->auth->id();

        // Prepare data
        $data = [
            'user_id' => $userId,
            'title' => $this->sanitize($inputData['title'] ?? ''),
            'subject' => $this->sanitize($inputData['subject'] ?? ''),
            'grade_level' => $this->sanitize($inputData['grade_level'] ?? ''),
            'duration' => $inputData['duration'] ?? null,
            'objectives' => $this->sanitize($inputData['objectives'] ?? ''),
            'materials' => $this->sanitize($inputData['materials'] ?? ''),
            'procedures' => $this->sanitize($inputData['procedures'] ?? ''),
            'assessment' => $this->sanitize($inputData['assessment'] ?? ''),
            'notes' => $this->sanitize($inputData['notes'] ?? ''),
            'status' => $inputData['status'] ?? 'draft'
        ];

        // Server-side validation using Validator class
        $validator = new Validator();
        $validationRules = [
            'title' => ['required', 'min:3', 'max:255'],
            'subject' => ['max:100'],
            'grade_level' => ['max:50'],
            'duration' => ['numeric'],
            'objectives' => ['max:1000'],
            'materials' => ['max:1000'],
            'procedures' => ['max:2000'],
            'assessment' => ['max:1000'],
            'notes' => ['max:1000']
        ];

        if (!$validator->validate($data, $validationRules)) {
            $errors = $validator->getAllErrors();
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $inputData;
            $this->redirectWithError(implode(', ', $errors), 'teacher/lesson-plans/create');
            return;
        }

        error_log("LessonPlanController::create - Prepared data: " . json_encode($data));

        // Create lesson plan
        $result = $this->lessonPlan->create($data);
        error_log("LessonPlanController::create - Create result: " . json_encode($result));

        if ($result['success']) {
            $lessonPlanId = $result['lesson_id'];

            // Generate QR code for the lesson plan
            $this->qrCode->generate($lessonPlanId);

            // Create sections if provided - demonstrate foreach loop
            if (isset($inputData['sections']) && is_array($inputData['sections']) && count($inputData['sections']) > 0) {
                $sectionCount = 0;
                foreach ($inputData['sections'] as $sectionKey => $sectionData) {
                    // Ensure sectionData is an array
                    if (!is_array($sectionData)) {
                        error_log("Section data is not an array for key: {$sectionKey}");
                        continue;
                    }
                    
                    // Only create section if it has a title
                    if (!empty($sectionData['title'])) {
                        $sectionResult = $this->lessonSection->create([
                            'lesson_id' => $lessonPlanId,
                            'section_type' => isset($sectionData['section_type']) ? $sectionData['section_type'] : 'introduction',
                            'title' => $this->sanitize($sectionData['title']),
                            'content' => isset($sectionData['content']) ? $this->sanitize($sectionData['content']) : '',
                            'duration' => isset($sectionData['duration']) && $sectionData['duration'] !== '' ? (int)$sectionData['duration'] : null,
                            'order_position' => isset($sectionData['order_position']) && $sectionData['order_position'] !== '' ? (int)$sectionData['order_position'] : 0
                        ]);
                        if (!$sectionResult['success']) {
                            // Log error but continue
                            error_log("Failed to create section: " . $sectionResult['message']);
                        } else {
                            $sectionCount++;
                        }
                    }
                }

                // Demonstrate while loop - log section creation count
                if ($sectionCount > 0) {
                    $logMessage = "Created lesson plan with {$sectionCount} sections";
                    $i = 0;
                    while ($i < $sectionCount && $i < 5) { // Log up to 5 sections
                        $logMessage .= " (section " . ($i + 1) . ")";
                        $i++;
                    }
                    if ($sectionCount > 5) {
                        $logMessage .= " and " . ($sectionCount - 5) . " more";
                    }
                    $this->activityLog->log($userId, 'lesson_plan_sections_created', $logMessage);
                }
            }

            // Log activity
            $this->activityLog->log(
                $userId,
                'lesson_plan_created',
                "Created lesson plan: {$data['title']}"
            );

            unset($_SESSION['old_input']);
            $this->redirectWithSuccess('Lesson plan created successfully', 'teacher/lesson-plans');
        } else {
            $this->redirectWithError($result['message'], 'teacher/lesson-plans/create');
        }
    }

    /**
     * Handle Session State for non-JS dynamic forms
     */
    private function handleSessionState(array $data)
    {
        $_SESSION['old_input'] = $data;
        if (isset($data['add_section'])) {
            $sections = $data['sections'] ?? [];
            $nextIndex = count($sections) + 1;
            $_SESSION['old_input']['sections'][$nextIndex] = [
                'section_type' => 'introduction',
                'title' => '',
                'content' => '',
                'duration' => 0,
                'order_position' => $nextIndex
            ];
        }
        if (isset($data['remove_section'])) {
            $indexToRemove = $data['remove_section'];
            if (isset($_SESSION['old_input']['sections'][$indexToRemove])) {
                unset($_SESSION['old_input']['sections'][$indexToRemove]);
            }
        }
    }

    /**
     * Update a lesson plan
     */
    public function update()
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to update lesson plans', 'login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'teacher/lesson-plans');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'teacher/lesson-plans');
            return;
        }

        $lessonPlanId = (int)($_POST['lesson_id'] ?? 0);
        $userId = $this->auth->id();

        // Prepare data
        $data = [
            'title' => $this->sanitize($_POST['title'] ?? ''),
            'subject' => $this->sanitize($_POST['subject'] ?? ''),
            'grade_level' => $this->sanitize($_POST['grade_level'] ?? ''),
            'duration' => $_POST['duration'] ?? null,
            'objectives' => $this->sanitize($_POST['objectives'] ?? ''),
            'materials' => $this->sanitize($_POST['materials'] ?? ''),
            'procedures' => $this->sanitize($_POST['procedures'] ?? ''),
            'assessment' => $this->sanitize($_POST['assessment'] ?? ''),
            'notes' => $this->sanitize($_POST['notes'] ?? ''),
            'status' => $_POST['status'] ?? 'draft'
        ];

        // Update lesson plan
        $result = $this->lessonPlan->update($lessonPlanId, $data, $userId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'lesson_plan_updated',
                "Updated lesson plan ID: {$lessonPlanId}"
            );

            $this->redirectWithSuccess('Lesson plan updated successfully', 'teacher/lesson-plans');
        } else {
            $this->redirectWithError($result['message'], 'teacher/lesson-plans/edit&id=' . $lessonPlanId);
        }
    }

    /**
     * Delete a lesson plan
     */
    public function delete()
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Unauthorized', 'teacher/lesson-plans');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'teacher/lesson-plans');
            return;
        }

        $lessonPlanId = (int)($_POST['lesson_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->redirectWithError('Invalid lesson plan ID', 'teacher/lesson-plans');
            return;
        }

        // Delete lesson plan
        $result = $this->lessonPlan->delete($lessonPlanId, $userId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log($userId, 'lesson_plan_deleted', "Deleted lesson plan ID: {$lessonPlanId}");
            $this->redirectWithSuccess('Lesson plan deleted successfully', 'teacher/lesson-plans');
        } else {
            $this->redirectWithError($result['message'], 'teacher/lesson-plans');
        }
    }

    /**
     * Get lesson plan data (AJAX)
     */
    public function get()
    {
        $lessonPlanId = (int)($_GET['id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid lesson plan ID'], 400);
            return;
        }

        $plan = $this->lessonPlan->getById($lessonPlanId, $userId);

        if ($plan) {
            $this->jsonResponse(['success' => true, 'data' => $plan]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Lesson plan not found'], 404);
        }
    }

    /**
     * Get all lesson plans for user (AJAX)
     */
    public function getAll()
    {
        if (!$this->auth->check()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $userId = $this->auth->id();
        $status = $_GET['status'] ?? null;

        $plans = $this->lessonPlan->getByUser($userId, $status);
        $this->jsonResponse(['success' => true, 'data' => $plans]);
    }

    /**
     * Index page - list user's lesson plans
     */
    public function index()
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to access lesson plans', 'login');
            return;
        }

        $data = $this->getIndexData();
        extract($data);

        require_once __DIR__ . '/../views/teacher/lesson-plans/index.php';
    }

    /**
     * View lesson plan
     */
    public function view($id)
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to view lesson plans', 'login');
            return;
        }

        $data = $this->getViewData($id);
        if (!$data) {
            $this->redirectWithError('Lesson plan not found', 'teacher/lesson-plans');
            return;
        }

        extract($data);

        require_once __DIR__ . '/../views/teacher/lesson-plans/view.php';
    }

    /**
     * Edit lesson plan
     */
    public function edit($id)
    {
        if (!$this->auth->check()) {
            $this->redirectWithError('Please login to edit lesson plans', 'login');
            return;
        }

        $data = $this->getEditData($id);
        if (!$data) {
            $this->redirectWithError('Lesson plan not found', 'teacher/lesson-plans');
            return;
        }

        extract($data);

        require_once __DIR__ . '/../views/teacher/lesson-plans/edit.php';
    }

    /**
     * Get data for index page
     */
    private function getIndexData(): array
    {
        $user = $this->auth->user();
        $lessonPlans = $this->lessonPlan->getByUser($user['user_id']);
        $stats = $this->lessonPlan->getStats($user['user_id']);
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        return compact('user', 'lessonPlans', 'stats', 'success', 'error');
    }

    /**
     * Get data for view page
     */
    private function getViewData(int $lessonPlanId): ?array
    {
        $user = $this->auth->user();
        $plan = $this->lessonPlan->getById($lessonPlanId, $user['user_id']);
        if (!$plan) {
            return null;
        }

        $sections = $this->lessonSection->getByLessonPlan($lessonPlanId);

        // Load files and QR code
        $files = []; // $fileHandler->getByLessonPlan($lessonPlanId); // TODO: Implement file handling
        $qr = $this->qrCode->getByLessonPlanId($lessonPlanId);

        return compact('plan', 'sections', 'files', 'qr');
    }

    /**
     * Get data for edit page
     */
    private function getEditData(int $lessonPlanId): ?array
    {
        $user = $this->auth->user();
        $plan = $this->lessonPlan->getById($lessonPlanId, $user['user_id']);
        if (!$plan) {
            return null;
        }

        $sections = $this->lessonSection->getByLessonPlan($lessonPlanId);
        $csrfToken = AuthController::generateCsrfToken();
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        return compact('plan', 'sections', 'csrfToken', 'error');
    }

    /**
     * Sanitize input
     */
    private function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Redirect with error message
     */
    private function redirectWithError(string $message, string $page)
    {
        $_SESSION['error'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Redirect with success message
     */
    private function redirectWithSuccess(string $message, string $page)
    {
        $_SESSION['success'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Import lesson plans from CSV file
     */
    public function importCsv()
    {
        if (!$this->auth->check()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        // Check if admin
        $user = $this->auth->user();
        if ($user['role_id'] != 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Admin access required'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'message' => 'No CSV file uploaded'], 400);
            return;
        }

        $file = $_FILES['csv_file'];
        $hasHeaders = isset($_POST['has_headers']) && $_POST['has_headers'] === 'on';

        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes) && !preg_match('/\.csv$/i', $file['name'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'], 400);
            return;
        }

        // Read CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to read CSV file'], 400);
            return;
        }

        $imported = 0;
        $errors = [];
        $rowNumber = 0;

        // Skip header row if specified
        if ($hasHeaders) {
            fgetcsv($handle);
            $rowNumber++;
        }

        // Process each row
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Validate row has required columns
            if (count($data) < 10) {
                $errors[] = "Row $rowNumber: Insufficient columns";
                continue;
            }

            // Map CSV columns to lesson plan data
            $lessonData = [
                'user_id' => $user['user_id'], // Import as admin user
                'title' => $this->sanitize($data[0] ?? ''),
                'subject' => $this->sanitize($data[1] ?? ''),
                'grade_level' => $this->sanitize($data[2] ?? ''),
                'duration' => !empty($data[3]) ? (int)$data[3] : null,
                'objectives' => $this->sanitize($data[4] ?? ''),
                'materials' => $this->sanitize($data[5] ?? ''),
                'procedures' => $this->sanitize($data[6] ?? ''),
                'assessment' => $this->sanitize($data[7] ?? ''),
                'notes' => $this->sanitize($data[8] ?? ''),
                'status' => $this->sanitize($data[9] ?? 'draft')
            ];

            // Validate required fields
            if (empty($lessonData['title'])) {
                $errors[] = "Row $rowNumber: Title is required";
                continue;
            }

            // Create lesson plan
            $result = $this->lessonPlan->create($lessonData);

            if ($result['success']) {
                $imported++;

                // Log activity
                $this->activityLog->log(
                    $user['user_id'],
                    'lesson_plan_imported',
                    "Imported lesson plan: {$lessonData['title']}"
                );
            } else {
                $errors[] = "Row $rowNumber: {$result['message']}";
            }
        }

        fclose($handle);

        $this->jsonResponse([
            'success' => true,
            'message' => 'CSV import completed',
            'imported' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
if (basename($_SERVER['PHP_SELF']) === 'LessonPlanController.php') {
    $controller = new LessonPlanController();
    $action = $_GET['action'] ?? '';
    $id = (int)($_GET['id'] ?? 0);

    switch ($action) {
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'get':
            $controller->get();
            break;
        case 'getAll':
            $controller->getAll();
            break;
        case 'index':
            $controller->index();
            break;
        case 'view':
            if ($id > 0) {
                $controller->view($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            break;
        case 'edit':
            if ($id > 0) {
                $controller->edit($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
