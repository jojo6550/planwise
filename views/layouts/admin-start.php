<?php
/**
 * Admin Layout - Start Partial
 * Include at the top of every admin view AFTER setting:
 *   $pageTitle  (string) - Page title
 *   $activePage (string) - Active nav item key: dashboard|users|activity-logs|system-settings
 *   $user       (array)  - Auth user data from $auth->user()
 */

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$_initials = strtoupper(
    substr($user['first_name'] ?? 'A', 0, 1) .
    substr($user['last_name'] ?? '', 0, 1)
);
$_fullName = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$_email    = htmlspecialchars($user['email'] ?? '');
$_active   = $activePage ?? '';

$_navLinks = [
    'dashboard'       => ['label' => 'Dashboard',       'icon' => 'fas fa-tachometer-alt', 'url' => BASE_URL . '/index.php?page=admin/dashboard',       'group' => ['dashboard']],
    'users'           => ['label' => 'User Management', 'icon' => 'fas fa-users',           'url' => BASE_URL . '/index.php?page=admin/users',           'group' => ['users','users-create','users-edit','users-view']],
    'activity-logs'   => ['label' => 'Activity Logs',   'icon' => 'fas fa-history',         'url' => BASE_URL . '/index.php?page=admin/activity-logs',   'group' => ['activity-logs']],
    'system-settings' => ['label' => 'System Settings', 'icon' => 'fas fa-cog',             'url' => BASE_URL . '/index.php?page=admin/system-settings', 'group' => ['system-settings']],
    'import'          => ['label' => 'Data Import',     'icon' => 'fas fa-file-import',     'url' => BASE_URL . '/index.php?page=admin/import',           'group' => ['import']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — PlanWise Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS with BASE_URL -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/css/admin.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="admin-body">

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===================== SIDEBAR ===================== -->
<nav class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">

    <a href="<?= BASE_URL ?>/index.php?page=admin/dashboard" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="fas fa-book-open"></i>
        </div>
        <span>PlanWise</span>
    </a>

    <div class="sidebar-nav">
        <div class="sidebar-section-title">Main Menu</div>

        <?php foreach (['dashboard', 'users'] as $key):
            $link = $_navLinks[$key];
            $isActive = in_array($_active, $link['group']);
        ?>
        <a href="<?= $link['url'] ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
            <i class="<?= $link['icon'] ?>"></i>
            <span><?= $link['label'] ?></span>
        </a>
        <?php endforeach; ?>

        <div class="sidebar-section-title">Reports</div>

        <?php
        $link = $_navLinks['activity-logs'];
        $isActive = in_array($_active, $link['group']);
        ?>
        <a href="<?= $link['url'] ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
            <i class="<?= $link['icon'] ?>"></i>
            <span><?= $link['label'] ?></span>
        </a>

        <div class="sidebar-section-title">Configuration</div>

        <?php
        $link = $_navLinks['system-settings'];
        $isActive = in_array($_active, $link['group']);
        ?>
        <a href="<?= $link['url'] ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
            <i class="<?= $link['icon'] ?>"></i>
            <span><?= $link['label'] ?></span>
        </a>

        <div class="sidebar-section-title">Data</div>

        <?php
        $link = $_navLinks['import'];
        $isActive = in_array($_active, $link['group']);
        ?>
        <a href="<?= $link['url'] ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
            <i class="<?= $link['icon'] ?>"></i>
            <span><?= $link['label'] ?></span>
        </a>

        <div class="sidebar-divider"></div>

        <a href="<?= BASE_URL ?>/index.php?page=logout" class="sidebar-link"
           onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="user-avatar size-sm"><?= $_initials ?></div>
            <div>
                <div class="user-name"><?= $_fullName ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>
</nav>

<!-- ===================== MAIN WRAPPER ===================== -->
<div class="admin-main" id="adminMain">

    <!-- Topbar -->
    <header class="admin-topbar">
        <button class="topbar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-search d-none d-md-flex">
            <i class="fas fa-search" style="color:#adb5bd; font-size:0.8rem;"></i>
            <input type="text" placeholder="Search..." id="adminSearchInput">
        </div>

        <div class="flex-grow-1"></div>

        <div class="d-flex align-items-center gap-2">
            <!-- Notifications (placeholder) -->
            <button class="btn btn-sm" style="color:#9ca3af; border-radius:9px;" title="Notifications">
                <i class="fas fa-bell"></i>
            </button>

            <!-- User dropdown -->
            <div class="dropdown">
                <a href="#" class="topbar-user-btn dropdown-toggle text-decoration-none"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar size-sm"><?= $_initials ?></div>
                    <span class="d-none d-md-inline"><?= $_fullName ?></span>
                    <i class="fas fa-chevron-down topbar-chevron"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow"
                    style="border-radius:12px; padding:0.5rem; min-width:210px;">
                    <li>
                        <div class="px-3 py-2 border-bottom mb-1">
                            <div style="font-weight:700; font-size:0.875rem; color:#1a2535;"><?= $_fullName ?></div>
                            <div style="font-size:0.78rem; color:#6c757d;"><?= $_email ?></div>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item rounded" href="<?= BASE_URL ?>/index.php?page=admin/system-settings"
                           style="font-size:0.875rem;">
                            <i class="fas fa-cog me-2 text-muted fa-sm"></i> Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded text-danger"
                           href="<?= BASE_URL ?>/index.php?page=logout"
                           onclick="return confirm('Are you sure you want to logout?');"
                           style="font-size:0.875rem;">
                            <i class="fas fa-sign-out-alt me-2 fa-sm"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Content area -->
    <main class="admin-content">
<script>
/* === Admin Topbar Search (AJAX) === */
(function () {
    const BASE_URL = '<?= BASE_URL ?>';
    const input = document.getElementById('adminSearchInput');
    if (!input) return;

    let debounceTimer;
    const wrap = input.closest('.topbar-search');
    wrap.style.position = 'relative';

    const dropdown = document.createElement('div');
    dropdown.id = 'searchDropdown';
    dropdown.style.cssText = 'position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:1050;' +
        'background:#fff;border:1px solid #dee2e6;border-radius:8px;' +
        'box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:300px;overflow-y:auto;display:none;';
    wrap.appendChild(dropdown);

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (!q) { dropdown.style.display = 'none'; return; }

        debounceTimer = setTimeout(async function () {
            try {
                const res = await fetch(
                    BASE_URL + '/index.php?page=admin/ajax/search-users&q=' + encodeURIComponent(q)
                );
                const data = await res.json();
                if (!data.success) { dropdown.style.display = 'none'; return; }

                if (data.data.length === 0) {
                    dropdown.innerHTML = '<div style="padding:10px 14px;color:#6c757d;font-size:.83rem;">No users found</div>';
                } else {
                    dropdown.innerHTML = data.data.slice(0, 8).map(function (u) {
                        const initials = ((u.first_name || '?')[0] + (u.last_name || '?')[0]).toUpperCase();
                        return '<a href="' + BASE_URL + '/index.php?page=admin/users/view&id=' + u.user_id + '"' +
                            ' style="display:flex;align-items:center;gap:10px;padding:8px 14px;text-decoration:none;' +
                            'color:#1a2535;border-bottom:1px solid #f5f5f5;">' +
                            '<div class="user-avatar size-sm">' + initials + '</div>' +
                            '<div>' +
                            '<div style="font-size:.83rem;font-weight:600;">' + u.first_name + ' ' + u.last_name + '</div>' +
                            '<div style="font-size:.73rem;color:#9ca3af;">' + u.email + '</div>' +
                            '</div></a>';
                    }).join('');
                }
                dropdown.style.display = 'block';
            } catch (e) { /* silent fail on network error */ }
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) dropdown.style.display = 'none';
    });
})();
</script>
