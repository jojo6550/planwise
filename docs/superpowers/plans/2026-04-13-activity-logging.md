# Activity Logging Improvements — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close six logging gaps so every meaningful user action appears in the admin activity-log view.

**Architecture:** Surgical additions only — add `ActivityLog::log()` calls at each action's completion point, update the DB column to allow NULL for unauthenticated events (failed logins), and fix a pre-existing merge conflict in FileController that was already hiding file-action logging.

**Tech Stack:** PHP 8+, MySQL/MariaDB (XAMPP), PHPUnit 10 (existing test suite at `tests/Unit/`), Bootstrap 5 badges in admin view.

---

## Files Modified

| File | Change |
|---|---|
| `classes/ActivityLog.php` | Nullable `$userId`, 4 new constants, LEFT JOINs, whitelist update |
| `tests/Unit/ActivityLogTest.php` | New — unit tests for nullable user_id and constants |
| `tests/bootstrap.php` | Load ActivityLog.php |
| `controllers/AuthController.php` | Log failed login |
| `views/teacher/profile.php` | Log profile update |
| `views/teacher/lesson-plans/view.php` | Log lesson plan view |
| `controllers/FileController.php` | Restore full file (fix merge conflict) |
| `views/admin/activity-logs.php` | Add 4 new actions to badge + label maps |

---

### Task 1: DB migration — allow NULL user_id in activity_logs

**Files:**
- Modify: `database/planwise_db(2).sql` (schema reference only — run SQL directly in phpMyAdmin)

- [ ] **Step 1: Run migration in phpMyAdmin**

Open phpMyAdmin → select `planwise_db` → SQL tab, run:

```sql
ALTER TABLE `activity_logs` MODIFY `user_id` INT(11) NULL DEFAULT NULL;
```

Expected: "1 row affected" — no error. The FK constraint `activity_logs_ibfk_1` remains valid; MySQL allows NULL FK values.

- [ ] **Step 2: Verify**

```sql
DESCRIBE `activity_logs`;
```

The `user_id` row should show `NULL` in the Null column (was `NO`, now `YES`).

- [ ] **Step 3: Commit note**

No PHP file changed in this task. Add a SQL comment to `database/planwise_db(2).sql` noting the change, then commit:

In `database/planwise_db(2).sql`, find:
```sql
  `user_id` int(11) NOT NULL,
```
Change the line for `activity_logs` table (around line 32) to:
```sql
  `user_id` int(11) DEFAULT NULL,
```

```bash
git add database/"planwise_db(2).sql"
git commit -m "feat: allow NULL user_id in activity_logs for unauthenticated events"
```

---

### Task 2: Update ActivityLog class

**Files:**
- Modify: `classes/ActivityLog.php`

- [ ] **Step 1: Make `$userId` nullable in `log()`**

In `classes/ActivityLog.php`, change line 54:
```php
public function log(int $userId, string $action, string $description = ''): bool
```
to:
```php
public function log(?int $userId, string $action, string $description = ''): bool
```

- [ ] **Step 2: Add four missing action constants**

After line 36 (`public const ACTION_FILE_DELETED = 'file_deleted';`), add:

```php
    public const ACTION_LOGIN_FAILED                 = 'login_failed';
    public const ACTION_PROFILE_UPDATED              = 'profile_updated';
    public const ACTION_LESSON_PLAN_EMAILED          = 'lesson_plan_emailed';
    public const ACTION_LESSON_PLAN_EMAILED_MULTIPLE = 'lesson_plan_emailed_multiple';
```

- [ ] **Step 3: Change INNER JOIN → LEFT JOIN in `getAll()`**

In `getAll()` around line 156, change:
```php
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";
```
to:
```php
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    {$whereClause}
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset";
```

- [ ] **Step 4: Change INNER JOIN → LEFT JOIN in `getRecentActivity()`**

Around line 266, change:
```php
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.user_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";
```
to:
```php
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";
```

- [ ] **Step 5: Add new actions to `getAll()` whitelist**

In `getAll()` around line 109–116, find the `$allowedActions` array and add the four new values:

```php
                $allowedActions = [
                    'user_login', 'user_logout', 'user_registered', 'user_created',
                    'user_updated', 'user_deleted', 'user_status_updated',
                    'password_reset_completed', 'lesson_plan_created', 'lesson_plan_updated',
                    'lesson_plan_deleted', 'lesson_plan_viewed', 'pdf_exported',
                    'word_exported', 'pdf_saved', 'word_saved', 'lesson_plan_imported',
                    'qr_code_generated', 'file_uploaded', 'file_downloaded', 'file_deleted',
                    'login_failed', 'profile_updated',
                    'lesson_plan_emailed', 'lesson_plan_emailed_multiple',
                ];
```

- [ ] **Step 6: Commit**

```bash
git add classes/ActivityLog.php
git commit -m "feat: nullable user_id, new action constants, LEFT JOINs in ActivityLog"
```

---

### Task 3: Unit tests for ActivityLog

**Files:**
- Modify: `tests/bootstrap.php`
- Create: `tests/Unit/ActivityLogTest.php`

- [ ] **Step 1: Add ActivityLog to bootstrap**

In `tests/bootstrap.php`, after the existing `require_once` lines, add:

```php
require_once __DIR__ . '/../classes/ActivityLog.php';
```

- [ ] **Step 2: Create test file**

Create `tests/Unit/ActivityLogTest.php`:

```php
<?php
/**
 * ActivityLogTest
 * Unit tests for the ActivityLog class.
 * Database singleton is replaced via Reflection so no real DB is needed.
 */

use PHPUnit\Framework\TestCase;

class ActivityLogTest extends TestCase
{
    private function makeActivityLog(): ActivityLog
    {
        $dbMock = $this->createMock(Database::class);
        $dbMock->method('insert')->willReturn(1);

        $log = new ActivityLog();

        // Inject mock via Reflection (same pattern as rest of suite)
        $ref = new ReflectionClass($log);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue($log, $dbMock);

        return $log;
    }

    public function testLogReturnsTrueForAuthenticatedUser(): void
    {
        $log = $this->makeActivityLog();
        $result = $log->log(5, ActivityLog::ACTION_USER_LOGIN, 'User logged in: test@example.com');
        $this->assertTrue($result);
    }

    public function testLogAcceptsNullUserIdForFailedLogin(): void
    {
        $log = $this->makeActivityLog();
        // Must not throw a TypeError — null is valid for unauthenticated events
        $result = $log->log(null, ActivityLog::ACTION_LOGIN_FAILED, 'Failed login attempt for email: bad@example.com');
        $this->assertTrue($result);
    }

    public function testLoginFailedConstantValue(): void
    {
        $this->assertSame('login_failed', ActivityLog::ACTION_LOGIN_FAILED);
    }

    public function testProfileUpdatedConstantValue(): void
    {
        $this->assertSame('profile_updated', ActivityLog::ACTION_PROFILE_UPDATED);
    }

    public function testLessonPlanEmailedConstantValue(): void
    {
        $this->assertSame('lesson_plan_emailed', ActivityLog::ACTION_LESSON_PLAN_EMAILED);
    }

    public function testLessonPlanEmailedMultipleConstantValue(): void
    {
        $this->assertSame('lesson_plan_emailed_multiple', ActivityLog::ACTION_LESSON_PLAN_EMAILED_MULTIPLE);
    }
}
```

- [ ] **Step 3: Run tests**

```bash
cd C:/xampp/htdocs/planwise
vendor/bin/phpunit tests/Unit/ActivityLogTest.php --testdox
```

Expected output:
```
ActivityLog (ActivityLog)
 ✔ Log returns true for authenticated user
 ✔ Log accepts null user id for failed login
 ✔ Login failed constant value
 ✔ Profile updated constant value
 ✔ Lesson plan emailed constant value
 ✔ Lesson plan emailed multiple constant value
```

- [ ] **Step 4: Run full suite to confirm no regressions**

```bash
vendor/bin/phpunit tests/ --testdox
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add tests/bootstrap.php tests/Unit/ActivityLogTest.php
git commit -m "test: add ActivityLogTest covering nullable user_id and new constants"
```

---

### Task 4: Log failed login in AuthController

**Files:**
- Modify: `controllers/AuthController.php`

- [ ] **Step 1: Add log call in the login failure branch**

In `controllers/AuthController.php`, find the `login()` method failure block (around line 90):

```php
        } else {
            // Login failed - redirect back with error and debug info
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }
            $this->redirectWithError($result['message'], 'login');
        }
```

Change to:

```php
        } else {
            // Login failed - redirect back with error and debug info
            if (defined('DEBUG_MODE') && DEBUG_MODE && isset($result['debug'])) {
                $_SESSION['debug'] = $result['debug'];
            }
            $this->activityLog->log(
                null,
                ActivityLog::ACTION_LOGIN_FAILED,
                "Failed login attempt for email: {$email}"
            );
            $this->redirectWithError($result['message'], 'login');
        }
```

- [ ] **Step 2: Verify manually**

1. Open the app login page
2. Submit with a wrong password
3. Check phpMyAdmin: `SELECT * FROM activity_logs WHERE action = 'login_failed' ORDER BY created_at DESC LIMIT 1;`
4. Expected: one row with `user_id = NULL`, `action = 'login_failed'`

- [ ] **Step 3: Commit**

```bash
git add controllers/AuthController.php
git commit -m "feat: log failed login attempts with null user_id"
```

---

### Task 5: Log profile updates in teacher profile view

**Files:**
- Modify: `views/teacher/profile.php`

- [ ] **Step 1: Add log call after successful update**

In `views/teacher/profile.php`, find the successful update block (around line 87):

```php
            if ($result['success']) {
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name']  = $lastName;
                $_SESSION['email']      = $email;
                $_SESSION['success']    = 'Profile updated successfully';
            } else {
```

Change to:

```php
            if ($result['success']) {
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name']  = $lastName;
                $_SESSION['email']      = $email;
                $_SESSION['success']    = 'Profile updated successfully';
                require_once __DIR__ . '/../../classes/ActivityLog.php';
                $activityLog = new ActivityLog();
                $activityLog->log(
                    $user['user_id'],
                    ActivityLog::ACTION_PROFILE_UPDATED,
                    "User updated their profile (User ID: {$user['user_id']})"
                );
            } else {
```

- [ ] **Step 2: Verify manually**

1. Log in as a teacher, go to Profile, save any change
2. Check: `SELECT * FROM activity_logs WHERE action = 'profile_updated' ORDER BY created_at DESC LIMIT 1;`
3. Expected: one row with the teacher's `user_id`

- [ ] **Step 3: Commit**

```bash
git add views/teacher/profile.php
git commit -m "feat: log teacher profile updates"
```

---

### Task 6: Log lesson plan views

**Files:**
- Modify: `views/teacher/lesson-plans/view.php`

- [ ] **Step 1: Add log call after plan is confirmed loaded**

In `views/teacher/lesson-plans/view.php`, after line 45 (after `if (!$plan) { ... }` block ends), add:

```php
require_once __DIR__ . '/../../../classes/ActivityLog.php';
$activityLog = new ActivityLog();
$activityLog->log(
    $user['user_id'],
    ActivityLog::ACTION_LESSON_PLAN_VIEWED,
    "Viewed lesson plan: '{$plan['title']}' (ID: {$lessonPlanId})"
);
```

The insertion point looks like this in context:

```php
$sections = $lessonSection->getByLessonPlan($lessonPlanId);   // existing line 47
$files    = $fileHandler->getByLessonPlan($lessonPlanId);      // existing line 48
```

Insert the block between lines 45 and 47:

```php
$plan = $lessonPlan->getById($lessonPlanId, $user['user_id']); // existing line 40
if (!$plan) {                                                   // existing lines 41-44
    $_SESSION['error'] = 'Lesson plan not found or unauthorized';
    header('Location: ' . BASE_URL . '/index.php?page=teacher/lesson-plans');
    exit();
}

// ← INSERT HERE
require_once __DIR__ . '/../../../classes/ActivityLog.php';
$activityLog = new ActivityLog();
$activityLog->log(
    $user['user_id'],
    ActivityLog::ACTION_LESSON_PLAN_VIEWED,
    "Viewed lesson plan: '{$plan['title']}' (ID: {$lessonPlanId})"
);

$sections = $lessonSection->getByLessonPlan($lessonPlanId);   // existing line 47
```

- [ ] **Step 2: Verify manually**

1. Log in as a teacher, open any lesson plan
2. Check: `SELECT * FROM activity_logs WHERE action = 'lesson_plan_viewed' ORDER BY created_at DESC LIMIT 1;`
3. Expected: one row with the plan title in the description

- [ ] **Step 3: Commit**

```bash
git add views/teacher/lesson-plans/view.php
git commit -m "feat: log lesson plan views"
```

---

### Task 7: Fix FileController merge conflict

**Files:**
- Modify: `controllers/FileController.php`

> The current file is truncated — it is missing its class declaration, constructor, `upload()`, `delete()`, and `download()` methods. The `import()` method and related helpers present in the broken file are already handled by `ImportController.php` and are not needed here.

- [ ] **Step 1: Replace the entire file**

Overwrite `controllers/FileController.php` with the following complete content:

```php
<?php
/**
 * FileController
 * Handles file upload, download, and delete operations.
 * CS334 Module 2 - Use of Files (10), Upload images (10), Built-in PHP functions (5)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/File.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class FileController
{
    private $auth;
    private $fileHandler;
    private $activityLog;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->fileHandler = new File();
        $this->activityLog = new ActivityLog();

        if (!$this->auth->check()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
        }
    }

    /**
     * Upload a file attachment to a lesson plan.
     */
    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->jsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
            return;
        }

        $userId       = $this->auth->id();
        $lessonPlanId = isset($_POST['lesson_plan_id']) && $_POST['lesson_plan_id'] !== ''
            ? (int)$_POST['lesson_plan_id']
            : null;

        $result = $this->fileHandler->upload($_FILES['file'], $userId, $lessonPlanId);

        if ($result['success']) {
            $this->activityLog->log(
                $userId,
                ActivityLog::ACTION_FILE_UPLOADED,
                "Uploaded file: '{$result['original_name']}'"
                    . ($lessonPlanId ? " to lesson plan ID: {$lessonPlanId}" : '')
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Delete a file.
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $fileId = (int)($input['file_id'] ?? 0);
        $userId = $this->auth->id();

        if ($fileId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid file ID'], 400);
            return;
        }

        // Fetch name before deletion for the log description
        $file   = $this->fileHandler->getById($fileId);
        $result = $this->fileHandler->delete($fileId, $userId);

        if ($result['success'] && $file) {
            $this->activityLog->log(
                $userId,
                ActivityLog::ACTION_FILE_DELETED,
                "Deleted file: '{$file['original_name']}' (File ID: {$fileId})"
            );
        }

        $this->jsonResponse($result);
    }

    /**
     * Stream a file download.
     */
    public function download(): void
    {
        $fileId = (int)($_GET['id'] ?? 0);

        if ($fileId <= 0) {
            http_response_code(400);
            echo 'Invalid file ID';
            exit();
        }

        $file = $this->fileHandler->getById($fileId);

        if (!$file || !file_exists($file['file_path'])) {
            http_response_code(404);
            echo 'File not found';
            exit();
        }

        $this->activityLog->log(
            $this->auth->id(),
            ActivityLog::ACTION_FILE_DOWNLOADED,
            "Downloaded file: '{$file['original_name']}' (File ID: {$fileId})"
        );

        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        readfile($file['file_path']);
        exit();
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

// Handle direct requests
if (basename($_SERVER['PHP_SELF']) === 'FileController.php') {
    $controller = new FileController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'upload':
            $controller->upload();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'download':
            $controller->download();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
```

- [ ] **Step 2: Verify no syntax errors**

```bash
php -l C:/xampp/htdocs/planwise/controllers/FileController.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Verify manually**

1. Log in as teacher, open a lesson plan, upload an attachment
2. Check: `SELECT * FROM activity_logs WHERE action = 'file_uploaded' ORDER BY created_at DESC LIMIT 1;`
3. Download that file, check: `SELECT * FROM activity_logs WHERE action = 'file_downloaded' ORDER BY created_at DESC LIMIT 1;`
4. Delete that file, check: `SELECT * FROM activity_logs WHERE action = 'file_deleted' ORDER BY created_at DESC LIMIT 1;`

- [ ] **Step 4: Commit**

```bash
git add controllers/FileController.php
git commit -m "fix: restore FileController (resolve merge conflict, file action logging intact)"
```

---

### Task 8: Update admin activity-logs view

**Files:**
- Modify: `views/admin/activity-logs.php`

- [ ] **Step 1: Add entries to `logsActionBadge()` map**

In `views/admin/activity-logs.php`, inside the `$map` array in `logsActionBadge()` (after line 90, before the closing `]`), add:

```php
        'login_failed'                   => ['Login Failed',        'bg-danger-subtle text-danger'],
        'profile_updated'                => ['Profile Updated',     'bg-info-subtle text-info'],
        'lesson_plan_emailed'            => ['Lesson Emailed',      'bg-secondary-subtle text-secondary'],
        'lesson_plan_emailed_multiple'   => ['Lesson Emailed×',     'bg-secondary-subtle text-secondary'],
        'lesson_plan_sections_created'   => ['Sections Created',    'bg-success-subtle text-success'],
```

- [ ] **Step 2: Add entries to `logsActionText()` map**

In the `$labels` array in `logsActionText()` (after line 119, before the closing `]`), add:

```php
        'login_failed'                   => 'Failed login attempt',
        'profile_updated'                => 'Teacher profile updated',
        'lesson_plan_emailed'            => 'Lesson plan emailed',
        'lesson_plan_emailed_multiple'   => 'Lesson plan emailed to multiple recipients',
        'lesson_plan_sections_created'   => 'Lesson plan sections created',
```

- [ ] **Step 3: Verify manually**

1. Open admin → Activity Logs
2. Confirm `login_failed` rows show a red "Login Failed" badge
3. Confirm `profile_updated` rows show a teal "Profile Updated" badge
4. Trigger a failed login if no rows exist yet (go to login page, enter wrong password)

- [ ] **Step 4: Commit**

```bash
git add views/admin/activity-logs.php
git commit -m "feat: add login_failed, profile_updated, lesson emailed badges to activity-logs view"
```

---

### Task 9: Final regression check

- [ ] **Step 1: Run the full test suite**

```bash
cd C:/xampp/htdocs/planwise
vendor/bin/phpunit tests/ --testdox
```

Expected: all tests pass (no failures, no errors).

- [ ] **Step 2: Smoke-test the six logging gaps end-to-end**

| Action | Where | Expected log row |
|---|---|---|
| Wrong password on login | `/index.php?page=login` | `login_failed`, user_id NULL |
| Save teacher profile | `/index.php?page=teacher/profile` | `profile_updated` |
| Open a lesson plan | `/index.php?page=teacher/lesson-plans/view&id=N` | `lesson_plan_viewed` |
| Upload a file attachment | lesson plan view page | `file_uploaded` |
| Download a file attachment | lesson plan view page | `file_downloaded` |
| Delete a file attachment | lesson plan view page | `file_deleted` |

- [ ] **Step 3: Check admin log view renders NULL-user rows cleanly**

Go to Admin → Activity Logs. Rows where `user_id` is NULL (failed logins) should display without a PHP warning — `u.first_name` / `u.last_name` / `u.email` will be NULL from the LEFT JOIN, so check that the view handles empty name gracefully (it already uses `htmlspecialchars()` on those fields, so NULL renders as empty string — no change needed).
