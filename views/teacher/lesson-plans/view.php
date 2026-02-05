<?php
/**
 * View Lesson Plan
 * Display lesson plan details with file attachments
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/LessonPlan.php';
require_once __DIR__ . '/../../../classes/LessonSection.php';
require_once __DIR__ . '/../../../classes/File.php';
require_once __DIR__ . '/../../../classes/QRCode.php';

$auth = new Auth();

// Redirect if not authenticated
if (!$auth->check()) {
    $_SESSION['error'] = 'Please login to view lesson plans';
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

$lessonPlanId = (int)($_GET['id'] ?? 0);
if ($lessonPlanId <= 0) {
    $_SESSION['error'] = 'Invalid lesson plan ID';
    header('Location: /planwise/public/index.php?page=teacher/lesson-plans');
    exit();
}

$user = $auth->user();
$lessonPlan = new LessonPlan();
$lessonSection = new LessonSection();
$fileHandler = new File();

$plan = $lessonPlan->getById($lessonPlanId, $user['user_id']);
if (!$plan) {
    $_SESSION['error'] = 'Lesson plan not found or unauthorized';
    header('Location: /planwise/public/index.php?page=teacher/lesson-plans');
    exit();
}

$sections = $lessonSection->getByLessonPlan($lessonPlanId);
$files = $fileHandler->getByLessonPlan($lessonPlanId);
$qrCode = new QRCode();
$qr = $qrCode->getByLessonPlanId($lessonPlanId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plan['title']); ?> - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/planwise/public/index.php?page=teacher/dashboard">PlanWise</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/planwise/public/index.php?page=teacher/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/planwise/public/index.php?page=teacher/lesson-plans">Lesson Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="/planwise/public/index.php?page=teacher/profile">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="/planwise/controllers/AuthController.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><?php echo htmlspecialchars($plan['title']); ?></h2>
                <p class="text-muted">Created on <?php echo date('F j, Y', strtotime($plan['created_at'])); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/planwise/public/index.php?page=teacher/lesson-plans/edit&id=<?php echo $plan['lesson_id']; ?>" class="btn btn-secondary">Edit</a>
                <a href="/planwise/controllers/ExportController.php?action=exportPDF&id=<?php echo $plan['lesson_id']; ?>" class="btn btn-primary">Export PDF</a>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Subject:</strong></div>
                    <div class="col-md-9"><?php echo htmlspecialchars($plan['subject'] ?: 'N/A'); ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3"><strong>Grade Level:</strong></div>
                    <div class="col-md-9"><?php echo htmlspecialchars($plan['grade_level'] ?: 'N/A'); ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3"><strong>Duration:</strong></div>
                    <div class="col-md-9"><?php echo $plan['duration'] ? $plan['duration'] . ' minutes' : 'N/A'; ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3"><strong>Status:</strong></div>
                    <div class="col-md-9"><span class="badge bg-<?php echo $plan['status'] === 'published' ? 'success' : ($plan['status'] === 'draft' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($plan['status']); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Learning Objectives -->
        <?php if (!empty($plan['objectives'])): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Learning Objectives</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($plan['objectives'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Materials -->
        <?php if (!empty($plan['materials'])): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Materials Needed</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($plan['materials'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Procedures -->
        <?php if (!empty($plan['procedures'])): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Procedures</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($plan['procedures'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Assessment -->
        <?php if (!empty($plan['assessment'])): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Assessment</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($plan['assessment'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Attached Files -->
        <?php if (!empty($files)): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Attached Files</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($files as $file): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($file['original_name']); ?>
                                <a href="/planwise/controllers/FileController.php?action=download&id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-primary">Download</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if (!empty($plan['notes'])): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Additional Notes</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($plan['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- QR Code Access -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">QR Code Access</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($qr && !empty($qr['qr_path'])): ?>
                    <!-- Display QR Code -->
                    <div class="mb-3">
                        <img src="/planwise/controllers/QRCodeController.php?action=display&lesson_id=<?php echo $plan['lesson_id']; ?>"
                             alt="QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                    <p class="text-muted mb-3">Scan this QR code to access this lesson plan</p>
                    <!-- Regenerate Button -->
                    <button id="regenerate-qr-btn" onclick="regenerateQRCode(<?php echo $plan['lesson_id']; ?>)" class="btn btn-outline-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Regenerate QR Code
                    </button>
                <?php else: ?>
                    <!-- Generate Button -->
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="currentColor" class="text-muted mb-3" viewBox="0 0 16 16">
                            <path d="M0 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2H0V2zm2-1a1 1 0 0 0-1 1v1h10V2a1 1 0 0 0-1-1H2z"/>
                            <path d="M0 5v-.5A1.5 1.5 0 0 1 1.5 3h11A1.5 1.5 0 0 1 14 4.5V5H0z"/>
                            <path d="M0 7v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H0z"/>
                        </svg>
                        <p class="text-muted">No QR code generated yet</p>
                    </div>
                    <button id="generate-qr-btn" onclick="generateQRCode(<?php echo $plan['lesson_id']; ?>)" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Generate QR Code
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <a href="/planwise/public/index.php?page=teacher/lesson-plans" class="btn btn-secondary">Back to Lesson Plans</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateQRCode(lessonId) {
            const btn = document.getElementById('generate-qr-btn');
            const spinner = btn.querySelector('.spinner-border');

            // Show loading state
            btn.disabled = true;
            spinner.classList.remove('d-none');

            fetch('/planwise/controllers/QRCodeController.php?action=generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lesson_id: lessonId })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message || 'Unauthorized access');
                        } catch (e) {
                            throw new Error('Server returned error: ' + text);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new QR code
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                console.error(error);
            })
            .finally(() => {
                // Hide loading state
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        }

        function regenerateQRCode(lessonId) {
            const btn = document.getElementById('regenerate-qr-btn');
            const spinner = btn.querySelector('.spinner-border');

            // Show loading state
            btn.disabled = true;
            spinner.classList.remove('d-none');

            fetch('/planwise/controllers/QRCodeController.php?action=generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lesson_id: lessonId })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message || 'Unauthorized access');
                        } catch (e) {
                            throw new Error('Server returned error: ' + text);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new QR code
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                console.error(error);
            })
            .finally(() => {
                // Hide loading state
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        }
    </script>
</body>
</html>
