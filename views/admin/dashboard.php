<?php
/**
 * Admin Dashboard
 * Main overview page for administrators
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
$userModel   = new User();
$activityLog = new ActivityLog();

/* ---- Stats ---- */
$allUsers     = $userModel->getAll();
$totalUsers   = count($allUsers);
$activeUsers  = count(array_filter($allUsers, fn($u) => $u['status'] === 'active'));
$adminCount   = count(array_filter($allUsers, fn($u) => (int)$u['role_id'] === 1));
$teacherCount = count(array_filter($allUsers, fn($u) => (int)$u['role_id'] === 2));

try {
    $lpRow      = $db->fetch("SELECT COUNT(*) as c FROM lesson_plans");
    $totalLessons = (int)($lpRow['c'] ?? 0);
    $pubRow     = $db->fetch("SELECT COUNT(*) as c FROM lesson_plans WHERE status = 'published'");
    $publishedLessons = (int)($pubRow['c'] ?? 0);
} catch (Exception $e) {
    $totalLessons = $publishedLessons = 0;
}

try {
    $todayRow = $db->fetch("SELECT COUNT(*) as c FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    $todayActivity = (int)($todayRow['c'] ?? 0);
} catch (Exception $e) {
    $todayActivity = 0;
}

/* ---- Recent data ---- */
$recentActivity = $activityLog->getRecentActivity(8);
$recentUsers    = array_slice($allUsers, 0, 6);

/* ---- Flash messages ---- */
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* ---- Layout variables ---- */
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require __DIR__ . '/../layouts/admin-start.php';

/* ---- Action label helper (inline) ---- */
function dashActionBadge(string $action): string {
    $map = [
        'user_login'              => ['Login',          'bg-success-subtle text-success'],
        'user_logout'             => ['Logout',         'bg-secondary-subtle text-secondary'],
        'user_registered'         => ['Registered',     'bg-primary-subtle text-primary'],
        'user_created'            => ['User Created',   'bg-info-subtle text-info'],
        'user_updated'            => ['User Updated',   'bg-warning-subtle text-warning'],
        'user_deleted'            => ['User Deleted',   'bg-danger-subtle text-danger'],
        'user_status_updated'     => ['Status Changed', 'bg-warning-subtle text-warning'],
        'password_reset_completed'=> ['Pwd Reset',      'bg-secondary-subtle text-secondary'],
        'lesson_plan_created'     => ['Lesson Created', 'bg-success-subtle text-success'],
        'lesson_plan_updated'     => ['Lesson Updated', 'bg-info-subtle text-info'],
        'lesson_plan_deleted'     => ['Lesson Deleted', 'bg-danger-subtle text-danger'],
        'pdf_exported'            => ['PDF Export',     'bg-primary-subtle text-primary'],
        'word_exported'           => ['Word Export',    'bg-primary-subtle text-primary'],
        'file_uploaded'           => ['File Upload',    'bg-success-subtle text-success'],
    ];
    $entry = $map[$action] ?? [str_replace('_', ' ', ucfirst($action)), 'bg-light text-secondary'];
    return '<span class="action-badge ' . $entry[1] . '">' . h($entry[0]) . '</span>';
}
?>

<!-- ========================================================
     ADMIN DASHBOARD CONTENT
     ======================================================== -->

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

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-breadcrumb">Welcome back, <?= h($user['first_name'] ?? 'Admin') ?>! Here's what's happening today.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/planwise/public/index.php?page=admin/users/create" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus me-1"></i> Add User
        </a>
        <a href="/planwise/public/index.php?page=admin/activity-logs" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-history me-1"></i> View Logs
        </a>
    </div>
</div>

<!-- ==================== STAT CARDS ==================== -->
<div class="row g-3 mb-4">

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($totalUsers) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($activeUsers) ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($adminCount) ?></div>
                <div class="stat-label">Admins</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($teacherCount) ?></div>
                <div class="stat-label">Teachers</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card teal">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($totalLessons) ?></div>
                <div class="stat-label">Lesson Plans</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card rose">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format($todayActivity) ?></div>
                <div class="stat-label">Today's Activity</div>
            </div>
        </div>
    </div>

</div>

<!-- ==================== MAIN CONTENT ROW ==================== -->
<div class="row g-4">

    <!-- Recent Activity Log -->
    <div class="col-12 col-xl-8">
        <div class="data-card h-100">
            <div class="data-card-header">
                <h2 class="data-card-title">
                    <i class="fas fa-history"></i> Recent Activity
                </h2>
                <a href="/planwise/public/index.php?page=admin/activity-logs"
                   class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentActivity)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                                No activity recorded yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $log): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar size-sm">
                                        <?= strtoupper(substr($log['first_name'] ?? '?', 0, 1) . substr($log['last_name'] ?? '', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; font-size:0.83rem; color:#1a2535; line-height:1.2;">
                                            <?= h($log['first_name'] . ' ' . $log['last_name']) ?>
                                        </div>
                                        <div style="font-size:0.73rem; color:#9ca3af;"><?= h($log['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= dashActionBadge($log['action']) ?></td>
                            <td style="max-width:220px;">
                                <span class="text-truncate d-block" style="max-width:220px; font-size:0.82rem; color:#6c757d;"
                                      title="<?= h($log['description'] ?? '') ?>">
                                    <?= h(truncate_text($log['description'] ?? 'N/A', 55)) ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap; font-size:0.8rem; color:#9ca3af;">
                                <?= format_datetime($log['created_at'], 'M j, g:i A') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-12 col-xl-4">
        <div class="data-card h-100">
            <div class="data-card-header">
                <h2 class="data-card-title">
                    <i class="fas fa-user-clock"></i> Recent Users
                </h2>
                <a href="/planwise/public/index.php?page=admin/users"
                   class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                    Manage <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="data-card-body p-0">
                <?php if (empty($recentUsers)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-users fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                        No users yet
                    </div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($recentUsers as $u): ?>
                        <li style="padding:0.9rem 1.25rem; border-bottom:1px solid #f7f8fb; display:flex; align-items:center; gap:0.75rem;">
                            <div class="user-avatar size-sm">
                                <?= strtoupper(substr($u['first_name'] ?? '?', 0, 1) . substr($u['last_name'] ?? '', 0, 1)) ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:0.855rem; color:#1a2535;">
                                    <?= h($u['first_name'] . ' ' . $u['last_name']) ?>
                                </div>
                                <div style="font-size:0.75rem; color:#9ca3af; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= h($u['email']) ?>
                                </div>
                            </div>
                            <div>
                                <?php if ((int)$u['role_id'] === 1): ?>
                                    <span class="badge-role-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge-role-teacher">Teacher</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge-status-active"><i class="fas fa-circle" style="font-size:0.45rem;"></i></span>
                            <?php else: ?>
                                <span class="badge-status-inactive"><i class="fas fa-circle" style="font-size:0.45rem;"></i></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($totalUsers > 6): ?>
                    <div style="padding:0.75rem 1.25rem; text-align:center;">
                        <a href="/planwise/public/index.php?page=admin/users" style="font-size:0.82rem; color:#4e7cf6; text-decoration:none; font-weight:600;">
                            View all <?= number_format($totalUsers) ?> users →
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /.row -->

<!-- ==================== QUICK ACTIONS ==================== -->
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="data-card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="/planwise/public/index.php?page=admin/users/create" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </a>
                    <a href="/planwise/public/index.php?page=admin/users" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="/planwise/public/index.php?page=admin/activity-logs" class="btn btn-outline-secondary">
                        <i class="fas fa-history me-2"></i>View All Logs
                    </a>
                    <a href="/planwise/public/index.php?page=admin/system-settings" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin-end.php'; ?>
