<?php
/**
 * Registration Page
 * User registration form
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
        header('Location: /planwise/public/index.php?page=admin/dashboard');
    } else {
        header('Location: /planwise/public/index.php?page=teacher/dashboard');
    }
    exit();
}

// Get error and success messages from session
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['old_input']);

// Generate CSRF token
require_once __DIR__ . '/../../controllers/AuthController.php';
$csrfToken = AuthController::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/planwise/public/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-md-6 col-lg-5">
                <!-- Registration Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Logo/Brand -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">PlanWise</h2>
                            <p class="text-muted">Lesson Plan Builder</p>
                        </div>

                        <!-- Registration Heading -->
                        <h4 class="text-center mb-4">Create Account</h4>

                        <!-- Success Alert -->
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Error Alert -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Debug Output (Development Mode Only) -->
                        <?php if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($debug)): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading">Debug Information</h6>
                                <ul class="mb-0">
                                    <?php foreach ($debug as $step): ?>
                                        <li><?php echo htmlspecialchars($step); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form method="POST" action="/planwise/controllers/AuthController.php?action=register" id="registerForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                            <!-- First Name Field -->
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="first_name" 
                                    name="first_name" 
                                    placeholder="Enter your first name"
                                    value="<?php echo htmlspecialchars($oldInput['first_name'] ?? ''); ?>"
                                    required
                                    autofocus
                                >
                            </div>

                            <!-- Last Name Field -->
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="last_name" 
                                    name="last_name" 
                                    placeholder="Enter your last name"
                                    value="<?php echo htmlspecialchars($oldInput['last_name'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- Email Field -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="Enter your email"
                                    value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- Password Field -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Enter password (min. 8 characters)"
                                    minlength="8"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long.
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Re-enter your password"
                                    minlength="8"
                                    required
                                >
                                <div class="invalid-feedback" id="password-match-error">
                                    Passwords must match.
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-3 form-check">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="terms" 
                                    required
                                >
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to the terms and conditions.
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Create Account
                                </button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <span class="text-muted">Already have an account?</span>
                                <a href="/planwise/public/index.php?page=login" class="text-decoration-none">
                                    Sign In
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
    

</body>
</html>
