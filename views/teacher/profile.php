<?php
/**
 * Teacher Profile Page
 * Allows teachers to view and edit their profile information
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require authentication
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->check()) {
    $_SESSION['error'] = 'Please login to access your profile';
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

// Get current user data
$user = $auth->user();
$userModel = new User();
$userDetails = $userModel->findById($user['user_id']);
if (!$userDetails) {
    error_log("Profile page: userDetails fetch failed for user_id {$user['user_id']}, using session data");
    $userDetails = [
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'email' => $user['email'] ?? '',
        'status' => 'active',
        'created_at' => date('Y-m-d'),
        'role_name' => 'Teacher (Role_' . ($_SESSION['role_id'] ?? '?') . ')'
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: /planwise/public/index.php?page=teacher/profile');
        exit();
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Enhanced validation
    // Validation (keep only enhanced version)
    $errors = [];
    if (empty($firstName) || strlen($firstName) > 50) {
        $errors[] = 'First name is required and must be 50 characters or less';
    }
    if (empty($lastName) || strlen($lastName) > 50) {
        $errors[] = 'Last name is required and must be 50 characters or less';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format (max 100 characters)';
    }
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($errors)) {
            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($email)
            ];

            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                require_once __DIR__ . '/../../classes/File.php';
                $fileHandler = new File();
                $uploadResult = $fileHandler->profileUpload($_FILES['profile_picture'], $user['user_id']);
                if ($uploadResult['success']) {
                    $updateData['profile_picture'] = $uploadResult['profile_picture'];
                    $updateData['profile_thumbnail'] = $uploadResult['profile_thumbnail'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }

            $result = $userModel->update($user['user_id'], $updateData);

            if ($result['success']) {

        if ($result['success']) {
            // Update session data
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;

            $_SESSION['success'] = 'Profile updated successfully';
            header('Location: /planwise/public/index.php?page=teacher/profile');
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    // Redirect to avoid form resubmission
    header('Location: /planwise/public/index.php?page=teacher/profile');
    exit();
}

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/planwise/public/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/planwise/public/index.php?page=teacher/dashboard">
                PlanWise
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/planwise/public/index.php?page=teacher/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/planwise/public/index.php?page=teacher/lesson-plans">Lesson Plans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/planwise/public/index.php?page=teacher/profile">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/planwise/controllers/AuthController.php?action=logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Success Alert -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <img id="profileAvatar" src="<?= htmlspecialchars($userModel->getProfileImage($user['user_id'], true)) ?>" 
                                 alt="Profile Picture" width="64" height="64" class="rounded-circle object-fit-cover me-3 border border-2 border-primary" 
                                 style="object-fit: cover; width: 64px; height: 64px;">

                            <div>
                                <h2 class="mb-1">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </h2>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($user['email']); ?> •
                                    <?php echo htmlspecialchars($userDetails['role_name'] ?? 'Teacher'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
<form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($userDetails['first_name'] ?? $user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($userDetails['last_name'] ?? $user['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($userDetails['email'] ?? $user['email']); ?>" required>
                            </div>
    <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">Upload JPEG, PNG, GIF or WebP (max 2MB). Square images recommended.</div>
                                <img id="imagePreview" class="mt-2 rounded-circle border p-1" src="" width="80" height="80" style="display:none; object-fit: cover;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userDetails['role_name'] ?? 'Teacher'); ?>" readonly>
                                <div class="form-text">Your role cannot be changed from this page.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Status</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($userDetails['status'] ?? 'Active')); ?>" readonly>
                                <div class="form-text">Contact an administrator to change your account status.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(date('F j, Y', strtotime($userDetails['created_at'] ?? 'now'))); ?>" readonly>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                                    </svg>
                                    Update Profile
                                </button>
                                <a href="/planwise/public/index.php?page=teacher/dashboard" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Account Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Last Login</span>
                            <span><?php echo htmlspecialchars(date('M j, Y g:i A', $user['login_time'] ?? time())); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Account Created</span>
                            <span><?php echo htmlspecialchars(date('M j, Y', strtotime($userDetails['created_at'] ?? 'now'))); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/planwise/public/index.php?page=teacher/dashboard" class="btn btn-outline-primary btn-sm">
                                Back to Dashboard
                            </a>
                            <a href="/planwise/public/index.php?page=teacher/lesson-plans/create" class="btn btn-outline-success btn-sm">
                                Create Lesson Plan
                            </a>
                            <a href="/planwise/controllers/AuthController.php?action=logout" class="btn btn-outline-danger btn-sm">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?php echo date('Y'); ?> PlanWise. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>

</html>