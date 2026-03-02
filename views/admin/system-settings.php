<?php
/**
 * Admin - System Settings
 * Application configuration and system information
 */

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
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

if (!$auth->hasRole(1)) {
    header('Location: /planwise/public/index.php?page=403');
    exit();
}

$user = $auth->user();
$db   = Database::getInstance();
$activityLog = new ActivityLog();
$userModel   = new User();

// System info
$phpVersion      = PHP_VERSION;
$serverSoftware  = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$osInfo          = PHP_OS_FAMILY . ' ' . PHP_OS;
$maxUpload       = ini_get('upload_max_filesize');
$maxPost         = ini_get('post_max_size');
$memoryLimit     = ini_get('memory_limit');
$execTime        = ini_get('max_execution_time') . 's';
$sessionTimeout  = '30 minutes';
$appUrl          = $_ENV['APP_URL'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

try {
    $dbVersion = $db->fetch("SELECT VERSION() as v")['v'] ?? 'Unknown';
} catch (Exception $e) {
    $dbVersion = 'Unknown';
}

// App stats
$allUsers      = $userModel->getAll();
$totalUsers    = count($allUsers);
$activeUsers   = count(array_filter($allUsers, fn($u) => $u['status'] === 'active'));

try {
    $lpRow        = $db->fetch("SELECT COUNT(*) as c FROM lesson_plans");
    $totalLessons = (int)($lpRow['c'] ?? 0);
    $logRow       = $db->fetch("SELECT COUNT(*) as c FROM activity_logs");
    $totalLogs    = (int)($logRow['c'] ?? 0);
} catch (Exception $e) {
    $totalLessons = $totalLogs = 0;
}

// Disk usage for uploads dir
$uploadsDir  = __DIR__ . '/../../uploads';
$uploadsSize = 0;
if (is_dir($uploadsDir)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir)) as $file) {
        if ($file->isFile()) $uploadsSize += $file->getSize();
    }
}

// Log file sizes
$logDir   = __DIR__ . '/../../logs';
$logSize  = 0;
if (is_dir($logDir)) {
    foreach (glob($logDir . '/*.log') as $logFile) {
        $logSize += filesize($logFile);
    }
}

// CSRF
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// Flash
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle  = 'System Settings';
$activePage = 'system-settings';

require __DIR__ . '/../layouts/admin-start.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">System Settings</h1>
        <p class="admin-breadcrumb">
            <a href="/planwise/public/index.php?page=admin/dashboard">Dashboard</a> /
            System Settings
        </p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show auto-dismiss mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- Left column -->
    <div class="col-12 col-lg-6">

        <!-- Application Stats -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-chart-pie"></i> Application Statistics</h2>
            </div>
            <div class="data-card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div style="background:#f0f4ff; border-radius:10px; padding:1rem; text-align:center;">
                            <div style="font-size:1.75rem; font-weight:800; color:#1d4ed8;"><?= number_format($totalUsers) ?></div>
                            <div style="font-size:0.77rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:#6c757d;">Total Users</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#f0fdf4; border-radius:10px; padding:1rem; text-align:center;">
                            <div style="font-size:1.75rem; font-weight:800; color:#15803d;"><?= number_format($activeUsers) ?></div>
                            <div style="font-size:0.77rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:#6c757d;">Active Users</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fefce8; border-radius:10px; padding:1rem; text-align:center;">
                            <div style="font-size:1.75rem; font-weight:800; color:#b45309;"><?= number_format($totalLessons) ?></div>
                            <div style="font-size:0.77rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:#6c757d;">Lesson Plans</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fdf2f8; border-radius:10px; padding:1rem; text-align:center;">
                            <div style="font-size:1.75rem; font-weight:800; color:#9d174d;"><?= number_format($totalLogs) ?></div>
                            <div style="font-size:0.77rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:#6c757d;">Activity Logs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Environment -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-server"></i> System Environment</h2>
            </div>
            <div class="data-card-body">
                <div class="info-stat-row">
                    <span class="label"><i class="fab fa-php me-2 text-muted fa-sm"></i>PHP Version</span>
                    <span class="value">
                        <span class="badge bg-primary-subtle text-primary" style="font-size:0.8rem;"><?= h($phpVersion) ?></span>
                    </span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-database me-2 text-muted fa-sm"></i>MySQL Version</span>
                    <span class="value">
                        <span class="badge bg-info-subtle text-info" style="font-size:0.8rem;"><?= h($dbVersion) ?></span>
                    </span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-globe me-2 text-muted fa-sm"></i>Web Server</span>
                    <span class="value" style="font-size:0.82rem;"><?= h($serverSoftware) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-desktop me-2 text-muted fa-sm"></i>OS</span>
                    <span class="value" style="font-size:0.82rem;"><?= h($osInfo) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-link me-2 text-muted fa-sm"></i>App URL</span>
                    <span class="value" style="font-size:0.82rem;"><?= h($appUrl) ?></span>
                </div>
            </div>
        </div>

        <!-- PHP Configuration -->
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-sliders-h"></i> PHP Configuration</h2>
            </div>
            <div class="data-card-body">
                <div class="info-stat-row">
                    <span class="label">Upload Max Filesize</span>
                    <span class="value"><?= h($maxUpload) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label">Post Max Size</span>
                    <span class="value"><?= h($maxPost) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label">Memory Limit</span>
                    <span class="value"><?= h($memoryLimit) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label">Max Execution Time</span>
                    <span class="value"><?= h($execTime) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label">Session Timeout</span>
                    <span class="value"><?= h($sessionTimeout) ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Right column -->
    <div class="col-12 col-lg-6">

        <!-- Storage -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-hdd"></i> Storage Usage</h2>
            </div>
            <div class="data-card-body">
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-folder-open me-2 text-muted fa-sm"></i>Uploads Directory</span>
                    <span class="value"><?= format_bytes($uploadsSize) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-file-alt me-2 text-muted fa-sm"></i>Log Files</span>
                    <span class="value"><?= format_bytes($logSize) ?></span>
                </div>
            </div>
        </div>

        <!-- Maintenance Actions -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-tools"></i> Maintenance</h2>
            </div>
            <div class="data-card-body">
                <p style="font-size:0.875rem; color:#6c757d;" class="mb-3">
                    Perform routine maintenance tasks to keep the system running smoothly.
                </p>

                <!-- Cleanup Logs -->
                <div class="border rounded p-3 mb-3" style="border-color:#e9ecef !important;">
                    <h3 style="font-size:0.9rem; font-weight:700; margin-bottom:0.4rem;">
                        <i class="fas fa-trash-alt me-2 text-danger fa-sm"></i>Cleanup Activity Logs
                    </h3>
                    <p style="font-size:0.82rem; color:#6c757d; margin-bottom:0.75rem;">
                        Delete activity logs older than a specified number of days.
                        Currently <strong><?= number_format($totalLogs) ?></strong> logs stored.
                    </p>
                    <form action="/planwise/controllers/ActivityLogController.php?action=cleanup" method="POST"
                          onsubmit="return confirm('Delete old logs? This cannot be undone.')">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width:160px;">
                                <input type="number" class="form-control" name="days" value="90" min="7" max="365">
                                <span class="input-group-text">days</span>
                            </div>
                            <button type="submit" name="cleanup" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt me-1"></i> Cleanup
                            </button>
                        </div>
                    </form>
                </div>

                <!-- View Logs -->
                <div class="border rounded p-3" style="border-color:#e9ecef !important;">
                    <h3 style="font-size:0.9rem; font-weight:700; margin-bottom:0.4rem;">
                        <i class="fas fa-history me-2 text-primary fa-sm"></i>Activity Logs
                    </h3>
                    <p style="font-size:0.82rem; color:#6c757d; margin-bottom:0.75rem;">
                        View and filter all recorded user activity across the system.
                    </p>
                    <a href="/planwise/public/index.php?page=admin/activity-logs" class="btn btn-primary btn-sm">
                        <i class="fas fa-history me-1"></i> View Activity Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- Security Status -->
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-shield-alt"></i> Security Status</h2>
            </div>
            <div class="data-card-body">
                <?php
                $checks = [
                    ['label' => 'CSRF Protection',      'ok' => true,   'note' => 'Active on all forms'],
                    ['label' => 'Password Hashing',     'ok' => true,   'note' => 'bcrypt via password_hash()'],
                    ['label' => 'Prepared Statements',  'ok' => true,   'note' => 'PDO parameterized queries'],
                    ['label' => 'Session Regeneration', 'ok' => true,   'note' => 'On login via session_regenerate_id()'],
                    ['label' => 'HTTPS',                'ok' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', 'note' => 'Recommended for production'],
                    ['label' => 'Display Errors',       'ok' => ini_get('display_errors') == 0, 'note' => 'Should be off in production'],
                ];
                foreach ($checks as $check):
                ?>
                <div class="info-stat-row">
                    <span class="label"><?= h($check['label']) ?></span>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:0.78rem; color:#6c757d;"><?= h($check['note']) ?></span>
                        <?php if ($check['ok']): ?>
                            <span style="color:#16a34a;"><i class="fas fa-check-circle"></i></span>
                        <?php else: ?>
                            <span style="color:#dc2626;"><i class="fas fa-exclamation-circle"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php require __DIR__ . '/../layouts/admin-end.php'; ?>
