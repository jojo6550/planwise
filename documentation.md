# PlanWise — Technical Documentation
### CS334 Applied Web Programming | PHP 8.2 + Vanilla JS | MVC Architecture

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture & Technology Stack](#2-architecture--technology-stack)
3. [Directory Structure](#3-directory-structure)
4. [Root-Level Files](#4-root-level-files)
5. [public/ — Web Entry Point](#5-public--web-entry-point)
6. [config/ — Configuration Files](#6-config--configuration-files)
7. [classes/ — Core Business Logic](#7-classes--core-business-logic)
8. [controllers/ — Request Handlers](#8-controllers--request-handlers)
9. [helpers/ — Utility Functions](#9-helpers--utility-functions)
10. [views/ — HTML Templates](#10-views--html-templates)
11. [database/ — Schema & Seeds](#11-database--schema--seeds)
12. [tests/ — Unit Test Suite](#12-tests--unit-test-suite)
13. [logs/ — Application Logs](#13-logs--application-logs)
14. [exports/, uploads/, public/qr/ — Generated Assets](#14-exports-uploads-publicqr--generated-assets)
15. [Database Entity-Relationship Summary](#15-database-entity-relationship-summary)
16. [Request Lifecycle](#16-request-lifecycle)
17. [Security Audit](#17-security-audit)
18. [CS334 Module Coverage Map](#18-cs334-module-coverage-map)

---

## 1. Project Overview

**PlanWise** is a web application built for Jamaican teachers to create, manage, export, and share structured lesson plans. Teachers register accounts, compose plans with sections (objectives, materials, procedures, assessment, notes), and can:

- Export plans to **PDF** (inline or download) and **Word** (.docx)
- Share plans instantly via **QR code** — students scan to view the PDF with no login required
- **Email** lesson plans to individual colleagues or multiple recipients
- **Import** lesson plans in bulk from a CSV file (admin only)

Two user roles exist:
- **Admin (role_id = 1)** — manages users, views all lesson plans, exports teacher records, views activity logs
- **Teacher (role_id = 2)** — creates and manages their own lesson plans

The project fulfills all requirements specified in CS334 Applied Web Programming, covering input validation, AJAX, PHP mail, PDF generation, file handling, and activity logging.

---

## 2. Architecture & Technology Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2 |
| Architecture | Custom MVC (no framework) |
| Database | MySQL / MariaDB 10.4 via PDO |
| Frontend | Bootstrap 5.3, Font Awesome 6, Vanilla JS |
| PDF generation | `dompdf/dompdf` (Composer) |
| Word export | `phpoffice/phpword` (Composer) |
| QR codes | `chillerlan/php-qrcode` (Composer) |
| Email | PHP `mail()` / PHPMailer via `phpmailer/phpmailer` |
| Environment | `vlucas/phpdotenv` (.env loading) |
| Testing (PHP) | PHPUnit 9 |
| Testing (JS) | Jest 29 + jest-environment-jsdom |
| Deployment | Docker / Render.com / XAMPP (local) |

**Design pattern:** Single entry point (`public/index.php`) routes all page requests. Controllers are included by the view files themselves (PHP include-based dispatch, not a router object). AJAX endpoints are direct controller files (`controllers/AjaxController.php`, `controllers/ExportController.php`, etc.) called from JavaScript.

---

## 3. Directory Structure

```
planwise/
├── public/                 ← Web root (served by Apache/Nginx)
│   ├── index.php           ← Single entry point + landing page HTML
│   ├── css/
│   │   ├── style.css       ← Global and landing-page styles
│   │   └── admin.css       ← Admin dashboard overrides
│   ├── js/
│   │   └── app.js          ← AJAX live search + XSS-safe helpers
│   └── qr/                 ← Generated QR code PNG images
│
├── classes/                ← Business logic (models / services)
│   ├── Database.php        ← PDO singleton with SQL injection defence
│   ├── Auth.php            ← Session-based authentication
│   ├── User.php            ← User CRUD
│   ├── LessonPlan.php      ← Lesson plan CRUD + stats
│   ├── LessonSection.php   ← Lesson section CRUD
│   ├── ActivityLog.php     ← Security/audit logging
│   ├── Validator.php       ← Server-side input validation
│   ├── BaseController.php  ← Shared controller utilities
│   ├── QRCode.php          ← QR code generation (chillerlan)
│   ├── Mail.php            ← Email sending (mail() / PHPMailer)
│   ├── PasswordReset.php   ← Password reset token management
│   ├── PDFExporter.php     ← PDF generation (dompdf)
│   ├── WordExporter.php    ← Word generation (phpword)
│   ├── DataExporter.php    ← CSV / XLS data export
│   ├── File.php            ← File upload management
│   └── Role.php            ← Role helpers
│
├── controllers/            ← Request handlers (included by views or called via AJAX)
│   ├── AuthController.php          ← Login, logout, register, password reset
│   ├── LessonPlanController.php    ← Lesson plan CRUD + email + import
│   ├── AjaxController.php          ← JSON search endpoints
│   ├── ExportController.php        ← PDF / Word / CSV export
│   ├── DashboardController.php     ← Dashboard data aggregation
│   ├── UserController.php          ← Admin user management
│   ├── FileController.php          ← File upload / delete
│   ├── QRCodeController.php        ← QR code generation endpoint
│   ├── ActivityLogController.php   ← Admin activity log view
│   └── ImportController.php        ← CSV import
│
├── helpers/                ← Procedural utility functions
│   ├── sanitize.php        ← Input sanitization functions
│   └── functions.php       ← CSRF, flash messages, redirect, h(), etc.
│
├── views/                  ← PHP/HTML templates
│   ├── layouts/            ← Shared header/footer/sidebar partials
│   ├── auth/               ← Login, register, forgot/reset password
│   ├── teacher/            ← Teacher dashboard, lesson plan pages, profile
│   ├── admin/              ← Admin dashboard, user management, logs
│   ├── dashboard/          ← Generic dashboard index
│   └── errors/             ← 403, 404, 500, database error pages
│
├── config/
│   ├── database.php        ← DB connection settings (reads .env)
│   └── mail.php            ← Mail driver settings and templates
│
├── database/
│   ├── schema.sql          ← Full database schema + seed data
│   └── seeds/              ← Additional seed files
│
├── tests/                  ← PHPUnit + Jest test suites
├── logs/                   ← activity.log, database.log, security.log
├── exports/                ← Server-saved PDF, Word, CSV exports
├── uploads/                ← User-uploaded profile pictures
├── .env                    ← Environment variables (not committed)
├── composer.json           ← PHP dependencies
├── package.json            ← JS dependencies + test scripts
└── phpunit.xml             ← PHPUnit configuration
```

---

## 4. Root-Level Files

### `.env`
Environment variables for database credentials, app URL, and mail settings. Loaded by `vlucas/phpdotenv` in `public/index.php`. Never committed to version control.

Key variables:
```
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
APP_URL
MAIL_DRIVER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
MAIL_FROM_ADDRESS, MAIL_FROM_NAME
```

### `composer.json`
PHP dependency manifest. Key packages:
- `dompdf/dompdf` — PDF generation
- `phpoffice/phpword` — Word document generation
- `chillerlan/php-qrcode` — QR code PNG generation
- `phpmailer/phpmailer` — SMTP email
- `vlucas/phpdotenv` — `.env` file loading
- `phpunit/phpunit ^9.0` (dev) — unit testing

### `package.json`
JavaScript/Node dependency manifest + test scripts:
```json
{
  "scripts": {
    "test":          "vendor\\bin\\phpunit.bat --testdox && jest",
    "test:php":      "vendor\\bin\\phpunit.bat --testdox",
    "test:js":       "jest",
    "test:watch":    "jest --watch",
    "test:coverage": "vendor\\bin\\phpunit.bat --coverage-text && jest --coverage"
  }
}
```
`npm test` runs **all** 155 tests: 136 PHP (PHPUnit) + 19 JS (Jest).

### `phpunit.xml`
PHPUnit configuration:
- Bootstrap: `tests/bootstrap.php`
- Test suites: `tests/Unit/` + `tests/Integration/`
- Coverage: `classes/` and `controllers/` directories
- `convertWarningsToExceptions="false"` — prevents XAMPP's OpenSSL duplicate-module warning from killing Auth tests

---

## 5. public/ — Web Entry Point

### `public/index.php`
**The application's single front controller.** Every page request passes through this file.

**Responsibilities:**
1. Loads `.env` via phpdotenv (`safeLoad()` skips missing files — for cloud deployment)
2. Registers custom PHP error/exception/fatal-error handlers that show friendly error pages
3. Starts the PHP session with secure settings (`httponly`, `use_strict_mode`, 1-hour lifetime)
4. Reads `?page=` from the query string and dispatches to the correct view file

**Route table (abridged):**
```php
$validPages = [
    'home'                          => null,            // renders landing page HTML below
    'lesson-plan/pdf'               => null,            // ExportController::exportPDF() (QR access)
    'login'                         => 'views/auth/login.php',
    'register'                      => 'views/auth/register.php',
    'teacher/dashboard'             => 'views/teacher/dashboard.php',
    'teacher/lesson-plans'          => 'views/teacher/lesson-plans/index.php',
    'teacher/lesson-plans/create'   => 'views/teacher/lesson-plans/create.php',
    'admin/dashboard'               => 'views/admin/dashboard.php',
    'admin/users'                   => 'views/admin/users/index.php',
    'admin/activity-logs'           => 'views/admin/activity-logs.php',
    // ... 403, 404, 500
];
```

The special `lesson-plan/pdf` route bypasses the view and calls `ExportController::exportPDF()` directly — this enables **unauthenticated QR code access** to lesson plan PDFs.

If `?page=home` or the route is unknown, the landing page HTML embedded at the bottom of `index.php` is rendered directly.

**Landing page sections:**
- Navbar with Login/Register buttons
- Hero section with app description
- Features section (3 cards: Create, Export, QR Share)
- How It Works (3 steps)
- CTA banner
- Footer

### `public/js/app.js`
Client-side JavaScript for the **live lesson plan search** feature.

```js
(function () {
    const searchInput = document.getElementById('lessonPlanSearchInput');
    if (!searchInput) return;

    let debounceTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();

        debounceTimer = setTimeout(async function () {
            const url = '/planwise/controllers/AjaxController.php'
                      + '?action=searchLessonPlans&q=' + encodeURIComponent(q);
            const res  = await fetch(url);
            const data = await res.json();
            // ... update table body with escaped HTML
        }, 300); // 300ms debounce
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }
})();
```

Key design decisions:
- **IIFE** — prevents global namespace pollution
- **300ms debounce** — avoids firing a request on every keystroke
- **`escapeHtml`** uses `createTextNode` — safe against XSS (encodes `&`, `<`, `>`)
- Silent error catch — table retains its current state on network failure

### `public/css/style.css`
Global stylesheet. Defines custom CSS variables and landing page component styles (`.lp-hero`, `.lp-feature-card`, `.lp-step`, `.lp-cta`, `.lp-footer`). Uses Bootstrap 5 for layout.

### `public/css/admin.css`
Admin-specific overrides for the sidebar, table styling, and dashboard stat cards.

---

## 6. config/ — Configuration Files

### `config/database.php`
Returns a PHP array of PDO connection settings, reading credentials from `$_ENV`:

```php
return [
    'driver'   => 'mysql',
    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
    'port'     => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'planwise_db',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // true parameterized queries
        PDO::ATTR_PERSISTENT         => false,
    ]
];
```

`ATTR_EMULATE_PREPARES => false` is critical — it forces the driver to use real prepared statements, providing genuine SQL injection protection.

### `config/mail.php`
Returns mail driver settings and named email templates. Supports two drivers:
- `'mail'` — uses PHP's built-in `mail()` function
- `'smtp'` — uses PHPMailer with SMTP credentials from `.env`

Templates defined: `registration`, `lesson_plan_created`, `password_reset`, `lesson_plan_email`.

---

## 7. classes/ — Core Business Logic

### `Database.php` — PDO Singleton

The foundational data-access layer. Implements the **Singleton** pattern so only one database connection exists per request.

**Key methods:**

| Method | Purpose |
|---|---|
| `getInstance()` | Returns the singleton instance |
| `query($sql, $params)` | Prepare + execute with parameter sanitization |
| `fetch($sql, $params)` | Return single row |
| `fetchAll($sql, $params)` | Return all rows |
| `insert($sql, $params)` | Execute INSERT, return `lastInsertId()` |
| `update($sql, $params)` | Execute UPDATE, return `rowCount()` |
| `delete($sql, $params)` | Execute DELETE, return `rowCount()` |
| `beginTransaction()` | Start DB transaction |
| `commit()` / `rollback()` | Commit/rollback |
| `castInt($val)` / `castString($val)` | Static type-safe cast helpers |

**Defence-in-depth security model:**

```php
public function query($sql, $params = [])
{
    // Layer 1: Pattern-based SQL injection detection
    if (!$this->validateQuery($sql)) {
        throw new Exception("Invalid query detected...");
    }
    // Layer 2: Parameter sanitization (null bytes, trim)
    $params = $this->sanitizeParams($params);
    // Layer 3: PDO prepared statement (primary defence)
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
}
```

Blocked SQL patterns: `UNION SELECT`, `UNION ALL`, `INTO OUTFILE`, `LOAD_FILE`, `EXEC`, `XP_*`, `WAITFOR DELAY`, `BENCHMARK`, SQL comments (`--`, `#`, `/* */`).

**Lazy connection:** The PDO object is created only when the first query runs (not in the constructor).

**Prevents unserialization attack:**
```php
public function __wakeup() {
    throw new Exception("Cannot unserialize singleton");
}
```

---

### `Auth.php` — Session Authentication

Handles login, logout, and session management.

**Constructor:**
```php
public function __construct(User $user = null)
{
    $this->user = $user ?: new User(); // Supports dependency injection for testing
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
```

**`login(string $email, string $password): array`**
1. Validates email format via `filter_var`
2. Fetches user from DB via `User::findByEmail()`
3. Checks user status is `'active'`
4. Verifies password with `password_verify()` (bcrypt)
5. Calls `session_regenerate_id(true)` to prevent session fixation attacks
6. Stores `user_id`, `email`, `first_name`, `last_name`, `role_id`, `authenticated = true`, `login_time` in `$_SESSION`

**`check(): bool`**
- Validates `$_SESSION['authenticated']` is set
- Enforces **30-minute session timeout** — auto-logs out expired sessions
- Refreshes `$_SESSION['last_activity']` on each check

**`hasRole(int $roleId): bool`** — Checks `$_SESSION['role_id']` against the required role.

**`requireAuth()`** / **`requireRole()`** — Redirect helpers called at the top of protected views.

---

### `User.php` — User Model

All user-related database operations.

**Key methods:**

| Method | Description |
|---|---|
| `findByEmail(string $email)` | Returns user row or `null` |
| `findById(int $userId)` | Joins with `roles` table; fallback query on JOIN failure |
| `create(array $data)` | Validates, hashes password with `password_hash()`, inserts |
| `update(int $userId, array $data)` | Updates user fields with email uniqueness check |
| `delete(int $userId)` | Hard-deletes user (CASCADE removes their lesson plans) |
| `updateStatus(int $userId, string $status)` | Whitelist: only `'active'` or `'inactive'` allowed |
| `getAll()` | All users joined with role names |
| `getTeachers()` | Users with `role_id = 2` |
| `getTeachersByIds(array $ids)` | Batch fetch by ID array |
| `getTeachersByPattern(string $pattern)` | Wildcard (`*`, `?`) or regex pattern search |
| `getProfileImage(int $userId, bool $thumbnail)` | Returns thumbnail path with fallback |

**Password security:** `password_hash($password, PASSWORD_DEFAULT)` uses bcrypt with auto-generated salt. Passwords are never stored in plain text.

---

### `LessonPlan.php` — Lesson Plan Model

```php
// CS334 Module 1 + Module 3 - DB manipulation, OOP, Control structures
```

**Key methods:**

| Method | Description |
|---|---|
| `create(array $data)` | Validates title (≥3 chars), duration (positive), then INSERTs |
| `getById(int $id, ?int $userId)` | With `$userId`: owner-only access. Without: `status = 'published'` only (QR code safety) |
| `getByUser(int $userId, ?string $status)` | All plans for a user, optional status filter |
| `update(int $id, array $data, int $userId)` | Authorization check via `getById($id, $userId)` before UPDATE |
| `delete(int $id, int $userId)` | Authorization check before DELETE |
| `getStats(int $userId)` | Returns `{total, published, drafts, archived}` counts |
| `getAll()` | Admin-only: all plans across all users |
| `getRecentActivity(int $userId)` | Plans + sections modified in last 5 days, sorted by date |

**Access control pattern for QR codes:**
```php
if ($userId !== null) {
    $sql .= " AND lp.user_id = :user_id";
} else {
    $sql .= " AND lp.status = 'published'";
}
```
This ensures that QR code scans (unauthenticated, `$userId = null`) can only access **published** lesson plans.

---

### `LessonSection.php` — Lesson Section Model

Manages individual sections within a lesson plan (e.g., Introduction, Main Activity, Conclusion). Sections have `section_type`, `title`, `content`, `duration`, and `order_position` fields. CRUD operations with `lesson_id` foreign key validation.

---

### `Validator.php` — Input Validator

Pipe-chain validation engine used by controllers.

```php
$validator = new Validator();
$valid = $validator->validate($data, [
    'title'    => 'required|min:3|max:255',
    'subject'  => 'max:100',
    'duration' => 'numeric',
    'email'    => 'required|email',
]);
```

**Supported rules:**

| Rule | Behaviour |
|---|---|
| `required` | Fails if empty (but `'0'` passes) |
| `email` | `filter_var(FILTER_VALIDATE_EMAIL)` |
| `min:N` | `strlen($value) >= N` |
| `max:N` | `strlen($value) <= N` |
| `numeric` | `is_numeric($value)` |
| `url` | `filter_var(FILTER_VALIDATE_URL)` |
| `alpha` | `/^[a-zA-Z]+$/` |
| `alphanum` | `/^[a-zA-Z0-9]+$/` |

**Error retrieval methods:** `getAllErrors()`, `getErrors($field)`, `hasErrors($field = null)`, `getFirstError($field)`.

Validation stops at the **first failing rule per field** (fail-fast).

---

### `ActivityLog.php` — Audit Logger

Logs all significant user actions to both the `activity_logs` database table and the `logs/activity.log` file.

**Action constants:**
```php
const ACTION_USER_LOGIN             = 'user_login';
const ACTION_LESSON_PLAN_CREATED    = 'lesson_plan_created';
const ACTION_LESSON_PLAN_EXPORTED_PDF = 'pdf_exported';
const ACTION_QR_CODE_GENERATED      = 'qr_code_generated';
// ... 20+ constants
```

**`log(int $userId, string $action, string $description): bool`**
Inserts into `activity_logs` capturing: `user_id`, `action`, `description`, `ip_address` (proxy-aware), `user_agent`, `created_at`.

**`getAll(array $filters, int $limit, int $offset): array`**
Admin query with filtering by: `user_id`, `action` (whitelist enforced), `date_from`/`date_to` (regex-validated `YYYY-MM-DD`), `search` (via `sanitizeSearchQuery()`). Uses `bindValue(PDO::PARAM_INT)` for LIMIT/OFFSET.

**`getActivityStats(): array`** — Returns total count, today's count, top-5 actions, top-5 users, last-7-days breakdown.

---

### `BaseController.php` — Shared Controller Utilities

Abstract base class for all controllers. Eliminates duplicate code.

```php
abstract class BaseController
{
    protected function sanitize(string $input): string
    {
        return trim(stripslashes($input));
    }

    protected function validateCsrfToken(string $token): bool
    {
        return validate_csrf_token($token); // hash_equals() comparison
    }

    protected function redirectWithError(string $message, string $page): void
    {
        $_SESSION['error'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
```

---

### `QRCode.php` — QR Code Generator

Uses `chillerlan/php-qrcode` to generate PNG QR codes that encode the lesson plan's public PDF URL.

```php
$appUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/planwise/public/', '/');
$qrData  = $appUrl . '/?page=lesson-plan/pdf&id=' . $lessonPlanId . '&inline=1';
$options = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_L]);
$qrCode  = new QRCode($options);
file_put_contents($filePath, $qrCode->render($qrData));
```

The QR code record in `qr_codes` table is **upserted** — if one already exists for the lesson, it replaces the old image file.

---

### `Mail.php` — Email Service

Supports two drivers, selected via `config/mail.php`:

**`mail` driver** — PHP's built-in `mail()` with properly assembled headers (MIME-Version, Content-Type, From, Reply-To, X-Mailer).

**`smtp` driver** — PHPMailer configured from `.env` with SMTP authentication, TLS/SSL encryption.

**Template methods:**
- `sendRegistrationEmail(array $user)` — Welcome email on sign-up
- `sendPasswordResetEmail(array $user, string $token)` — Reset link email
- `sendLessonPlanEmail(...)` — Share a single lesson plan with a colleague
- `sendLessonPlanToMultiple(...)` — Batch send to multiple recipients, returns `success_count` + `failures[]`

---

### `PDFExporter.php`, `WordExporter.php`, `DataExporter.php`

**PDFExporter** — Uses `dompdf/dompdf` to render the lesson plan as an HTML template and output as PDF. Supports two modes:
- **Download** — sets `Content-Disposition: attachment`
- **Inline** — sets `Content-Disposition: inline` (used for QR code scans)

**WordExporter** — Uses `phpoffice/phpword` to generate a `.docx` document with the lesson plan structure.

**DataExporter** — Generates CSV (via PHP's `fputcsv`) or XLS (tab-separated) files for data exports. Used by admin teacher exports.

---

### `PasswordReset.php`

Manages secure password reset tokens stored in the `password_resets` table. Tokens are 64-char hex strings from `random_bytes(32)` with a 1.5-hour expiry. `resetPassword(string $token, string $password)` validates the token, re-hashes the new password, and marks the token as used.

---

### `File.php`, `Role.php`

**File.php** — Handles file attachment upload/retrieval for lesson plans. Manages `uploads/lesson-plans/` directory.

**Role.php** — Helper for role name lookups from the `roles` table.

---

## 8. controllers/ — Request Handlers

All controllers extend `BaseController`. They are either:
1. **Included by view files** (e.g., `views/auth/login.php` includes `AuthController.php` and calls `$controller->login()`)
2. **Called directly via AJAX** (e.g., `AjaxController.php?action=searchLessonPlans`)

### `AuthController.php`

Handles the full authentication lifecycle.

| Method | HTTP | Route | Description |
|---|---|---|---|
| `login()` | POST | `AuthController.php?action=login` | Validates CSRF, sanitizes input, calls `Auth::login()`, redirects by role |
| `logout()` | GET | `AuthController.php?action=logout` | Logs activity, calls `Auth::logout()`, redirects to login |
| `register()` | POST | `AuthController.php?action=register` | Validates all fields + password match + strength (≥8 chars), creates user, sends welcome email |
| `forgotPassword()` | POST | `AuthController.php?action=forgot-password` | Generates reset token, sends email, **always shows generic message** (prevents email enumeration) |
| `resetPassword()` | POST | `AuthController.php?action=reset-password` | Validates token + new password, calls `PasswordReset::resetPassword()` |
| `generateCsrfToken()` | — | Static | Called from views to inject CSRF token into forms |

---

### `LessonPlanController.php`

The most feature-rich controller.

| Method | HTTP | Description |
|---|---|---|
| `create()` | POST | Sanitizes input, runs `Validator` with pipe-chain rules, creates plan, generates QR code, creates sections (foreach), logs activity |
| `update()` | POST | Updates plan + sections |
| `delete()` | POST | Deletes plan with ownership check |
| `get()` | GET/AJAX | Returns JSON for a single plan |
| `getAll()` | GET/AJAX | Returns JSON for all user's plans |
| `emailLesson()` | POST/AJAX | Validates + sends lesson plan email to one recipient |
| `emailLessonMultiple()` | POST/AJAX | Validates recipients array, sends to multiple via `Mail::sendLessonPlanToMultiple()` |
| `importCsv()` | POST | Admin only: reads uploaded CSV, processes each row (while loop), creates plans |

**Validation example (CS334 Module 1):**
```php
$validator = new Validator();
$rules = [
    'title'      => ['required', 'min:3', 'max:255'],
    'subject'    => ['max:100'],
    'grade_level'=> ['max:50'],
    'duration'   => ['numeric'],
    'objectives' => ['max:1000'],
];
if (!$validator->validate($data, $rules)) {
    $errors = $validator->getAllErrors();
    // redirect with errors
}
```

**Control structures demonstrated (CS334 Module 3):**
- `foreach` — iterating section data to create sections
- `while` — logging section count up to 5
- `if/elseif/else` — role-based redirect after login

---

### `AjaxController.php`

JSON-only endpoints. Constructor **requires authentication**; 401 is returned immediately for unauthenticated requests.

```php
// GET /planwise/controllers/AjaxController.php?action=searchLessonPlans&q=algebra
public function searchLessonPlans(): void
{
    $user = $this->auth->user();
    $q    = trim($_GET['q'] ?? '');

    if ($user['role_id'] == 1) {
        // Admin: LIKE query across all lesson plans
    } else {
        // Teacher: PHP-side filter of own plans
    }
    $this->jsonResponse(['success' => true, 'data' => $results, 'count' => count($results)]);
}
```

`searchUsers()` — admin only (role check), filters `getAll()` result in PHP.

---

### `ExportController.php`

| Method | Route | Description |
|---|---|---|
| `exportPDF()` | `?action=exportPDF&id=N` | Streams PDF. `inline=1` param skips auth (QR code access) |
| `savePDF()` | POST `?action=savePDF` | Saves PDF to `exports/pdf/`, returns JSON path |
| `exportWord()` | `?action=exportWord&id=N` | Streams Word document download |
| `saveWord()` | POST `?action=saveWord` | Saves Word doc to `exports/word/`, returns JSON |
| `exportTeachers()` | `?action=exportTeachers` | Admin: export teacher list as CSV or XLS |

The QR code access flow:
```
User scans QR code
  → /public/index.php?page=lesson-plan/pdf&id=N&inline=1
  → ExportController::exportPDF()
  → $userId = null (unauthenticated)
  → PDFExporter fetches plan with LessonPlan::getById(N, null)
  → Only published plans are accessible (status = 'published' guard)
  → PDF rendered inline in browser
```

---

### `UserController.php`

Admin-only user management: create, edit, update, delete, toggle status. Each action:
1. Checks `Auth::hasRole(1)` (admin)
2. Validates CSRF token
3. Sanitizes input
4. Calls `User` model methods
5. Logs to `ActivityLog`

---

### `DashboardController.php`

Aggregates data for admin and teacher dashboards. Calls `User::getActiveUsersCount()`, `LessonPlan::getStats()`, `ActivityLog::getRecentActivity()`, etc.

---

### `ActivityLogController.php`, `FileController.php`, `QRCodeController.php`, `ImportController.php`

Thin controllers delegating to their respective class counterparts. `ImportController` handles the admin CSV bulk-import page view.

---

## 9. helpers/ — Utility Functions

### `helpers/sanitize.php`

Pure procedural sanitization functions loaded globally via `tests/bootstrap.php` and `classes/Database.php`.

| Function | Input | Output | Purpose |
|---|---|---|---|
| `sanitizeString($value)` | any | string | trim + stripslashes + remove null bytes |
| `sanitizeInt($value, $default=0)` | any | int | Cast to int; negative → default |
| `sanitizeFloat($value, $default=0.0)` | any | float | Cast to float; negative → default |
| `sanitizeEmail(string $email)` | string | string | lowercase + trim + `FILTER_SANITIZE_EMAIL` |
| `sanitizeBool($value)` | any | bool | `'true'/'1'/'on'` → `true`; `'false'/'0'/'off'` → `false` |
| `sanitizeArray(array $array, string $type)` | array | array | Applies type-specific sanitizer to each value |
| `sanitizeLikePattern(string $value)` | string | string | Escapes `%` and `_` wildcards for SQL LIKE |
| `sanitizeSearchQuery(string $query)` | string | string | Strips dangerous chars, limits to 255 chars |
| `sanitizeUsername(string $username)` | string | string\|null | `/^[a-zA-Z0-9_]{3,30}$/` or null |
| `sanitizeUrl(string $url)` | string | string\|null | Prepends `http://` if missing, validates, or null |
| `escapeOutput($value)` | any | string | `htmlspecialchars(ENT_QUOTES, UTF-8)` for view output |
| `stripHtml(string $value)` | string | string | `strip_tags(trim($value))` |
| `cleanInput(string $source, array $fields)` | — | array | Batch-sanitize from `$_POST`/`$_GET` by field type map |
| `getInt($key, $source, $min, $max, $default)` | — | int | Range-validated int from request |
| `getString($key, $source, $default)` | — | string | Sanitized string from request |
| `getEmail($key, $source)` | — | string\|null | Validated email from request |

**Design note:** `sanitizeString()` deliberately **does NOT** call `htmlspecialchars()` — HTML escaping is the view layer's responsibility (via `h()` / `escapeOutput()`). Applying it at the storage layer would cause double-encoding.

---

### `helpers/functions.php`

Higher-level application utilities, all procedural.

| Function | Description |
|---|---|
| `activity_log(int $userId, string $action, string $description)` | Convenience wrapper for `ActivityLog::log()` |
| `get_current_user_id()` | Returns `$_SESSION['user_id']` or null |
| `is_logged_in()` | Checks if `get_current_user_id()` is non-null |
| `get_current_user()` | Returns `Auth::user()` or null |
| `h(string $data)` | `htmlspecialchars(ENT_QUOTES, UTF-8)` — the view-layer XSS escape |
| `format_date(string $date, string $format)` | `date()` wrapper, default `'M d, Y'` |
| `format_datetime(string $date, string $format)` | `date()` wrapper, default `'M d, Y h:i A'` |
| `truncate_text(string $text, int $maxLength)` | Appends `...` if text exceeds max |
| `random_string(int $length)` | `bin2hex(random_bytes($length / 2))` |
| `flash(string $key)` | Read + clear a `$_SESSION['flash'][$key]` value |
| `set_flash(string $key, string $message)` | Write a `$_SESSION['flash'][$key]` value |
| `redirect(string $url)` | `header("Location: $url"); exit()` |
| `base_url()` | Protocol + `$_SERVER['HTTP_HOST']` |
| `asset_url(string $path)` | `base_url() . '/' . ltrim($path, '/')` |
| `format_bytes(int $bytes)` | Human-readable file size (B/KB/MB/GB/TB) |
| `is_valid_email(string $email)` | `filter_var(FILTER_VALIDATE_EMAIL)` |
| `generate_csrf_token()` | Creates/returns `$_SESSION['csrf_token']` (32-byte hex) |
| `validate_csrf_token(string $token)` | `hash_equals()` comparison — timing-attack safe |

---

## 10. views/ — HTML Templates

Views are included by `public/index.php`. They bootstrap their own controller at the top of the file.

### `views/layouts/`

| File | Description |
|---|---|
| `header.php` | `<head>` with Bootstrap/FA CDN links |
| `sidebar.php` | Navigation sidebar (role-aware links) |
| `teacher-start.php` | Opens the teacher layout div structure |
| `teacher-end.php` | Closes teacher layout, loads Bootstrap JS |
| `admin-start.php` | Opens admin layout with sidebar |
| `admin-end.php` | Closes admin layout |

### `views/auth/`

| File | Description |
|---|---|
| `login.php` | Login form + CSRF token + error/success flash display |
| `register.php` | Registration form with password confirmation field |
| `forgot-password.php` | Email input for password reset request |
| `reset-password.php` | New password + confirm fields, token passed as hidden input |

### `views/teacher/`

| File | Description |
|---|---|
| `dashboard.php` | Stats cards (total/published/draft/archived), recent activity, quick actions |
| `profile.php` | Profile picture upload + account info edit |
| `lesson-plans/index.php` | Table of user's lesson plans with `#lessonPlanSearchInput` for live search |
| `lesson-plans/create.php` | Full lesson plan form with dynamic section add/remove |
| `lesson-plans/edit.php` | Pre-populated edit form |
| `lesson-plans/view.php` | Read-only view with Export PDF/Word buttons, QR code display, email-share panel |
| `lesson-plans/email-share.php` | Email sharing form component |

### `views/admin/`

| File | Description |
|---|---|
| `dashboard.php` | Platform stats, recent activity, user/plan counts |
| `activity-logs.php` | Filterable activity log table with pagination |
| `import.php` | CSV import form with column mapping instructions |
| `system-settings.php` | System configuration display |
| `users/index.php` | User management table with search |
| `users/create.php` | Admin user creation form |
| `users/edit.php` | User edit form |
| `users/view.php` | User detail view with activity history |

### `views/errors/`

| File | HTTP Code | Description |
|---|---|---|
| `403.php` | 403 | Forbidden — insufficient role |
| `404.php` | 404 | Page not found |
| `500.php` | 500 | Internal server error |
| `database.php` | 503 | Database connection failure |

---

## 11. database/ — Schema & Seeds

### `database/schema.sql`

Full MariaDB dump. Tables:

| Table | Primary Key | Description |
|---|---|---|
| `roles` | `role_id` | Admin (1), Teacher (2) |
| `users` | `user_id` | User accounts with bcrypt hashed passwords |
| `lesson_plans` | `lesson_id` | Core lesson plan data |
| `lesson_sections` | `section_id` | Sections within a lesson plan |
| `activity_logs` | `log_id` | Audit trail of all user actions |
| `qr_codes` | `qr_id` | QR code image paths keyed by `lesson_id` |
| `files` | `file_id` | Attached file uploads per lesson plan |
| `password_resets` | `reset_id` | Secure password reset tokens with expiry |

**Foreign key constraints (all CASCADE):**
- `users.role_id` → `roles.role_id`
- `lesson_plans.user_id` → `users.user_id` (DELETE CASCADE — deletes all user's plans on user delete)
- `lesson_sections.lesson_id` → `lesson_plans.lesson_id` (DELETE CASCADE)
- `activity_logs.user_id` → `users.user_id` (DELETE CASCADE)
- `qr_codes.lesson_id` → `lesson_plans.lesson_id` (DELETE CASCADE)
- `files.lesson_id` → `lesson_plans.lesson_id` (DELETE CASCADE)
- `password_resets.user_id` → `users.user_id` (DELETE CASCADE)

### `database/seeds/`
- `default_roles.sql` — inserts Admin and Teacher roles
- `test_lesson.sql` — sample lesson plan data for development

---

## 12. tests/ — Unit Test Suite

### `tests/bootstrap.php`

PHPUnit bootstrap file. Configures the PHP environment for CLI testing:

```php
ini_set('session.use_cookies',      '0');  // Prevents "headers already sent" from XAMPP's
ini_set('session.use_only_cookies', '0');  // OpenSSL duplicate-module warning
ini_set('session.cache_limiter',    '');
ini_set('error_log', $testLogDir . '/test.log');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../helpers/sanitize.php';
require_once __DIR__ . '/../classes/Database.php';
// ... all class files
```

### `tests/Unit/ValidatorTest.php` — 38 tests

Covers all 8 validation rules, pipe-chained rules, and all error-retrieval methods (`getAllErrors()`, `getErrors()`, `hasErrors()`, `getFirstError()`).

### `tests/Unit/SanitizeTest.php` — 45 tests

Covers every function in `helpers/sanitize.php` including edge cases (null bytes, negative numbers, SQL wildcards, regex patterns, HTML tags).

### `tests/Unit/AuthTest.php` — 19 tests

Uses `createMock(User::class)` for dependency injection. Tests all login failure paths (empty input, bad format, user not found, inactive, wrong password) and success path. Tests `logout()`, `check()` (including 30-min timeout), `user()`, `hasRole()`, `id()`.

### `tests/Unit/UserTest.php` — 13 tests

DB singleton mocked via `ReflectionClass`:
```php
$ref  = new ReflectionClass(Database::class);
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, $this->mockDb);  // Inject mock
```
Tests `create()` (validation failures + success), `findByEmail()`, `findById()`, `updateStatus()`.

### `tests/Unit/LessonPlanTest.php` — 12 tests

Same DB mock pattern. Tests `create()` (missing fields, short title, negative duration, success), `getStats()`, `update()`, `getById()` (public vs. owner access control).

### `tests/js/app.test.js` — 19 tests (Jest)

Uses `jsdom` environment. Tests:
- `escapeHtml()`: empty string, `<script>` tags, `&`, null/undefined coercion
- `getStatusBadge()`: all 3 valid statuses + unknown/empty/undefined fallback
- IIFE integration: debounce timing (fake timers), single fetch after 300ms, URL encoding of search term

**Running the full test suite:**
```bash
# All 155 tests (PHP + JS) from one command:
npm test

# PHP only:
npm run test:php

# JS only:
npm run test:js
```

---

## 13. logs/ — Application Logs

| File | Written By | Content |
|---|---|---|
| `activity.log` | `ActivityLog::logToFile()` | `[timestamp] User:N Action:X IP:Y - description` |
| `database.log` | `Database::logToFile()` | Connection events, query errors |
| `security.log` | `Database::logSecurityEvent()` | Blocked SQL injection patterns, failed queries |
| `test.log` | PHPUnit bootstrap | PHP errors during test runs |

All log files are created automatically if missing. `.gitkeep` preserves the empty directory in version control.

---

## 14. exports/, uploads/, public/qr/ — Generated Assets

| Directory | Contains | Access |
|---|---|---|
| `exports/pdf/` | Server-saved PDF copies of lesson plans | Protected by `.htaccess` (no direct browser access) |
| `exports/word/` | Server-saved Word documents | Protected |
| `exports/data/` | CSV/XLS teacher export files | Protected |
| `uploads/avatars/` | Full-size user profile pictures | Protected |
| `uploads/thumbnails/` | Resized profile thumbnails | Protected |
| `uploads/lesson-plans/` | Lesson plan file attachments | Protected |
| `public/qr/` | QR code PNG images | **Publicly accessible** (required for QR scan display) |

All directories have `.htaccess` Deny-All rules except `public/qr/` which must be web-accessible.

---

## 15. Database Entity-Relationship Summary

```
roles (1) ──────── (*) users (1) ──────── (*) lesson_plans (1) ──── (*) lesson_sections
                    |                          |
                    └── (*) activity_logs      └── (1) qr_codes
                    |                          └── (*) files
                    └── (*) password_resets
```

All relationships use **InnoDB with foreign key constraints and CASCADE deletes**, ensuring referential integrity and automatic cleanup when a parent record is deleted.

---

## 16. Request Lifecycle

### Standard Page Request (e.g., Teacher Dashboard)

```
Browser: GET /planwise/public/index.php?page=teacher/dashboard
  ↓
public/index.php
  ├── Load .env
  ├── Start session
  ├── Validate ?page= against $validPages whitelist
  └── include 'views/teacher/dashboard.php'
        ↓
        views/teacher/dashboard.php
          ├── require_once 'controllers/DashboardController.php'
          ├── $controller = new DashboardController()
          │     └── Auth::requireAuth()  ← redirects to login if not authenticated
          ├── $data = $controller->getTeacherDashboardData()
          │     └── LessonPlan::getStats(), LessonPlan::getRecentActivity()
          └── Render HTML with PHP template + Bootstrap 5
```

### AJAX Search Request

```
Browser: GET /planwise/controllers/AjaxController.php?action=searchLessonPlans&q=algebra
  ↓
AjaxController.php
  ├── session_start()
  ├── new AjaxController() → Auth::check() or 401
  └── searchLessonPlans()
        ├── LessonPlan::getByUser() or DB LIKE query
        └── jsonResponse(['success' => true, 'data' => [...]])
              ↓
        Browser: JavaScript updates table HTML using escapeHtml()
```

### QR Code → PDF Access (Unauthenticated)

```
Student scans QR code
  → /planwise/public/index.php?page=lesson-plan/pdf&id=999&inline=1
  → ExportController::exportPDF()
  → $userId = null  (inline=1 bypasses auth)
  → LessonPlan::getById(999, null)
       → SQL: WHERE lesson_id = 999 AND status = 'published'
  → PDFExporter::generateLessonPlanPDF(999, null, false, true)
  → Content-Type: application/pdf; Content-Disposition: inline
  → Browser renders PDF
```

---

## 17. Security Audit

### Authentication & Session Security

| Control | Implementation |
|---|---|
| Password hashing | `password_hash(PASSWORD_DEFAULT)` — bcrypt |
| Session fixation | `session_regenerate_id(true)` on login |
| Session timeout | 30-minute inactivity timeout in `Auth::check()` |
| Session cookie | `httponly=1`, `secure` on HTTPS, `use_strict_mode=1` |
| CSRF protection | All state-changing forms include hidden `csrf_token`; validated via `hash_equals()` (timing-safe) |

### SQL Injection Prevention

Three-layer defence:
1. **PDO prepared statements** (`ATTR_EMULATE_PREPARES = false`) — primary protection
2. **`Database::validateQuery()`** — blocks dangerous SQL patterns (UNION, INTO OUTFILE, etc.)
3. **`Database::sanitizeParams()`** — removes null bytes from string parameters

### XSS Prevention

- **Storage layer:** `sanitizeString()` does NOT HTML-encode — prevents double-encoding
- **View layer:** `h()` / `escapeOutput()` applies `htmlspecialchars(ENT_QUOTES)` before output
- **JavaScript:** `escapeHtml()` uses `createTextNode` to safely encode content injected via `innerHTML`

### Input Validation

- **Server-side:** `Validator` class enforces all rules on every form submission
- **Format validation:** `filter_var(FILTER_VALIDATE_EMAIL)`, `filter_var(FILTER_VALIDATE_URL)`
- **Type coercion:** All integer IDs cast with `(int)` before use in queries
- **Action whitelisting:** `ActivityLog::getAll()` validates the `action` filter against a hardcoded whitelist

### Access Control

- **Role-based access:** Every admin-only action checks `$this->auth->hasRole(1)`
- **Resource ownership:** Lesson plan update/delete calls `getById($id, $userId)` — SQL-level ownership check
- **Public QR access:** `getById($id, null)` adds `AND status = 'published'` constraint
- **Directory protection:** `.htaccess` Deny-All in `classes/`, `controllers/`, `config/`, `database/`, `logs/`, `exports/`, `uploads/`

### Additional Security Considerations

- **Email enumeration prevention:** `forgotPassword()` always returns the same success message regardless of whether the email exists
- **Debug mode gating:** Reset URLs and debug info only exposed in `$_SESSION['debug']` when `DEBUG_MODE` is defined and `true`
- **Path traversal:** File paths in QR codes use absolute filesystem paths; web output uses relative URL paths
- **CSP / clickjacking:** Not currently implemented (future improvement)

---

## 18. CS334 Module Coverage Map

| CS334 Module | Requirement | Implementation |
|---|---|---|
| **Module 1** | Input Validation (40 marks) | `Validator.php` — 8 rules, pipe-chains, error helpers. Used in `LessonPlanController`, `AuthController`, `UserController` |
| **Module 1** | AJAX (10 marks) | `AjaxController.php` + `public/js/app.js` — live search with 300ms debounce, JSON API, DOM update |
| **Module 1** | Control Structures (18 marks) | `LessonPlanController::create()` — `foreach` (sections), `while` (log count), `if/elseif/else` (role redirect) |
| **Module 2** | Generate PDF Reports (22 marks) | `PDFExporter.php` (dompdf), `ExportController::exportPDF()` — inline + download modes |
| **Module 2** | PHP Mail (10 marks) | `Mail.php` — `mail()` + PHPMailer SMTP; welcome email, password reset, lesson plan sharing (single + multiple) |
| **Module 2** | Use of Files (10 marks) | `DataExporter.php` (CSV/XLS), `FileController.php` (uploads), `LessonPlanController::importCsv()` (CSV read with `fgetcsv`) |
| **Module 3** | DB Manipulation (OOP) | All classes use PDO singleton; `LessonPlan`, `User`, `Auth` demonstrate encapsulation + constructor injection |
| **Module 3** | Activity Logs (10 marks) | `ActivityLog.php` — 20+ action constants, DB + file dual logging, admin filtering/pagination, stats |

---

*Generated 2026-03-30 by Claude Code — PlanWise v1.0*
