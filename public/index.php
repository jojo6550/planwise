<?php
/**
 * PlanWise - Application Entry Point
 * Handles routing and includes appropriate views
 */

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad() skips missing .env files (Railway uses dashboard env vars)

// Global error handler for production
function errorHandler($errno, $errstr, $errfile, $errline) {
    // Log error to stderr for Render visibility
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline", 4);

    // Don't display errors in production
    if (ini_get('display_errors') == '1') {
        return false;
    }

    // For fatal errors, show custom 500 page
    if ($errno == E_ERROR || $errno == E_PARSE || $errno == E_CORE_ERROR ||
        $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR) {
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
        exit();
    }

    return true;
}

// Set error handler
set_error_handler('errorHandler');

// Custom exception handler for graceful error handling
function exceptionHandler($exception) {
    $message = $exception->getMessage();
    if (strpos($message, 'Could not connect to database') !== false ||
        strpos($message, 'Database configuration error') !== false) {
        http_response_code(503); // Service Unavailable
        include __DIR__ . '/../views/errors/database.php';
        exit();
    } else {
        // Default 500 error
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
        exit();
    }
}
set_exception_handler('exceptionHandler');

// Fatal error handler
function fatalErrorHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'], 4);
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
        exit();
    }
}
register_shutdown_function('fatalErrorHandler');

// Configure error reporting for production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Log errors to stderr for Render
ini_set('log_errors', 1);
ini_set('error_log', 'php://stderr');

// Define BASE_URL for deployment flexibility (handles /planwise/ subdir on Render)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$BASE_URL = rtrim($protocol . $host . $scriptPath, '/');
define('BASE_URL', $BASE_URL);

// Start session with Render-friendly settings
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

// Get the page parameter
$page = $_GET['page'] ?? 'home';

// Define valid pages and their corresponding view files
$validPages = [
    'home'            => null, // Show landing page
    'lesson-plan/pdf' => null, // Public inline PDF for QR code scans (no auth required)
    'login' => 'views/auth/login.php',
    'register' => 'views/auth/register.php',
    'forgot-password' => 'views/auth/forgot-password.php',
    'reset-password' => 'views/auth/reset-password.php',
    'teacher/dashboard' => 'views/teacher/dashboard.php',
    'teacher/profile' => 'views/teacher/profile.php',
    'teacher/lesson-plans' => 'views/teacher/lesson-plans/index.php',
    'teacher/lesson-plans/create' => 'views/teacher/lesson-plans/create.php',
    'teacher/lesson-plans/edit' => 'views/teacher/lesson-plans/edit.php',
    'teacher/lesson-plans/view' => 'views/teacher/lesson-plans/view.php',
    'admin/dashboard' => 'views/admin/dashboard.php',
    'admin/users' => 'views/admin/users/index.php',
    'admin/users/create' => 'views/admin/users/create.php',
    'admin/users/edit' => 'views/admin/users/edit.php',
    'admin/users/view' => 'views/admin/users/view.php',
    'admin/activity-logs' => 'views/admin/activity-logs.php',
    'admin/import' => 'views/admin/import.php',
    'admin/system-settings' => 'views/admin/system-settings.php',
    '403' => 'views/errors/403.php',
    '404' => 'views/errors/404.php',
    '500' => 'views/errors/500.php',
];

// Enhanced routing with POST controller handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists($page, $validPages)) {
    // Handle POST requests for controller actions
    switch ($page) {
        case 'login':
            require_once __DIR__ . '/../controllers/AuthController.php';
            $controller = new AuthController();
            $controller->login();
            break;
        case 'register':
            require_once __DIR__ . '/../controllers/AuthController.php';
            $controller = new AuthController();
            $controller->register();
            break;
        // Add more POST handlers as needed (forgot-password, etc.)
        default:
            // Fall through to GET/view
            break;
    }
}

// Check if the page is valid
if (array_key_exists($page, $validPages)) {
    $viewFile = $validPages[$page];
    if ($viewFile !== null && file_exists(__DIR__ . '/../' . $viewFile)) {
        include __DIR__ . '/../views/' . $viewFile;
        exit();
    } elseif ($viewFile === null) {
        if ($page === 'lesson-plan/pdf') {
            require_once __DIR__ . '/../controllers/ExportController.php';
            $controller = new ExportController();
            $controller->exportPDF();
            exit();
        }
    } else {
        include __DIR__ . '/../views/errors/404.php';
        exit();
    }
} else {
    include __DIR__ . '/../views/errors/404.php';
    exit();
}

// Home landing page with BASE_URL (only shown for GET home)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanWise &ndash; Lesson Plan Builder for Jamaican Teachers</title>
    <meta name="description" content="Create, export, and share professional lesson plans with QR codes. Built for Jamaican teachers.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS with BASE_URL -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL . '/css/style.css'); ?>">
</head>
<body>

    <!-- ============================================================
         NAVBAR
    ============================================================ -->
    <nav class="navbar navbar-expand-lg lp-navbar">
            <div class="container">
                <a class="navbar-brand" href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=home'); ?>">
                    <i class="fas fa-book-open me-2"></i>PlanWise
                </a>
                <button class="navbar-toggler" type="button"
                        data-bs-toggle="collapse" data-bs-target="#lpNavbar"
                        aria-controls="lpNavbar" aria-expanded="false"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="lpNavbar">
                    <div class="ms-auto d-flex align-items-center gap-2 pt-2 pt-lg-0">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=login'); ?>"
                           class="btn btn-outline-primary btn-sm px-3">Login</a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=register'); ?>"
                           class="btn btn-primary btn-sm px-3">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============================================================
         HERO SECTION
    ============================================================ -->
    <section class="lp-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Build Better Lesson Plans, Effortlessly</h1>
                    <p class="lead">
                        Create structured digital lesson plans, export them to PDF or Word,
                        and share instantly with students and colleagues via QR codes.
                        Designed for Jamaican teachers.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=register'); ?>"
                           class="btn btn-light btn-lg">
                            <i class="fas fa-rocket me-2"></i>Get Started Free
                        </a>
                        <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=login'); ?>"
                           class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 lp-hero-illustration">
                    <div class="lp-hero-card">
                        <i class="fas fa-file-alt fa-4x mb-3"></i>
                        <p class="fw-semibold fs-6 mb-1">Lesson Plan Ready</p>
                        <p class="small mb-3 opacity-75">Mathematics &ndash; Grade 7<br>Fractions &amp; Decimals</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-white text-primary px-3 py-2">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </span>
                            <span class="badge bg-white text-success px-3 py-2">
                                <i class="fas fa-qrcode me-1"></i>QR Code
                            </span>
                            <span class="badge bg-white text-secondary px-3 py-2">
                                <i class="fas fa-file-word me-1"></i>Word
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         FEATURES SECTION
    ============================================================ -->
    <section class="lp-features">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Everything You Need</h2>
                <p class="section-subtitle">Powerful tools designed around how teachers work</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon blue">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <h3>Create Lesson Plans</h3>
                        <p>Build structured, professional lesson plans with sections for
                           objectives, resources, activities, and assessments &mdash; all in one place.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon green">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <h3>Export to PDF &amp; Word</h3>
                        <p>One-click export to professionally formatted PDF or Word documents,
                           ready for printing, submission, or archiving.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="lp-feature-card">
                        <div class="lp-feature-icon purple">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3>QR Code Sharing</h3>
                        <p>Generate a QR code for any lesson plan. Students scan it with their
                           phone to view the PDF instantly &mdash; no login required.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         HOW IT WORKS SECTION
    ============================================================ -->
    <section class="lp-how">
        <div class="container">
            <div class="text-center mb-5">
                <h2>How It Works</h2>
                <p class="section-subtitle">Up and running in minutes</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-sm-4">
                    <div class="lp-step">
                        <div class="lp-step-number">1</div>
                        <h3>Create Your Account</h3>
                        <p>Register for free in under a minute. No credit card required.</p>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="lp-step">
                        <div class="lp-step-number">2</div>
                        <h3>Build Your Lesson Plan</h3>
                        <p>Fill in your structured plan with objectives, resources, and activities.</p>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="lp-step">
                        <div class="lp-step-number">3</div>
                        <h3>Export or Share</h3>
                        <p>Download as PDF or Word, or share a QR code directly with students.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         CTA BANNER
    ============================================================ -->
    <section class="lp-cta">
        <div class="container">
            <h2>Start Building Better Lesson Plans Today</h2>
            <p>Join teachers across Jamaica who use PlanWise to save time and teach smarter.</p>
            <a href="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=register'); ?>" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i>Sign Up Free
            </a>
        </div>
    </section>

    <!-- ============================================================
         FOOTER
    ============================================================ -->
    <footer class="lp-footer">
        <div class="container">
            <p>&copy; 2026 <strong>PlanWise</strong>. All rights reserved.
               &nbsp;&mdash;&nbsp; Empowering Jamaican teachers, one plan at a time.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
