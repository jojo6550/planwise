<?php
/**
 * 500 Internal Server Error Page
 * Custom error page for server errors
 */

// Set proper HTTP status code
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error - PlanWise</title>
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
            color: #fd7e14;
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
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h1 class="display-4 fw-bold text-warning">500</h1>
                    <h2 class="h4 mb-4">Internal Server Error</h2>
                    <p class="text-muted mb-4">
                        Something went wrong on our end. We're working to fix this issue.
                        Please try again later or contact support if the problem persists.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/planwise/public/index.php?page=home" class="btn btn-primary">
                            <i class="bi bi-house-door me-2"></i>Go Home
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                        </button>
                    </div>
                    <div class="mt-4">
                        <small class="text-muted">
                            Error ID: <?php echo uniqid('ERR_', true); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
