<?php
/**
 * Admin Activity Logs View
 * Displays and manages activity logs
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
$activityLog = new ActivityLog();
$userModel   = new User();

// Filters
$filters = [
    'user_id'   => $_GET['user_id']   ?? '',
    'action'    => $_GET['action']    ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to']   ?? '',
    'search'    => $_GET['search']    ?? '',
];

$currentPage = max(1, (int)($_GET['page_num'] ?? 1));
$limit  = 25;
$offset = ($currentPage - 1) * $limit;

$logs      = $activityLog->getAll($filters, $limit, $offset);
$totalLogs = $activityLog->getTotalCount($filters);
$totalPages = (int)ceil($totalLogs / $limit);

$actionTypes = $activityLog->getActionTypes();
$users       = $userModel->getAll();

// CSRF
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// Flash
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Helpers
function logsActionBadge(string $action): string {
    $map = [
        'user_login'               => ['Login',          'bg-success-subtle text-success'],
        'user_logout'              => ['Logout',         'bg-secondary-subtle text-secondary'],
        'user_registered'          => ['Registered',     'bg-primary-subtle text-primary'],
        'user_created'             => ['User Created',   'bg-info-subtle text-info'],
        'user_updated'             => ['User Updated',   'bg-warning-subtle text-warning'],
        'user_deleted'             => ['User Deleted',   'bg-danger-subtle text-danger'],
        'user_status_updated'      => ['Status Changed', 'bg-warning-subtle text-warning'],
        'password_reset_completed' => ['Pwd Reset',      'bg-secondary-subtle text-secondary'],
        'lesson_plan_created'      => ['Lesson Created', 'bg-success-subtle text-success'],
        'lesson_plan_updated'      => ['Lesson Updated', 'bg-info-subtle text-info'],
        'lesson_plan_deleted'      => ['Lesson Deleted', 'bg-danger-subtle text-danger'],
        'lesson_plan_viewed'       => ['Lesson Viewed',  'bg-light text-secondary'],
        'pdf_exported'             => ['PDF Export',     'bg-primary-subtle text-primary'],
        'word_exported'            => ['Word Export',    'bg-primary-subtle text-primary'],
        'pdf_saved'                => ['PDF Saved',      'bg-info-subtle text-info'],
        'word_saved'               => ['Word Saved',     'bg-info-subtle text-info'],
        'lesson_plan_imported'     => ['Imported',       'bg-primary-subtle text-primary'],
        'qr_code_generated'        => ['QR Generated',   'bg-info-subtle text-info'],
        'file_uploaded'            => ['File Upload',    'bg-success-subtle text-success'],
        'file_downloaded'          => ['File Download',  'bg-primary-subtle text-primary'],
        'file_deleted'             => ['File Deleted',   'bg-danger-subtle text-danger'],
        'activity_logs_cleaned'    => ['Logs Cleaned',   'bg-secondary-subtle text-secondary'],
    ];
    $entry = $map[$action] ?? [str_replace('_', ' ', ucfirst($action)), 'bg-light text-secondary'];
    return '<span class="action-badge ' . $entry[1] . '">' . htmlspecialchars($entry[0]) . '</span>';
}

function logsActionText(string $action): string {
    $labels = [
        'user_login'               => 'User logged in',
        'user_logout'              => 'User logged out',
        'user_registered'          => 'New user registered',
        'user_created'             => 'User account created',
        'user_updated'             => 'User profile updated',
        'user_deleted'             => 'User account deleted',
        'user_status_updated'      => 'User status changed',
        'password_reset_completed' => 'Password reset completed',
        'lesson_plan_created'      => 'Lesson plan created',
        'lesson_plan_updated'      => 'Lesson plan updated',
        'lesson_plan_deleted'      => 'Lesson plan deleted',
        'lesson_plan_viewed'       => 'Lesson plan viewed',
        'pdf_exported'             => 'Exported to PDF',
        'word_exported'            => 'Exported to Word',
        'pdf_saved'                => 'Saved as PDF',
        'word_saved'               => 'Saved as Word',
        'lesson_plan_imported'     => 'Lesson plan imported',
        'qr_code_generated'        => 'QR code generated',
        'file_uploaded'            => 'File uploaded',
        'file_downloaded'          => 'File downloaded',
        'file_deleted'             => 'File deleted',
        'activity_logs_cleaned'    => 'Activity logs cleaned up',
    ];
    return $labels[$action] ?? htmlspecialchars(str_replace('_', ' ', ucfirst($action)));
}

$pageTitle  = 'Activity Logs';
$activePage = 'activity-logs';

require __DIR__ . '/../layouts/admin-start.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Activity Logs</h1>
        <p class="admin-breadcrumb">
            <a href="/planwise/public/index.php?page=admin/dashboard">Dashboard</a> /
            Activity Logs
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#cleanupModal">
            <i class="fas fa-trash me-1"></i> Cleanup Old Logs
        </button>
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

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($totalLogs) ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($activityLog->getTotalCount(['action' => 'user_login'])) ?></div>
                <div class="stat-label">Login Events</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card teal">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($activityLog->getTotalCount(['action' => 'lesson_plan_created'])) ?></div>
                <div class="stat-label">Lesson Plans</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-tags"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format(count($actionTypes)) ?></div>
                <div class="stat-label">Action Types</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="" class="admin-filter-bar mb-4">
    <input type="hidden" name="page" value="admin/activity-logs">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">User</label>
            <select class="form-select form-select-sm" name="user_id">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['user_id'] ?>" <?= ($filters['user_id'] == $u['user_id']) ? 'selected' : '' ?>>
                        <?= h($u['first_name'] . ' ' . $u['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">Action Type</label>
            <select class="form-select form-select-sm" name="action">
                <option value="">All Actions</option>
                <?php foreach ($actionTypes as $act): ?>
                    <option value="<?= h($act) ?>" <?= ($filters['action'] === $act) ? 'selected' : '' ?>>
                        <?= logsActionText($act) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">From Date</label>
            <input type="date" class="form-control form-control-sm" name="date_from" value="<?= h($filters['date_from']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">To Date</label>
            <input type="date" class="form-control form-control-sm" name="date_to" value="<?= h($filters['date_to']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">Search</label>
            <input type="text" class="form-control form-control-sm" name="search"
                   placeholder="Description…" value="<?= h($filters['search']) ?>">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="fas fa-filter"></i>
            </button>
            <?php if (array_filter($filters)): ?>
            <a href="?page=admin/activity-logs" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Table -->
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title"><i class="fas fa-history"></i> Activity Records</h2>
        <span style="font-size:0.82rem; color:#6c757d;">
            Showing <?= count($logs) ?> of <?= number_format($totalLogs) ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Date / Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                        No activity logs found
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-size:0.8rem; color:#9ca3af;"><?= $log['log_id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar size-sm">
                                <?= strtoupper(substr($log['first_name'] ?? '?', 0, 1) . substr($log['last_name'] ?? '', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600; font-size:0.83rem; line-height:1.2; color:#1a2535;">
                                    <?= h($log['first_name'] . ' ' . $log['last_name']) ?>
                                </div>
                                <div style="font-size:0.73rem; color:#9ca3af;"><?= h($log['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= logsActionBadge($log['action']) ?></td>
                    <td style="max-width:240px;">
                        <span class="d-block text-truncate" style="max-width:240px; font-size:0.82rem; color:#6c757d;"
                              title="<?= h($log['description'] ?? '') ?>">
                            <?= h(truncate_text($log['description'] ?? 'N/A', 60)) ?>
                        </span>
                    </td>
                    <td style="font-family:monospace; font-size:0.78rem; color:#9ca3af;">
                        <?= h($log['ip_address'] ?? 'Unknown') ?>
                    </td>
                    <td style="white-space:nowrap; font-size:0.8rem; color:#9ca3af;">
                        <?= format_datetime($log['created_at'], 'M j, Y g:i A') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.5rem; border-top:1px solid #f0f4f8;">
        <nav aria-label="Activity logs pagination">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $currentPage - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                        &laquo; Prev
                    </a>
                </li>
                <?php
                $start = max(1, $currentPage - 2);
                $end   = min($totalPages, $currentPage + 2);
                if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=admin/activity-logs&page_num=1&<?= http_build_query(array_filter($filters)) ?>">1</a></li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?page=admin/activity-logs&page_num=<?= $totalPages ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $currentPage + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                        Next &raquo;
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1" aria-labelledby="cleanupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="cleanupModalLabel">
                    <i class="fas fa-trash-alt text-danger me-2"></i>Cleanup Old Activity Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/planwise/controllers/ActivityLogController.php?action=cleanup"
                  method="POST" onsubmit="return confirm('This will permanently delete old logs. Continue?');">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <div class="modal-body">
                    <p class="text-muted" style="font-size:0.875rem;">
                        This will permanently delete activity logs older than the specified number of days.
                        Currently <strong><?= number_format($activityLog->getTotalCount()) ?></strong> total logs.
                    </p>
                    <div class="mb-3">
                        <label for="cleanupDays" class="form-label fw-semibold">Keep logs from the last:</label>
                        <div class="input-group" style="max-width:200px;">
                            <input type="number" class="form-control" id="cleanupDays" name="days"
                                   value="90