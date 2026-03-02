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
    'dashboard'       => ['label' => 'Dashboard',       'icon' => 'fas fa-tachometer-alt', 'url' => '/planwise/public/index.php?page=admin/dashboard',       'group' => ['dashboard']],
    'users'           => ['label' => 'User Management', 'icon' => 'fas fa-users',           'url' => '/planwise/public/index.php?page=admin/users',           'group' => ['users','users-create','users-edit','users-view']],
    'activity-logs'   => ['label' => 'Activity Logs',   'icon' => 'fas fa-history',         'url' => '/planwise/public/index.php?page=admin/activity-logs',   'group' => ['activity-logs']],
    'system-settings' => ['label' => 'System Settings', 'icon' => 'fas fa-cog',             'url' => '/planwise/public/index.php?page=admin/system-settings', 'group' => ['system-settings']],
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
    <link rel="stylesheet" href="/planwise/public/css/admin.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="admin-body">

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===================== SIDEBAR ===================== -->
<nav class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">

    <a href="/planwise/public/index.php?page=admin/dashboard" class="sidebar-brand">
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

        <div class="sidebar-divider"></div>

        <a href="/planwise/controllers/AuthController.php?action=logout" class="sidebar-link"
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
                        <a class="dropdown-item rounded" href="/planwise/public/index.php?page=admin/system-settings"
                           style="font-size:0.875rem;">
                            <i class="fas fa-cog me-2 text-muted fa-sm"></i> Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded text-danger"
                           href="/planwise/controllers/AuthController.php?action=logout"
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
