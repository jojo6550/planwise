<?php
/**
 * 404 Not Found Error Page
 * Custom error page for missing pages
 */

// Set proper HTTP status code
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found - PlanWise</title>
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
            color: #6c757d;
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
                        <i class="bi bi-search"></i>
                    </div>
                    <h1 class="display-4 fw-bold text-muted">404</h1>
                    <h2 class="h4 mb-4">Page Not Found</h2>
                    <p class="text-muted mb-4">
                        The page you're looking for doesn't exist or has been moved.
                        Please check the URL or navigate back to our homepage.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/planwise/public/index.php?page=home" class="btn btn-primary">
                            <i class="bi bi-house-door me-2"></i>Go Home
                        </a>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
