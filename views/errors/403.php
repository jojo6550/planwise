<?php
/**
 * 403 Forbidden Error Page
 * Custom error page for access denied
 */

// Set proper HTTP status code
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center">
                    <div class="error-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h1 class="display-4 fw-bold text-danger">403</h1>
                    <h2 class="h4 mb-4">Access Denied</h2>
                    <p class="text-muted mb-4">
                        You don't have permission to access this page.
                        Please contact an administrator if you believe this is an error.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/planwise/public/index.php?page=home" class="btn btn-primary">
                            <i class="bi bi-house-door me-2"></i>Go Home
                        </a>
                        <a href="/planwise/public/index.php?page=login" class="btn btn-outline-secondary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
