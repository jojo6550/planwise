<?php
/**
 * PlanWise - Application Entry Point
 * Handles routing and includes appropriate views
 */

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

// Start session with Render-friendly settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for better Render compatibility
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Session cookie

    session_start();
}

// Get the page parameter
$page = $_GET['page'] ?? 'home';

// Define valid pages and their corresponding view files
$validPages = [
    'home' => null, // Show landing page
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
    'admin/system-settings' => 'views/admin/system-settings.php',
    '403' => 'views/errors/403.php',
    '404' => 'views/errors/404.php',
    '500' => 'views/errors/500.php',
];

// Check if the page is valid
if (array_key_exists($page, $validPages)) {
    $viewFile = $validPages[$page];
    if ($viewFile !== null && file_exists(__DIR__ . '/../' . $viewFile)) {
        // Include the view file
        include __DIR__ . '/../' . $viewFile;
        exit();
    } elseif ($viewFile === null) {
        // Show landing page
    } else {
        // View file not found, show 404
        include __DIR__ . '/../views/errors/404.php';
        exit();
    }
} else {
    // Invalid page, show 404
    include __DIR__ . '/../views/errors/404.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanWise - Lesson Plan Builder</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
        <div class="text-center">
            <header>
                <h1 class="display-4 mb-4">Welcome to PlanWise – Lesson Plan Builder</h1>
            </header>
            <main>
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-book-half" viewBox="0 0 16 16">
                        <path d="M8.5 2.687c.654-.689 1.782-.886 3.112-.532 1.234.292 4.005 1.475 4.005 4.594 0 2.39-1.384 4.09-3.083 5.204-.518.282-1.063.454-1.643.542-.509.082-1.021.077-1.522-.02-.99-.188-1.792-.48-2.45-.83-.672-.354-1.187-.806-1.187-1.605 0-.81.544-1.41 1.187-1.805.66-.406 1.518-.674 2.45-.83.502-.103 1.014-.108 1.522-.02.58.088 1.125.26 1.643.542C12.116 8.59 13.5 10.29 13.5 12.69c0 2.119-2.771 3.302-4.005 3.594-1.33.354-2.458.157-3.112-.532-.654-.689-.654-1.805 0-2.494.654-.689 1.782-.886 3.112-.532 1.234.292 4.005 1.475 4.005 4.594 0 2.39-1.384 4.09-3.083 5.204-.518.282-1.063.454-1.643.542-.509.082-1.021.077-1.522-.02-.99-.188-1.792-.48-2.45-.83-.672-.354-1.187-.806-1.187-1.605 0-.81.544-1.41 1.187-1.805.66-.406 1.518-.674 2.45-.83.502-.103 1.014-.108 1.522-.02.58.088 1.125.26 1.643.542C12.116 13.59 13.5 15.29 13.5 17.69c0 2.119-2.771 3.302-4.005 3.594-1.33.354-2.458.157-3.112-.532-.654-.689-.654-1.805 0-2.494z"/>
                    </svg>
                </div>
                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                    <a href="/planwise/public/index.php?page=login" class="btn btn-primary btn-lg">Login</a>
                    <a href="/planwise/public/index.php?page=register" class="btn btn-secondary btn-lg">Register</a>
                </div>
            </main>
            <footer class="mt-5">
                <p class="text-muted">&copy; 2023 PlanWise. All rights reserved.</p>
            </footer>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
