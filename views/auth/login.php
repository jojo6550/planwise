<?php
/**
 * Login Page
 * User authentication form
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already authenticated
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = new Auth();
if ($auth->check()) {
    $user = $auth->user();
    if ($user['role_id'] == 1) {
        header('Location: /public/index.php?page=admin/dashboard');
    } else {
        header('Location: /public/index.php?page=teacher/dashboard');
    }
    exit();
}

// Get error and success messages from session
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Generate CSRF token
require_once __DIR__ . '/../../controllers/AuthController.php';
$csrfToken = AuthController::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <!-- Login Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Logo/Brand -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">PlanWise</h2>
                            <p class="text-muted">Lesson Plan Builder</p>
                        </div>

                        <!-- Login Heading -->
                        <h4 class="text-center mb-4">Sign In</h4>

                        <!-- Success Alert -->
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Error Alert -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="/controllers/AuthController.php?action=login" id="loginForm">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                            <!-- Email Field -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="Enter your email"
                                    required
                                    autofocus
                                >
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Enter your password"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="mb-3 form-check">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="remember" 
                                    name="remember"
                                >
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Sign In
                                </button>
                            </div>

                            <!-- Forgot Password Link -->
                            <div class="text-center mb-3">
                                <a href="/public/index.php?page=forgot-password" class="text-decoration-none">
                                    Forgot your password?
                                </a>
                            </div>

                            <!-- Registration Link -->
                            <div class="text-center">
                                <span class="text-muted">Don't have an account?</span>
                                <a href="/public/index.php?page=register" class="text-decoration-none">
                                    Sign Up
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer Text -->
                <p class="text-center text-muted mt-4">
                    &copy; <?php echo date('Y'); ?> PlanWise. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Validation Script -->
    <script>
        // Form validation
        (function() {
            'use strict';
            
            const form = document.getElementById('loginForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>
