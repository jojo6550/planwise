<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Temporarily Unavailable - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/planwise/public/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <!-- Warning Icon -->
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-exclamation-triangle-fill text-warning" viewBox="0 0 16 16">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                        </div>

                        <!-- Error Title -->
                        <h1 class="h3 mb-3 fw-bold text-dark">Service Temporarily Unavailable</h1>

                        <!-- Error Message -->
                        <p class="text-muted mb-4">
                            We're experiencing technical difficulties with our database connection.
                            Our team has been notified and is working to resolve this issue.
                        </p>

                        <!-- Retry Button -->
                        <div class="d-grid mb-3">
                            <a href="/planwise/public/index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Try Again
                            </a>
                        </div>

                        <!-- Contact Info -->
                        <div class="text-center">
                            <small class="text-muted">
                                If the problem persists, please contact our support team.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
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
