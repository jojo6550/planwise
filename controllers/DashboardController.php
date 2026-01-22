<?php
/**
 * DashboardController
 * Handles dashboard access and displays
 */

// Require necessary classes
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';

class DashboardController
{
    private $auth;

    /**
     * Constructor - Initialize Auth class
     */
    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Display dashboard for authenticated users
     * 
     * @return void
     */
    public function index()
    {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            // Redirect to login if not authenticated
            $_SESSION['error'] = 'Please login to access the dashboard';
            header('Location: /public/index.php?page=login');
            exit();
        }

        // Get current user data
        $user = $this->auth->user();

        // Redirect to appropriate dashboard based on role
        if ($user['role_id'] == 1) {
            // Admin role - load admin dashboard
            $this->loadView('admin/dashboard', $user);
        } else {
            // Teacher role - load teacher dashboard
            $this->loadView('teacher/dashboard', $user);
        }
    }

    /**
     * Display teacher dashboard
     * 
     * @return void
     */
    public function teacher()
    {
        // Require authentication
        $this->auth->requireAuth('/public/index.php?page=login');

        // Get current user data
        $user = $this->auth->user();

        // Load teacher dashboard view
        $this->loadView('teacher/dashboard', $user);
    }

    /**
     * Display admin dashboard
     * 
     * @return void
     */
    public function admin()
    {
        // Require authentication
        $this->auth->requireAuth('/public/index.php?page=login');

        // Require admin role
        $this->auth->requireRole(1, '/public/index.php?page=403');

        // Get current user data
        $user = $this->auth->user();

        // Load admin dashboard view
        $this->loadView('admin/dashboard', $user);
    }

    /**
     * Load a view file
     * 
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     * @return void
     */
    private function loadView(string $view, array $data = [])
    {
        // Extract data to make variables available in view
        extract($data);

        // Build view file path
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        // Check if view file exists
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // View not found - show 404
            header('HTTP/1.0 404 Not Found');
            echo "View not found: {$view}";
            exit();
        }
    }

    /**
     * Check if user is authenticated (for AJAX requests)
     * 
     * @return void
     */
    public function checkAuth()
    {
        header('Content-Type: application/json');
        
        if ($this->auth->check()) {
            echo json_encode([
                'authenticated' => true,
                'user' => $this->auth->user()
            ]);
        } else {
            echo json_encode([
                'authenticated' => false,
                'message' => 'Not authenticated'
            ]);
        }
        exit();
    }
}

// Handle direct requests to this controller
if (basename($_SERVER['PHP_SELF']) === 'DashboardController.php') {
    $controller = new DashboardController();
    $action = $_GET['action'] ?? 'index';

    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'teacher':
            $controller->teacher();
            break;
        case 'admin':
            $controller->admin();
            break;
        case 'check-auth':
            $controller->checkAuth();
            break;
        default:
            $controller->index();
            break;
    }
}
