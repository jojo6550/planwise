<?php
/**
 * Admin Edit User View
 * User editing form for administrators
 * CS334 Module 3 - Different access levels (13)
 */

// Require admin authentication
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../middleware/RoleMiddleware.php';

$authMiddleware = new AuthMiddleware();
$authMiddleware->checkAuthentication();

$roleMiddleware = new RoleMiddleware();
$roleMiddleware->requireRole(1); // Admin only

// Get user ID from URL
$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: /planwise/public/index.php?page=admin/users');
    exit();
}

// Initialize dependencies
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Role.php';

$userModel = new User();
$roleModel = new Role();

// Get user data
$user = $userModel->findById($userId);
if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: /planwise/public/index.php?page=admin/users');
    exit();
}

$roles = $roleModel->getAll() ?: [];

// Get CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/planwise/public/index.php?page=admin/users" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php include __DIR__ . '/../components/alerts.php'; ?>

            <!-- Edit User Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Edit User Information</h5>
                        </div>
                        <div class="card-body">
                            <form action="/planwise/controllers/UserController.php?action=update" method="post" id="editUserForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                               required maxlength="50">
                                        <div class="invalid-feedback">
                                            Please provide a valid first name.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                               required maxlength="50">
                                        <div class="invalid-feedback">
                                            Please provide a valid last name.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           required maxlength="100">
                                    <div class="invalid-feedback">
                                        Please provide a valid email address.
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="">Select a role</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['role_id']; ?>"
                                                        <?php echo $role['role_id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a role.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a status.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <small class="text-muted">
                                                <strong>Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small class="text-muted">
                                                <strong>Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($user['updated_at'] ?? $user['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="/planwise/public/index.php?page=admin/users" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Leave blank to keep the current password unchanged.
                            </p>
                            <form action="/planwise/controllers/UserController.php?action=changePassword" method="post" id="changePasswordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                        <div class="invalid-feedback">
                                            Password must be at least 8 characters long.
                                        </div>
                                        <div class="form-text">
                                            Minimum 8 characters, include uppercase, lowercase, and numbers.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" minlength="8">
                                        <div class="invalid-feedback">
                                            Passwords do not match.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Password confirmation validation for change password form
document.getElementById('confirm_new_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;

    if (password && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Password strength validation
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const confirmField = document.getElementById('confirm_new_password');

    if (password) {
        // Check password strength
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasMinLength = password.length >= 8;

        if (!hasMinLength) {
            this.setCustomValidity('Password must be at least 8 characters long');
        } else if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
            this.setCustomValidity('Password must contain uppercase, lowercase, and numeric characters');
        } else {
            this.setCustomValidity('');
        }

        // Re-validate confirmation
        if (confirmField.value) {
            confirmField.dispatchEvent(new Event('input'));
        }
    } else {
        this.setCustomValidity('');
    }
});

// Email uniqueness check (AJAX) - exclude current user
let emailCheckTimeout;
document.getElementById('email').addEventListener('input', function() {
    clearTimeout(emailCheckTimeout);
    const email = this.value;
    const currentEmail = '<?php echo addslashes($user['email']); ?>';
    const emailField = this;

    if (email && email.includes('@') && email !== currentEmail) {
        emailCheckTimeout = setTimeout(() => {
            fetch('/planwise/public/js/ajax-handler.php?action=check_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email, exclude_user_id: <?php echo $user['user_id']; ?> })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    emailField.setCustomValidity('This email address is already registered');
                    emailField.classList.add('is-invalid');
                } else {
                    emailField.setCustomValidity('');
                    emailField.classList.remove('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error checking email:', error);
            });
        }, 500);
    } else {
        emailField.setCustomValidity('');
        emailField.classList.remove('is-invalid');
    }
});

// Form validation
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const form = this;

    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();

        // Find first invalid field and focus it
        const firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
            firstInvalid.focus();
        }
    }

    form.classList.add('was-validated');
});

// Change password form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_new_password').value;

    if (newPassword || confirmPassword) {
        // If either field is filled, both must be valid
        if (!newPassword || !confirmPassword) {
            e.preventDefault();
            alert('Please fill in both password fields or leave them empty.');
            return;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
    }
});
</script>
