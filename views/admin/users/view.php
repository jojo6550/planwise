<?php
/**
 * Admin - View User
 * Shows detailed information about a user
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
    $dotenv->safeLoad();
}

require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/ActivityLog.php';

$auth = new Auth();

if (!$auth->check()) {
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

if (!$auth->hasRole(1)) {
    header('Location: /planwise/public/index.php?page=403');
    exit();
}

$currentUser = $auth->user();

// Get target user
$targetId  = (int)($_GET['id'] ?? 0);
$userModel = new User();
$targetUser = $userModel->findById($targetId);

if (!$targetUser || $targetId <= 0) {
    $_SESSION['error'] = 'User not found.';
    header('Location: /planwise/public/index.php?page=admin/users');
    exit();
}

// Get recent activity for this user
$activityLog     = new ActivityLog();
$userActivity    = $activityLog->getByUser($targetId, 10);

// Lesson plan count for user
$db = Database::getInstance();
try {
    $lpRow   = $db->fetch("SELECT COUNT(*) as c FROM lesson_plans WHERE user_id = :uid", [':uid' => $targetId]);
    $lpCount = (int)($lpRow['c'] ?? 0);
} catch (Exception $e) {
    $lpCount = 0;
}

// Flash
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// CSRF
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

$pageTitle  = h($targetUser['first_name'] . ' ' . $targetUser['last_name']);
$activePage = 'users-view';

// Initials
$initials = strtoupper(substr($targetUser['first_name'], 0, 1) . substr($targetUser['last_name'], 0, 1));

require __DIR__ . '/../../layouts/admin-start.php';

/* Action badge helper */
function viewActionBadge(string $action): string {
    $map = [
        'user_login'  => ['Login',          'bg-success-subtle text-success'],
        'user_logout' => ['Logout',         'bg-secondary-subtle text-secondary'],
        'user_updated'=> ['Profile Updated','bg-warning-subtle text-warning'],
        'lesson_plan_created' => ['Lesson Created', 'bg-info-subtle text-info'],
        'lesson_plan_updated' => ['Lesson Updated', 'bg-info-subtle text-info'],
        'lesson_plan_deleted' => ['Lesson Deleted', 'bg-danger-subtle text-danger'],
        'pdf_exported'        => ['PDF Export',     'bg-primary-subtle text-primary'],
        'word_exported'       => ['Word Export',    'bg-primary-subtle text-primary'],
        'password_reset_completed' => ['Pwd Reset', 'bg-secondary-subtle text-secondary'],
    ];
    $entry = $map[$action] ?? [str_replace('_', ' ', ucfirst($action)), 'bg-light text-secondary'];
    return '<span class="action-badge ' . $entry[1] . '">' . htmlspecialchars($entry[0]) . '</span>';
}
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">User Profile</h1>
        <p class="admin-breadcrumb">
            <a href="/planwise/public/index.php?page=admin/dashboard">Dashboard</a> /
            <a href="/planwise/public/index.php?page=admin/users">Users</a> /
            <?= h($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/planwise/public/index.php?page=admin/users/edit&id=<?= $targetUser['user_id'] ?>"
           class="btn btn-primary btn-sm">
            <i class="fas fa-pencil-alt me-1"></i> Edit User
        </a>
        <a href="/planwise/public/index.php?page=admin/users" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
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

    <!-- Left: User Profile Card -->
    <div class="col-12 col-lg-4">

        <!-- Profile card -->
        <div class="data-card mb-4">
            <div class="data-card-body text-center" style="padding:2rem 1.5rem;">
                <div class="user-avatar size-lg mx-auto mb-3"><?= $initials ?></div>
                <h2 style="font-size:1.2rem; font-weight:800; color:#1a2535; margin:0 0 0.25rem;">
                    <?= h($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?>
                </h2>
                <p style="font-size:0.855rem; color:#6c757d; margin:0 0 1rem;">
                    <?= h($targetUser['email']) ?>
                </p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <?php if ((int)$targetUser['role_id'] === 1): ?>
                        <span class="badge-role-admin"><i class="fas fa-shield-alt me-1"></i>Admin</span>
                    <?php else: ?>
                        <span class="badge-role-teacher"><i class="fas fa-chalkboard-teacher me-1"></i>Teacher</span>
                    <?php endif; ?>
                    <?php if ($targetUser['status'] === 'active'): ?>
                        <span class="badge-status-active"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Active</span>
                    <?php else: ?>
                        <span class="badge-status-inactive"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h3 class="data-card-title"><i class="fas fa-chart-bar"></i> Stats</h3>
            </div>
            <div class="data-card-body">
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-file-alt me-2 text-muted fa-sm"></i>Lesson Plans</span>
                    <span class="value"><?= number_format($lpCount) ?></span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-history me-2 text-muted fa-sm"></i>Activity Logs</span>
                    <span class="value"><?= number_format(count($userActivity)) ?>+</span>
                </div>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-calendar-plus me-2 text-muted fa-sm"></i>Joined</span>
                    <span class="value"><?= format_date($targetUser['created_at'], 'M j, Y') ?></span>
                </div>
                <?php if (!empty($targetUser['updated_at'])): ?>
                <div class="info-stat-row">
                    <span class="label"><i class="fas fa-edit me-2 text-muted fa-sm"></i>Last Updated</span>
                    <span class="value"><?= format_date($targetUser['updated_at'], 'M j, Y') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if ((int)$targetUser['user_id'] !== (int)$currentUser['user_id']): ?>
        <div class="data-card">
            <div class="data-card-header">
                <h3 class="data-card-title"><i class="fas fa-tools"></i> Actions</h3>
            </div>
            <div class="data-card-body d-flex flex-column gap-2">
                <a href="/planwise/public/index.php?page=admin/users/edit&id=<?= $targetUser['user_id'] ?>"
                   class="btn btn-primary w-100">
                    <i class="fas fa-pencil-alt me-2"></i>Edit Profile
                </a>

                <!-- Status toggle -->
                <form action="/planwise/controllers/UserController.php?action=updateStatus"
                      method="POST" id="statusForm">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="user_id"    value="<?= $targetUser['user_id'] ?>">
                    <input type="hidden" name="status"
                           value="<?= $targetUser['status'] === 'active' ? 'inactive' : 'active' ?>">
                    <button type="submit" class="btn w-100 <?= $targetUser['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                            onclick="return confirm('Change this user\'s status?')">
                        <?php if ($targetUser['status'] === 'active'): ?>
                            <i class="fas fa-user-slash me-2"></i>Deactivate User
                        <?php else: ?>
                            <i class="fas fa-user-check me-2"></i>Activate User
                        <?php endif; ?>
                    </button>
                </form>

                <!-- Delete -->
                <button class="btn btn-outline-danger w-100" id="deleteUserBtn">
                    <i class="fas fa-trash-alt me-2"></i>Delete User
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Activity -->
    <div class="col-12 col-lg-8">
        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h2>
                <a href="/planwise/public/index.php?page=admin/activity-logs&user_id=<?= $targetUser['user_id'] ?>"
                   class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date / Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($userActivity)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                                No activity recorded for this user
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userActivity as $log): ?>
                        <tr>
                            <td><?= viewActionBadge($log['action']) ?></td>
                            <td style="max-width:260px;">
                                <span class="d-block text-truncate" style="max-width:260px; font-size:0.82rem; color:#6c757d;"
                                      title="<?= h($log['description'] ?? '') ?>">
                                    <?= h(truncate_text($log['description'] ?? 'N/A', 60)) ?>
                                </span>
                            </td>
                            <td style="font-family:monospace; font-size:0.8rem; color:#9ca3af;">
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
        </div>
    </div>

</div>

<?php
$_targetUserId   = (int)$targetUser['user_id'];
$_targetUserName = h($targetUser['first_name'] . ' ' . $targetUser['last_name']);
$_csrfJson       = json_encode($csrfToken);
$extraScripts    = <<<ENDJS
<script>
const deleteBtn = document.getElementById('deleteUserBtn');
if (deleteBtn) {
    deleteBtn.addEventListener('click', async function() {
        const name = {$_targetUserName};
        if (!confirm('Permanently delete "' + name + '"?\\n\\nThis action cannot be undone.')) return;
        try {
            const res = await fetch('/planwise/controllers/UserController.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: {$_targetUserId}, csrf_token: {$_csrfJson} })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/planwise/public/index.php?page=admin/users';
            } else {
                alert('Error: ' + (data.message || 'Failed to delete user.'));
            }
        } catch(e) {
            alert('Network error. Please try again.');
        }
    });
}
</script>
ENDJS;
require __DIR__ . '/../../layouts/admin-end.php';
?>
