<?php
/**
 * Admin Import View
 * CSV/XLS Data Import Interface
 * CS334 Module 2 - File Handling
 */

// Require admin authentication
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../middleware/RoleMiddleware.php';

$authMiddleware = new AuthMiddleware();
$authMiddleware->checkAuthentication();

$roleMiddleware = new RoleMiddleware();
$roleMiddleware->requireRole(1); // Admin only

// Get CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Data Import</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/planwise/public/index.php?page=admin/import&action=downloadTemplate" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php include __DIR__ . '/../components/alerts.php'; ?>

            <!-- Import Instructions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">CSV Import Instructions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6>Supported Format:</h6>
                            <ul>
                                <li>CSV files with comma-separated values</li>
                                <li>Maximum file size: 5MB</li>
                                <li>Required columns: title, subject, grade_level, objectives, materials, duration_minutes</li>
                            </ul>

                            <h6>Column Descriptions:</h6>
                            <ul>
                                <li><strong>title:</strong> Lesson plan title (required)</li>
                                <li><strong>subject:</strong> Subject area (e.g., Mathematics, Science)</li>
                                <li><strong>grade_level:</strong> Grade level (e.g., Grade 9, High School)</li>
                                <li><strong>objectives:</strong> Learning objectives</li>
                                <li><strong>materials:</strong> Required materials and resources</li>
                                <li><strong>duration_minutes:</strong> Duration in minutes (numeric)</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Tips</h6>
                                <ul class="mb-0">
                                    <li>Use the template for correct format</li>
                                    <li>Ensure all required fields are filled</li>
                                    <li>Check for special characters in text</li>
                                    <li>Validate data before importing</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="/planwise/controllers/ImportController.php?action=upload" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file"
                                   accept=".csv,text/csv" required>
                            <div class="form-text">
                                Only CSV files are accepted. Maximum size: 5MB.
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="validate_only" name="validate_only">
                                <label class="form-check-label" for="validate_only">
                                    Validate only (don't import data)
                                </label>
                            </div>
                            <div class="form-text">
                                Check this to validate the file without actually importing the data.
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import Data
                            </button>
                            <a href="/planwise/public/index.php?page=admin/dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Import Activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- This would be populated with actual import history -->
                                <tr>
                                    <td colspan="4" class="text-muted text-center">
                                        <em>No recent import activity</em>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// File validation
document.getElementById('csv_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (5MB limit)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            alert('File size exceeds 5MB limit. Please select a smaller file.');
            e.target.value = '';
            return;
        }

        // Check file type
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
        if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv')) {
            alert('Please select a valid CSV file.');
            e.target.value = '';
            return;
        }
    }
});

// Form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('csv_file');
    if (!fileInput.files[0]) {
        e.preventDefault();
        alert('Please select a CSV file to upload.');
        return;
    }

    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
    submitBtn.disabled = true;

    // Re-enable button after 30 seconds (in case of timeout)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 30000);
});
</script>
