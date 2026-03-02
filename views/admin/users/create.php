<?php
/**
 * Admin - Create User
 * Form to create a new user account
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
require_once __DIR__ . '/../../../classes/Role.php';

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
$roleModel = new Role();
$roles     = $roleModel->getAll();

// Generate / fetch CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Flash messages
$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Retain form values after failed submission
$formData = [
    'first_name' => $_SESSION['form_first_name'] ?? '',
    'last_name'  => $_SESSION['form_last_name']  ?? '',
    'email'      => $_SESSION['form_email']      ?? '',
    'role_id'    => $_SESSION['form_role_id']    ?? 2,
    'status'     => $_SESSION['form_status']     ?? 'active',
];
unset($_SESSION['form_first_name'], $_SESSION['form_last_name'],
      $_SESSION['form_email'],      $_SESSION['form_role_id'],
      $_SESSION['form_status']);

$pageTitle  = 'Create User';
$activePage = 'users';

require __DIR__ . '/../../layouts/admin-start.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Create New User</h1>
        <p class="admin-breadcrumb">
            <a href="/planwise/public/index.php?page=admin/dashboard">Dashboard</a> /
            <a href="/planwise/public/index.php?page=admin/users">Users</a> /
            Create
        </p>
    </div>
    <a href="/planwise/public/index.php?page=admin/users" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Users
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show auto-dismiss mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">

        <div class="data-card">
            <div class="data-card-header">
                <h2 class="data-card-title">
                    <i class="fas fa-user-plus"></i> User Information
                </h2>
            </div>
            <div class="data-card-body">
                <form action="/planwise/controllers/UserController.php?action=create"
                      method="POST" id="createUserForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

                    <!-- Name row -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="first_name" class="form-label fw-semibold">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?= h($formData['first_name']) ?>"
                                   placeholder="e.g. Jane" maxlength="100" required autofocus>
                            <div class="invalid-feedback">First name is required.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="last_name" class="form-label fw-semibold">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?= h($formData['last_name']) ?>"
                                   placeholder="e.g. Smith" maxlength="100" required>
                            <div class="invalid-feedback">Last name is required.</div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= h($formData['email']) ?>"
                               placeholder="jane.smith@school.edu" maxlength="150" required>
                        <div class="invalid-feedback">A valid email address is required.</div>
                    </div>

                    <!-- Password -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="password" class="form-label fw-semibold">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       minlength="8" required placeholder="Min 8 characters">
                                <button class="btn btn-outline-secondary" type="button" id="togglePass"
                                        title="Show/hide password">
                                    <i class="fas fa-eye fa-sm"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Password must be at least 8 characters.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="password_confirm" class="form-label fw-semibold">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirm"
                                       name="password_confirm" minlength="8" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassConfirm"
                                        title="Show/hide password">
                                    <i class="fas fa-eye fa-sm"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>

                    <!-- Password strength indicator -->
                    <div class="mb-3" id="passwordStrengthWrap" style="display:none;">
                        <div class="d-flex gap-1 mb-1">
                            <div class="flex-fill rounded" id="ps1" style="height:4px; background:#e9ecef;"></div>
                            <div class="flex-fill rounded" id="ps2" style="height:4px; background:#e9ecef;"></div>
                            <div class="flex-fill rounded" id="ps3" style="height:4px; background:#e9ecef;"></div>
                            <div class="flex-fill rounded" id="ps4" style="height:4px; background:#e9ecef;"></div>
                        </div>
                        <small id="passwordStrengthLabel" class="text-muted"></small>
                    </div>

                    <!-- Role & Status -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="role_id" class="form-label fw-semibold">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select role…</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['role_id'] ?>"
                                            <?= (string)$formData['role_id'] === (string)$r['role_id'] ? 'selected' : '' ?>>
                                        <?= h($r['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active"   <?= $formData['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/planwise/public/index.php?page=admin/users" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
/* ---- Password visibility toggle ---- */
function toggleVisibility(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn   = document.getElementById(btnId);
    btn.addEventListener('click', function() {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'fas fa-eye-slash fa-sm' : 'fas fa-eye fa-sm';
    });
}
toggleVisibility('password', 'togglePass');
toggleVisibility('password_confirm', 'togglePassConfirm');

/* ---- Password strength meter ---- */
const pwField = document.getElementById('password');
const pwWrap  = document.getElementById('passwordStrengthWrap');
const pwLabel = document.getElementById('passwordStrengthLabel');
const bars    = [document.getElementById('ps1'), document.getElementById('ps2'),
                 document.getElementById('ps3'), document.getElementById('ps4')];

pwField.addEventListener('input', function() {
    const pw = this.value;
    pwWrap.style.display = pw.length > 0 ? 'block' : 'none';
    let score = 0;
    if (pw.length >= 8)            score++;
    if (/[A-Z]/.test(pw))          score++;
    if (/[0-9]/.test(pw))          score++;
    if (/[^A-Za-z0-9]/.test(pw))   score++;

    const colors = ['#dc3545','#fd7e14','#ffc107','#198754'];
    const labels = ['Weak','Fair','Good','Strong'];
    bars.forEach((b, i) => {
        b.style.background = i < score ? colors[score - 1] : '#e9ecef';
    });
    pwLabel.textContent  = score > 0 ? 'Strength: ' + labels[score - 1] : '';
    pwLabel.style.color  = score > 0 ? colors[score - 1] : '#6c757d';
});

/* ---- Confirm password match ---- */
document.getElementById('password_confirm').addEventListener('input', function() {
    const pw = document.getElementById('password').value;
    this.setCustomValidity(pw && this.value !== pw ? 'Passwords do not match' : '');
});

/* ---- Form validation ---- */
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault(); e.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>
JS;
require __DIR__ . '/../../layouts/admin-end.php';
?>
