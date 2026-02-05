<?php
/**
 * Admin Activity Logs View
 * Displays and manages activity logs
 * CS334 Module 3 - Activity logs (10 marks)
 */

// Start session and check authentication
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/ActivityLog.php';
require_once __DIR__ . '/../../classes/User.php';

$auth = new Auth();

// Check authentication
if (!$auth->check()) {
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

// Check admin role
if (!$auth->hasRole(1)) {
    header('Location: /planwise/public/index.php?page=403');
    exit();
}

$user = $auth->user();
$activityLog = new ActivityLog();

// Get filter values
$filters = [
    'user_id' => $_GET['user_id'] ?? '',
    'action' => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$page = (int)($_GET['page'] ?? 1);
$limit = 25;
$offset = ($page - 1) * $limit;

// Get activity logs with filters
$logs = $activityLog->getAll($filters, $limit, $offset);
$totalLogs = $activityLog->getTotalCount($filters);
$totalPages = ceil($totalLogs / $limit);

// Get action types for filter dropdown
$actionTypes = $activityLog->getActionTypes();

// Get user list for filter dropdown
$userModel = new User();
$users = $userModel->getAll();

// Format action names for display
function formatAction(string $action): string
{
    $actionLabels = [
        'user_login' => '<span class="badge bg-success">Login</span>',
        'user_logout' => '<span class="badge bg-secondary">Logout</span>',
        'user_registered' => '<span class="badge bg-primary">Registered</span>',
        'user_created' => '<span class="badge bg-info">User Created</span>',
        'user_updated' => '<span class="badge bg-warning">User Updated</span>',
        'user_deleted' => '<span class="badge bg-danger">User Deleted</span>',
        'user_status_updated' => '<span class="badge bg-warning">Status Changed</span>',
        'password_reset_completed' => '<span class="badge bg-secondary">Password Reset</span>',
        'lesson_plan_created' => '<span class="badge bg-success">Lesson Created</span>',
        'lesson_plan_updated' => '<span class="badge bg-info">Lesson Updated</span>',
        'lesson_plan_deleted' => '<span class="badge bg-danger">Lesson Deleted</span>',
        'lesson_plan_viewed' => '<span class="badge bg-light">Lesson Viewed</span>',
        'pdf_exported' => '<span class="badge bg-primary">PDF Export</span>',
        'word_exported' => '<span class="badge bg-primary">Word Export</span>',
        'pdf_saved' => '<span class="badge bg-info">PDF Saved</span>',
        'word_saved' => '<span class="badge bg-info">Word Saved</span>',
        'lesson_plan_imported' => '<span class="badge bg-primary">Lesson Imported</span>',
        'qr_code_generated' => '<span class="badge bg-info">QR Generated</span>',
        'file_uploaded' => '<span class="badge bg-success">File Upload</span>',
        'file_downloaded' => '<span class="badge bg-primary">File Download</span>',
        'file_deleted' => '<span class="badge bg-danger">File Deleted</span>',
        'activity_logs_cleaned' => '<span class="badge bg-secondary">Logs Cleaned</span>'
    ];

    return $actionLabels[$action] ?? '<span class="badge bg-light">' . htmlspecialchars($action) . '</span>';
}

function formatActionText(string $action): string
{
    $actionText = [
        'user_login' => 'User logged in',
        'user_logout' => 'User logged out',
        'user_registered' => 'New user registered',
        'user_created' => 'User account created',
        'user_updated' => 'User profile updated',
        'user_deleted' => 'User account deleted',
        'user_status_updated' => 'User status changed',
        'password_reset_completed' => 'Password reset completed',
        'lesson_plan_created' => 'Lesson plan created',
        'lesson_plan_updated' => 'Lesson plan updated',
        'lesson_plan_deleted' => 'Lesson plan deleted',
        'lesson_plan_viewed' => 'Lesson plan viewed',
        'pdf_exported' => 'Exported to PDF',
        'word_exported' => 'Exported to Word',
        'pdf_saved' => 'Saved as PDF',
        'word_saved' => 'Saved as Word',
        'lesson_plan_imported' => 'Lesson plan imported',
        'qr_code_generated' => 'QR code generated',
        'file_uploaded' => 'File uploaded',
        'file_downloaded' => 'File downloaded',
        'file_deleted' => 'File deleted',
        'activity_logs_cleaned' => 'Activity logs cleaned up'
    ];

    return $actionText[$action] ?? htmlspecialchars(str_replace('_', ' ', ucfirst($action)));
}

// Get flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - PlanWise Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="/planwise/public/css/admin.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .activity-log-table {
            font-size: 0.875rem;
        }
        .activity-log-table td {
            vertical-align: middle;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .log-description {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ip-address {
            font-family: monospace;
            font-size: 0.8rem;
        }
        .user-info {
            min-width: 150px;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-history me-2"></i>Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Export
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                                <i class="fas fa-trash me-1"></i> Cleanup Old
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= h($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= h($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="page" value="admin/activity-logs">
                        
                        <div class="col-md-2">
                            <label for="user_id" class="form-label">User</label>
                            <select class="form-select form-select-sm" id="user_id" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['user_id'] ?>" <?= ($filters['user_id'] == $u['user_id']) ? 'selected' : '' ?>>
                                        <?= h($u['first_name'] . ' ' . $u['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="action" class="form-label">Action Type</label>
                            <select class="form-select form-select-sm" id="action" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($actionTypes as $action): ?>
                                    <option value="<?= h($action) ?>" <?= ($filters['action'] === $action) ? 'selected' : '' ?>>
                                        <?= formatActionText($action) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= h($filters['date_from']) ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= h($filters['date_to']) ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Search description..." value="<?= h($filters['search']) ?>">
                        </div>
                        
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($filters['user_id']) || !empty($filters['action']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['search'])): ?>
                        <div class="mt-2">
                            <a href="?page=admin/activity-logs" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title"><?= number_format($totalLogs) ?></h5>
                                <p class="card-text">Total Logs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title"><?= number_format($activityLog->getTotalCount(['action' => 'user_login'])) ?></h5>
                                <p class="card-text">Login Events</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title"><?= number_format($activityLog->getTotalCount(['action' => 'lesson_plan_created'])) ?></h5>
                                <p class="card-text">Lesson Plans</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title"><?= number_format(count($actionTypes)) ?></h5>
                                <p class="card-text">Action Types</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Logs Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover activity-log-table">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No activity logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['log_id'] ?></td>
                                        <td class="user-info">
                                            <strong><?= h($log['first_name'] . ' ' . $log['last_name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= h($log['email']) ?></small>
                                        </td>
                                        <td><?= formatAction($log['action']) ?></td>
                                        <td class="log-description" title="<?= h($log['description'] ?? '') ?>">
                                            <?= h($log['description'] ?? 'N/A') ?>
                                        </td>
                                        <td class="ip-address">
                                            <?= h($log['ip_address'] ?? 'Unknown') ?>
                                        </td>
                                        <td>
                                            <?= format_datetime($log['created_at'], 'M d, Y h:i A') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Activity logs pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $page - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=admin/activity-logs&page_num=<?= $page + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="text-center text-muted mb-4">
                    Showing <?= count($logs) ?> of <?= number_format($totalLogs) ?> logs
                </div>
            </main>
        </div>
    </div>
    
    <!-- Cleanup Modal -->
    <div class="modal fade" id="cleanupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cleanup Old Activity Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will permanently delete activity logs older than the specified number of days.</p>
                    <div class="mb-3">
                        <label for="cleanupDays" class="form-label">Keep logs for (days):</label>
                        <input type="number" class="form-control" id="cleanupDays" value="90" min="1" max="365">
                    </div>
                </div>
                <form action="/planwise/controllers/ActivityLogController.php?action=cleanup" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="cleanup" class="btn btn-danger">Cleanup Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

