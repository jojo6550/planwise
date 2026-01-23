<?php
/**
 * Lesson Plans List View
 * Displays all lesson plans for the teacher
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../classes/Database.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/LessonPlan.php';

$auth = new Auth();

// Redirect if not authenticated
if (!$auth->check()) {
    $_SESSION['error'] = 'Please login to access lesson plans';
    header('Location: /planwise/public/index.php?page=login');
    exit();
}

$user = $auth->user();
$lessonPlan = new LessonPlan();
$lessonPlans = $lessonPlan->getByUser($user['user_id']);
$stats = $lessonPlan->getStats($user['user_id']);
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lesson Plans - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/planwise/public/css/style.css">
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
                    <li class="nav-item"><a class="nav-link active" href="/planwise/public/index.php?page=teacher/lesson-plans">Lesson Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="/planwise/public/index.php?page=teacher/profile">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="/planwise/controllers/AuthController.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alerts -->
        <?php if (!empty($success ?? '')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error ?? '')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>My Lesson Plans</h2>
                <p class="text-muted">Manage your lesson plans</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/planwise/public/index.php?page=teacher/lesson-plans/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create New Plan
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Total Plans</h6>
                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Published</h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['published']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Drafts</h6>
                        <h3 class="mb-0 text-warning"><?php echo $stats['drafts']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Archived</h6>
                        <h3 class="mb-0 text-secondary"><?php echo $stats['archived']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lesson Plans Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php if (empty($lessonPlans ?? [])): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No lesson plans yet. Create your first lesson plan!</p>
                        <a href="/planwise/public/index.php?page=teacher/lesson-plans/create" class="btn btn-primary">Create Lesson Plan</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Grade Level</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($lessonPlans ?? []) as $plan): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($plan['title'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($plan['subject'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($plan['grade_level'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'draft' => 'badge bg-warning',
                                                'published' => 'badge bg-success',
                                                'archived' => 'badge bg-secondary'
                                            ];
                                            $class = $statusClass[$plan['status'] ?? ''] ?? 'badge bg-secondary';
                                            ?>
                                            <span class="<?php echo $class; ?>"><?php echo ucfirst($plan['status'] ?? ''); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($plan['updated_at'] ?? '')); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/planwise/public/index.php?page=teacher/lesson-plans/view&id=<?php echo $plan['lesson_plan_id'] ?? 0; ?>" class="btn btn-outline-primary">View</a>
                                                <a href="/planwise/public/index.php?page=teacher/lesson-plans/edit&id=<?php echo $plan['lesson_plan_id'] ?? 0; ?>" class="btn btn-outline-secondary">Edit</a>
                                                <a href="/planwise/controllers/ExportController.php?action=exportPDF&id=<?php echo $plan['lesson_plan_id'] ?? 0; ?>" class="btn btn-outline-info">PDF</a>
                                                <button onclick="deletePlan(<?php echo $plan['lesson_plan_id'] ?? 0; ?>)" class="btn btn-outline-danger">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deletePlan(id) {
            if (!confirm('Are you sure you want to delete this lesson plan? This action cannot be undone.')) {
                return;
            }

            fetch('/planwise/controllers/LessonPlanController.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lesson_plan_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while deleting the lesson plan');
                console.error(error);
            });
        }
    </script>
</body>
</html>
