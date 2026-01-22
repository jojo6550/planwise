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

        // Require authentication
        if (!$this->auth->check()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }
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

        // Create lesson plan
        $result = $this->lessonPlan->create($data);

        if ($result['success']) {
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
        $userId = $this->auth->id();
        $status = $_GET['status'] ?? null;

        $plans = $this->lessonPlan->getByUser($userId, $status);
        $this->jsonResponse(['success' => true, 'data' => $plans]);
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
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
