<?php
/**
 * Reset Password View
 * Allows users to reset their password using a token
 */

require_once __DIR__ . '/../../controllers/AuthController.php';

// Generate CSRF token
$csrfToken = AuthController::generateCsrfToken();

// Get token from URL
$token = $_GET['token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PlanWise</title>
    <link href="/planwise/public/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Reset Password</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($token)): ?>
                            <div class="alert alert-danger">
                                Invalid reset link. Please request a new password reset.
                            </div>
                            <div class="text-center">
                                <a href="/planwise/public/index.php?page=forgot-password" class="btn btn-primary">
                                    Request New Reset Link
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">
                                Enter your new password below.
                            </p>

                            <form method="POST" action="/planwise/controllers/AuthController.php?action=reset-password">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                           minlength="8" required>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                           minlength="8" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Reset Password</button>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <a href="/planwise/public/index.php?page=login" class="text-decoration-none">
                                    Back to Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
