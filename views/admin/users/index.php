<?php
/**
 * Admin - User Management Index
 * Lists all users with CRUD actions
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

$user      = $auth->user();
$userModel = new User();

// Get all users
$allUsers = $userModel->getAll();

// Search / filter
$search    = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$filteredUsers = $allUsers;

if ($search !== '') {
    $s = strtolower($search);
    $filteredUsers = array_filter($filteredUsers, function($u) use ($s) {
        return str_contains(strtolower($u['first_name'] . ' ' . $u['last_name']), $s)
            || str_contains(strtolower($u['email']), $s);
    });
}

if ($roleFilter !== '') {
    $filteredUsers = array_filter($filteredUsers, fn($u) => (string)$u['role_id'] === $roleFilter);
}

if ($statusFilter !== '') {
    $filteredUsers = array_filter($filteredUsers, fn($u) => $u['status'] === $statusFilter);
}

$filteredUsers = array_values($filteredUsers);

// Flash messages
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Layout vars
$pageTitle  = 'User Management';
$activePage = 'users';

require __DIR__ . '/../../layouts/admin-start.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">User Management</h1>
        <p class="admin-breadcrumb">
            <a href="/planwise/public/index.php?page=admin/dashboard">Dashboard</a> /
            Users
        </p>
    </div>
    <a href="/planwise/public/index.php?page=admin/users/create" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Add New User
    </a>
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

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <?php
    $total   = count($allUsers);
    $active  = count(array_filter($allUsers, fn($u) => $u['status'] === 'active'));
    $admins  = count(array_filter($allUsers, fn($u) => (int)$u['role_id'] === 1));
    $teachers= count(array_filter($allUsers, fn($u) => (int)$u['role_id'] === 2));
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $active ?></div>
                <div class="stat-label">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $admins ?></div>
                <div class="stat-label">Admins</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?= $teachers ?></div>
                <div class="stat-label">Teachers</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="" class="admin-filter-bar mb-4">
    <input type="hidden" name="page" value="admin/users">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">Search</label>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#adb5bd;font-size:0.8rem;"></i>
                <input type="text" name="search" class="form-control form-control-sm ps-4"
                       placeholder="Name or email…" value="<?= h($search) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">Role</label>
            <select name="role" class="form-select form-select-sm">
                <option value="">All Roles</option>
                <option value="1" <?= $roleFilter === '1' ? 'selected' : '' ?>>Admin</option>
                <option value="2" <?= $roleFilter === '2' ? 'selected' : '' ?>>Teacher</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:0.82rem;">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active"   <?= $statusFilter === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
            <a href="?page=admin/users" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times me-1"></i> Clear
            </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Export Section -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <h3 class="data-card-title" style="font-size: 1rem; margin: 0;">
            <i class="fas fa-download me-2"></i>Export Teacher Accounts
        </h3>
    </div>
    <div class="p-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-auto">
                <label class="form-label fw-semibold" style="font-size:0.85rem; margin-bottom: 0;">Export Type:</label>
            </div>
            <div class="col-md-auto">
                <select id="exportType" class="form-select form-select-sm" style="width: 150px;">
                    <option value="all">All Teachers</option>
                    <option value="filtered">Filtered Results</option>
                    <option value="selected">Selected Only</option>
                </select>
            </div>
            <div class="col-md-auto">
                <label class="form-label fw-semibold" style="font-size:0.85rem; margin-bottom: 0;">Format:</label>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="button" id="exportCSV" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i>CSV
                </button>
                <button type="button" id="exportXLS" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-file-excel me-1"></i>XLS
                </button>
            </div>
            <div class="col-md-auto ms-auto">
                <small class="text-muted">Select checkbox to export specific teachers</small>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="data-card">
    <div class="data-card-header">
        <h2 class="data-card-title">
            <i class="fas fa-list"></i>
            All Users
            <span class="badge bg-secondary ms-1" style="font-size:0.72rem; font-weight:600;">
                <?= count($filteredUsers) ?>
            </span>
        </h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table" id="usersTable">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Select all teachers" style="cursor: pointer;">
                    </th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($filteredUsers)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-users fa-2x mb-2 d-block" style="opacity:0.3;"></i>
                        No users found
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($filteredUsers as $u): ?>
                <tr data-user-role="<?= (int)$u['role_id'] ?>">
                    <td class="text-center">
                        <?php if ((int)$u['role_id'] === 2): ?>
                        <input type="checkbox" class="form-check-input user-checkbox" data-user-id="<?= $u['user_id'] ?>" data-user-name="<?= h($u['first_name'] . ' ' . $u['last_name']) ?>" style="cursor: pointer;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar size-sm">
                                <?= strtoupper(substr($u['first_name'] ?? '?', 0, 1) . substr($u['last_name'] ?? '', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600; font-size:0.875rem; color:#1a2535; line-height:1.2;">
                                    <?= h($u['first_name'] . ' ' . $u['last_name']) ?>
                                </div>
                                <div style="font-size:0.75rem; color:#9ca3af;">ID #<?= $u['user_id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.855rem;"><?= h($u['email']) ?></td>
                    <td>
                        <?php if ((int)$u['role_id'] === 1): ?>
                            <span class="badge-role-admin"><i class="fas fa-shield-alt me-1" style="font-size:0.65rem;"></i>Admin</span>
                        <?php else: ?>
                            <span class="badge-role-teacher"><i class="fas fa-chalkboard-teacher me-1" style="font-size:0.65rem;"></i>Teacher</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn p-0 border-0 bg-transparent status-toggle-btn"
                                data-user-id="<?= $u['user_id'] ?>"
                                data-status="<?= $u['status'] ?>"
                                data-csrf="<?= $csrfToken ?>"
                                title="Click to toggle status"
                                <?= ((int)$u['user_id'] === (int)$user['user_id']) ? 'disabled' : '' ?>>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge-status-active"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-status-inactive"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Inactive</span>
                            <?php endif; ?>
                        </button>
                    </td>
                    <td style="font-size:0.8rem; color:#9ca3af; white-space:nowrap;">
                        <?= format_date($u['created_at'], 'M j, Y') ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="/planwise/public/index.php?page=admin/users/view&id=<?= $u['user_id'] ?>"
                               class="action-btn primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/planwise/public/index.php?page=admin/users/edit&id=<?= $u['user_id'] ?>"
                               class="action-btn" title="Edit">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <?php if ((int)$u['user_id'] !== (int)$user['user_id']): ?>
                            <button class="action-btn danger delete-user-btn"
                                    data-user-id="<?= $u['user_id'] ?>"
                                    data-user-name="<?= h($u['first_name'] . ' ' . $u['last_name']) ?>"
                                    title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = <<<JS
<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

/* ---- Select All Checkbox ---- */
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });
}

/* ---- Update Select All Status ---- */
document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.user-checkbox');
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkedCount === allCheckboxes.length && allCheckboxes.length > 0;
        }
    });
});

/* ---- Export Functions ---- */
function getSelectedTeachers() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    return Array.from(checkboxes).map(cb => parseInt(cb.dataset.userId));
}

function handleExport(format) {
    const exportType = document.getElementById('exportType').value;
    const selectedTeachers = getSelectedTeachers();

    if (exportType === 'selected' && selectedTeachers.length === 0) {
        alert('Please select at least one teacher to export.');
        return;
    }

    let url = '/planwise/controllers/ExportController.php?action=exportTeachers&format=' + format;

    if (exportType === 'all') {
        url += '&type=all';
    } else if (exportType === 'filtered') {
        url += '&type=all';
    } else if (exportType === 'selected') {
        url += '&type=multiple&user_ids=' + selectedTeachers.join(',');
    }

    window.location.href = url;
}

document.getElementById('exportCSV').addEventListener('click', function() {
    handleExport('csv');
});

document.getElementById('exportXLS').addEventListener('click', function() {
    handleExport('xls');
});

/* ---- Status Toggle ---- */
document.querySelectorAll('.status-toggle-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const userId  = this.dataset.userId;
        const current = this.dataset.status;
        const newStatus = current === 'active' ? 'inactive' : 'active';

        if (!confirm(`Set this user to ${newStatus}?`)) return;

        try {
            const res = await fetch('/planwise/controllers/UserController.php?action=updateStatus', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: parseInt(userId), status: newStatus, csrf_token: CSRF_TOKEN })
            });
            const data = await res.json();

            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update status.'));
            }
        } catch (e) {
            alert('Network error. Please try again.');
        }
    });
});

/* ---- Delete User ---- */
document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const userId   = this.dataset.userId;
        const userName = this.dataset.userName;

        if (!confirm(`Delete user "${userName}"?\n\nThis will permanently remove their account and all associated data. This action cannot be undone.`)) return;

        try {
            const res = await fetch('/planwise/controllers/UserController.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: parseInt(userId), csrf_token: CSRF_TOKEN })
            });
            const data = await res.json();

            if (data.success) {
                const row = this.closest('tr');
                row.style.transition = 'opacity 0.3s, background 0.3s';
                row.style.opacity = '0';
                row.style.background = '#fee2e2';
                setTimeout(() => row.remove(), 350);
            } else {
                alert('Error: ' + (data.message || 'Failed to delete user.'));
            }
        } catch (e) {
            alert('Network error. Please try again.');
        }
    });
});
</script>
JS;
require __DIR__ . '/../../layouts/admin-end.php';
?>
