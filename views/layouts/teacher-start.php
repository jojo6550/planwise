<?php
/**
 * Teacher Layout - Start Partial
 * Include at the top of every teacher view AFTER setting:
 *   $pageTitle  (string) - Page title
 *   $activePage (string) - Active nav item: dashboard|lesson-plans|profile
 */

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$_active = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'PlanWise'); ?> - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS with BASE_URL -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>/css/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/planwise/public/index.php?page=teacher/dashboard">PlanWise</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php
                    $navItems = [
                        'dashboard'    => ['label' => 'Dashboard',    'url' => '/planwise/public/index.php?page=teacher/dashboard'],
                        'lesson-plans' => ['label' => 'Lesson Plans', 'url' => '/planwise/public/index.php?page=teacher/lesson-plans'],
                        'profile'      => ['label' => 'Profile',      'url' => '/planwise/public/index.php?page=teacher/profile'],
                    ];
                    foreach ($navItems as $key => $item):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $_active === $key ? 'active' : ''; ?>"
                           href="<?php echo $item['url']; ?>">
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/planwise/controllers/AuthController.php?action=logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
