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

class LessonPlanController
{
    private $auth;
    private $lessonPlan;
    private $lessonSection;
    private $activityLog;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->auth = new Auth();
        $this->lessonPlan = new LessonPlan();
        $this->lessonSection = new LessonSection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Create a new lesson plan
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method', 'teacher/lesson-plans');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError('Invalid security token', 'teacher/lesson-plans');
            return;
        }

        // Get user ID
        $userId = $this->auth->id();

        // Prepare data
        $data = [
            'user_id' => $userId,
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

        error_log("LessonPlanController::create - Prepared data: " . json_encode($data));

        // Create lesson plan
        $result = $this->lessonPlan->create($data);
        error_log("LessonPlanController::create - Create result: " . json_encode($result));

        if ($result['success']) {
            $lessonPlanId = $result['lesson_plan_id'];

            // Create sections if provided
            if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                foreach ($_POST['sections'] as $sectionData) {
                    if (!empty($sectionData['title'])) {
                        $sectionResult = $this->lessonSection->create([
                            'lesson_plan_id' => $lessonPlanId,
                            'section_type' => $sectionData['section_type'] ?? 'introduction',
                            'title' => $this->sanitize($sectionData['title']),
                            'content' => $this->sanitize($sectionData['content'] ?? ''),
                            'duration' => $sectionData['duration'] ?? null,
                            'order_position' => $sectionData['order_position'] ?? 0
                        ]);
                        if (!$sectionResult['success']) {
                            // Log error but continue
                            error_log("Failed to create section: " . $sectionResult['message']);
                        }
                    }
                }
            }

            // Log activity
            $this->activityLog->log(
                $userId,
                'lesson_plan_created',
                "Created lesson plan: {$data['title']}"
            );

            $this->redirectWithSuccess('Lesson plan created successfully', 'teacher/lesson-plans');
        } else {
            $this->redirectWithError($result['message'], 'teacher/lesson-plans/create');
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

        $lessonPlanId = (int)($_POST['lesson_plan_id'] ?? 0);
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
     * Delete a lesson plan (AJAX)
     */
    public function delete()
    {
        if (!$this->auth->check()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $lessonPlanId = (int)($input['lesson_plan_id'] ?? 0);
        $userId = $this->auth->id();

        if ($lessonPlanId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid lesson plan ID'], 400);
            return;
        }

        // Delete lesson plan
        $result = $this->lessonPlan->delete($lessonPlanId, $userId);

        if ($result['success']) {
            // Log activity
            $this->activityLog->log(
                $userId,
                'lesson_plan_deleted',
                "Deleted lesson plan ID: {$lessonPlanId}"
            );
        }

        $this->jsonResponse($result);
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

        // Mock file and QR code handling (assuming classes exist)
        $files = []; // $fileHandler->getByLessonPlan($lessonPlanId);
        $qr = null; // $qrCode->getByLessonPlanId($lessonPlanId);

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
