<?php
/**
 * Edit Lesson Plan View
 * Form to edit an existing lesson plan
 */

session_start();

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/LessonPlan.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

$auth = new Auth();

// Redirect if not authenticated
if (!$auth->check()) {
    $_SESSION['error'] = 'Please login to edit lesson plans';
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
$plan = $lessonPlan->getById($lessonPlanId, $user['user_id']);

if (!$plan) {
    $_SESSION['error'] = 'Lesson plan not found or unauthorized';
    header('Location: /planwise/public/index.php?page=teacher/lesson-plans');
    exit();
}

$csrfToken = AuthController::generateCsrfToken();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson Plan - PlanWise</title>
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
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Edit Lesson Plan</h2>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="/planwise/controllers/LessonPlanController.php?action=update" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="lesson_plan_id" value="<?php echo $plan['lesson_plan_id']; ?>">

                            <!-- Basic Information -->
                            <h5 class="mb-3">Basic Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="title" class="form-label">Lesson Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($plan['title']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($plan['subject']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="grade_level" class="form-label">Grade Level</label>
                                    <input type="text" class="form-control" id="grade_level" name="grade_level" value="<?php echo htmlspecialchars($plan['grade_level']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $plan['duration']; ?>" min="1">
                                </div>
                            </div>

                            <!-- Learning Objectives -->
                            <h5 class="mb-3 mt-4">Learning Objectives</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="objectives" name="objectives" rows="3"><?php echo htmlspecialchars($plan['objectives']); ?></textarea>
                            </div>

                            <!-- Materials Needed -->
                            <h5 class="mb-3 mt-4">Materials Needed</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="materials" name="materials" rows="3"><?php echo htmlspecialchars($plan['materials']); ?></textarea>
                            </div>

                            <!-- Procedures -->
                            <h5 class="mb-3 mt-4">Procedures</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="procedures" name="procedures" rows="5"><?php echo htmlspecialchars($plan['procedures']); ?></textarea>
                            </div>

                            <!-- Assessment -->
                            <h5 class="mb-3 mt-4">Assessment</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="assessment" name="assessment" rows="3"><?php echo htmlspecialchars($plan['assessment']); ?></textarea>
                            </div>

                            <!-- Notes -->
                            <h5 class="mb-3 mt-4">Additional Notes</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($plan['notes']); ?></textarea>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $plan['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo $plan['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="archived" <?php echo $plan['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/planwise/public/index.php?page=teacher/lesson-plans" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Lesson Plan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
