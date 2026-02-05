<?php
/**
 * Create Lesson Plan View
 * Form to create a new lesson plan
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

$auth = new Auth();

// Redirect if not authenticated
if (!$auth->check()) {
    $_SESSION['error'] = 'Please login to create lesson plans';
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

$user = $auth->user();
$csrfToken = AuthController::generateCsrfToken();

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$oldInput = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['errors'] ?? [];

unset($_SESSION['error'], $_SESSION['success'], $_SESSION['errors']);

$sections = $oldInput['sections'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lesson Plan - PlanWise</title>
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
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Create New Lesson Plan</h2>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form id="lesson-plan-form" action="/planwise/controllers/LessonPlanController.php?action=create" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                            <!-- Basic Information -->
                            <h5 class="mb-3">Basic Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="title" class="form-label">Lesson Title *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo htmlspecialchars($oldInput['title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['title'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['title'][0] ?? $errors['title']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($oldInput['subject'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="grade_level" class="form-label">Grade Level</label>
                                    <input type="text" class="form-control" id="grade_level" name="grade_level" value="<?php echo htmlspecialchars($oldInput['grade_level'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="1" value="<?php echo htmlspecialchars($oldInput['duration'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Learning Objectives -->
                            <h5 class="mb-3 mt-4">Learning Objectives</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="objectives" name="objectives" rows="3"><?php echo htmlspecialchars($oldInput['objectives'] ?? ''); ?></textarea>
                            </div>

                            <!-- Materials Needed -->
                            <h5 class="mb-3 mt-4">Materials Needed</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="materials" name="materials" rows="3"><?php echo htmlspecialchars($oldInput['materials'] ?? ''); ?></textarea>
                            </div>

                            <!-- Procedures -->
                            <h5 class="mb-3 mt-4">Procedures</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="procedures" name="procedures" rows="5"><?php echo htmlspecialchars($oldInput['procedures'] ?? ''); ?></textarea>
                            </div>

                            <!-- Assessment -->
                            <h5 class="mb-3 mt-4">Assessment</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="assessment" name="assessment" rows="3"><?php echo htmlspecialchars($oldInput['assessment'] ?? ''); ?></textarea>
                            </div>

                            <!-- Notes -->
                            <h5 class="mb-3 mt-4">Additional Notes</h5>
                            <div class="mb-3">
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($oldInput['notes'] ?? ''); ?></textarea>
                            </div>

                            <!-- Lesson Sections -->
                            <h5 class="mb-3 mt-4">Lesson Sections</h5>
                            <div class="mb-3">
                                <button type="submit" name="add_section" value="1" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-lg"></i> Add Section
                                </button>
                            </div>
                            <div id="sections-container">
                                <?php foreach ($sections as $index => $section): ?>
                                    <div class="section-item card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">Section Type</label>
                                                    <select class="form-select section-type" name="sections[<?php echo $index; ?>][section_type]">
                                                        <option value="introduction" <?php echo ($section['section_type'] ?? '') === 'introduction' ? 'selected' : ''; ?>>Introduction</option>
                                                        <option value="main_activity" <?php echo ($section['section_type'] ?? '') === 'main_activity' ? 'selected' : ''; ?>>Main Activity</option>
                                                        <option value="conclusion" <?php echo ($section['section_type'] ?? '') === 'conclusion' ? 'selected' : ''; ?>>Conclusion</option>
                                                        <option value="assessment" <?php echo ($section['section_type'] ?? '') === 'assessment' ? 'selected' : ''; ?>>Assessment</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" class="form-control section-title" name="sections[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($section['title'] ?? ''); ?>" placeholder="Section title">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Duration (min)</label>
                                                    <input type="number" class="form-control section-duration" name="sections[<?php echo $index; ?>][duration]" min="0" value="<?php echo htmlspecialchars($section['duration'] ?? ''); ?>">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="submit" name="remove_section" value="<?php echo $index; ?>" class="btn btn-danger btn-sm">&times;</button>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">Content</label>
                                                <textarea class="form-control section-content" name="sections[<?php echo $index; ?>][content]" rows="3" placeholder="Section content"><?php echo htmlspecialchars($section['content'] ?? ''); ?></textarea>
                                            </div>
                                            <input type="hidden" name="sections[<?php echo $index; ?>][order_position]" value="<?php echo $index; ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" selected>Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/planwise/public/index.php?page=teacher/lesson-plans" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create Lesson Plan</button>
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
