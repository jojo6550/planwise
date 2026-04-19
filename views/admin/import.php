<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();
}

require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/ActivityLog.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit();
}

if (!$auth->hasRole(1)) {
    header('Location: ' . BASE_URL . '/index.php?page=403');
    exit();
}

$user = $auth->user();

// Ensure CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Flash messages
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Recent import activity from activity logs
$activityLog   = new ActivityLog();
$recentImports = $activityLog->getAll(['action' => 'lesson_plan_imported'], 10, 0);

$pageTitle  = 'Data Import';
$activePage = 'import';

require __DIR__ . '/../layouts/admin-start.php';
?>

<!-- Flash messages -->
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show auto-dismiss mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?= h($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Data Import</h2>
        <p class="text-muted mb-0">Import lesson plans or users from a CSV file</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/index.php?page=admin/import/template&type=lesson_plans"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-download me-1"></i> Lesson Plan Template
        </a>
        <a href="<?= BASE_URL ?>/index.php?page=admin/import/template&type=users"
           class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-download me-1"></i> User Template
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Import Form -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0"><i class="fas fa-upload me-2 text-primary"></i>Upload CSV File</h5>
            </div>
            <div class="card-body">
                <form id="importForm" method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <!-- Import type selector -->
                    <div class="mb-3">
                        <label for="import_type" class="form-label fw-semibold">Import Type</label>
                        <select class="form-select" id="import_type" name="import_type" required>
                            <option value="lesson_plans" selected>Lesson Plans</option>
                            <option value="users">Users (Teachers)</option>
                        </select>
                    </div>

                    <!-- File input -->
                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-semibold">Select CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file"
                               accept=".csv,text/csv" required>
                        <div class="form-text">
                            Only CSV files are accepted. Maximum size: 5MB.
                        </div>
                    </div>

                    <!-- Validate only -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="validate_only" name="validate_only">
                            <label class="form-check-label" for="validate_only">
                                <strong>Validate only</strong> — check for errors without importing data
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                            <i class="fas fa-upload me-1"></i> Import Data
                        </button>
                        <a href="<?= BASE_URL ?>/index.php?page=admin/dashboard" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </form>

                <!-- AJAX result area -->
                <div id="importResults" class="mt-4 d-none"></div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-info"></i>CSV Format Guide</h5>
            </div>
            <div class="card-body">
                <div id="lessonPlanInstructions">
                    <h6 class="fw-bold">Lesson Plans CSV Columns</h6>
                    <ol class="small">
                        <li><code>title</code> — lesson title <span class="text-danger">*required</span></li>
                        <li><code>subject</code> — e.g. Mathematics, Science</li>
                        <li><code>grade_level</code> — e.g. Grade 9</li>
                        <li><code>duration_minutes</code> — numeric (e.g. 60)</li>
                        <li><code>objectives</code> — learning objectives</li>
                        <li><code>materials</code> — required resources</li>
                        <li><code>procedures</code> — step-by-step activities</li>
                        <li><code>assessment</code> — assessment method</li>
                        <li><code>notes</code> — additional notes</li>
                    </ol>
                </div>
                <div id="userInstructions" class="d-none">
                    <h6 class="fw-bold">Users CSV Columns</h6>
                    <ol class="small">
                        <li><code>first_name</code> <span class="text-danger">*required</span></li>
                        <li><code>last_name</code> <span class="text-danger">*required</span></li>
                        <li><code>email</code> <span class="text-danger">*required</span></li>
                        <li><code>password</code> — min 6 characters <span class="text-danger">*required</span></li>
                    </ol>
                    <div class="alert alert-warning small py-2">
                        <i class="fas fa-lock me-1"></i> Passwords are stored securely using bcrypt hashing.
                    </div>
                </div>

                <hr>
                <div class="alert alert-light border small py-2 mb-0">
                    <ul class="mb-0 ps-3">
                        <li>Use the template buttons above for correct column order</li>
                        <li>First row must be the header row</li>
                        <li>Empty rows are skipped automatically</li>
                        <li>Use "Validate only" to check for errors first</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Import Activity -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="card-title mb-0"><i class="fas fa-history me-2 text-secondary"></i>Recent Import Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Date &amp; Time</th>
                        <th>Performed By</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentImports)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                            No import activity yet
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recentImports as $log): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= h(format_datetime($log['created_at'] ?? '')); ?></td>
                            <td class="small"><?= h(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></td>
                            <td class="small"><?= h($log['description'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>const BASE_URL = ' . json_encode(BASE_URL) . ';</script>' . <<<'JS'
<script>
(function () {
    // Toggle instruction panel based on import type selection
    const typeSelect  = document.getElementById('import_type');
    const lpInstr     = document.getElementById('lessonPlanInstructions');
    const userInstr   = document.getElementById('userInstructions');

    typeSelect.addEventListener('change', function () {
        if (this.value === 'users') {
            lpInstr.classList.add('d-none');
            userInstr.classList.remove('d-none');
        } else {
            userInstr.classList.add('d-none');
            lpInstr.classList.remove('d-none');
        }
    });

    // Client-side file validation before AJAX submit
    document.getElementById('csv_file').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please select a smaller file.');
            e.target.value = '';
            return;
        }
        if (!file.name.toLowerCase().endsWith('.csv')) {
            alert('Please select a valid CSV file.');
            e.target.value = '';
        }
    });

    // AJAX form submission with live result display
    document.getElementById('importForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const fileInput  = document.getElementById('csv_file');
        if (!fileInput.files[0]) {
            alert('Please select a CSV file to upload.');
            return;
        }

        const formData   = new FormData(this);
        const submitBtn  = document.getElementById('importSubmitBtn');
        const resultsDiv = document.getElementById('importResults');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        resultsDiv.className = 'mt-4';
        resultsDiv.innerHTML = '<div class="alert alert-secondary"><i class="fas fa-spinner fa-spin me-2"></i> Uploading and processing CSV file...</div>';

        try {
            const res  = await fetch(BASE_URL + '/index.php?page=admin/import/upload', {
                method: 'POST',
                body:   formData  // Content-Type set automatically with multipart boundary
            });

            if (!res.ok && res.headers.get('content-type') && !res.headers.get('content-type').includes('json')) {
                throw new Error('Server error: ' + res.status);
            }

            const data = await res.json();

            if (data.success) {
                const validateOnly = data.validate_only;
                const label  = validateOnly ? 'Validation' : 'Import';
                let html = '<div class="alert ' + (data.error_count === 0 ? 'alert-success' : 'alert-warning') + '">';
                html += '<h6 class="alert-heading"><i class="fas fa-' + (data.error_count === 0 ? 'check-circle' : 'exclamation-triangle') + ' me-2"></i>' + label + ' Complete</h6>';
                html += '<p class="mb-1"><strong>' + data.success_count + '</strong> row(s) ' + (validateOnly ? 'valid' : 'imported successfully') + '</p>';
                if (data.error_count > 0) {
                    html += '<p class="mb-1"><strong>' + data.error_count + '</strong> row(s) had errors</p>';
                    html += '<hr><strong>Error Details:</strong><ul class="mb-0 small">';
                    data.errors.forEach(function (err) { html += '<li>' + err + '</li>'; });
                    html += '</ul>';
                }
                html += '</div>';
                resultsDiv.innerHTML = html;
            } else {
                resultsDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
            }
        } catch (err) {
            resultsDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Network or server error. Please try again.<br><small class="text-muted">' + err.message + '</small></div>';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Import Data';
        }
    });
})();
</script>
JS;

require __DIR__ . '/../layouts/admin-end.php';
