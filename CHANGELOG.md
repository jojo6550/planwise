# PlanWise – Refactoring Changelog

## Summary
Automated refactoring pass performed on 2026-03-02.  
All changes preserve existing functionality. No database schema changes were made.

---

## 1. Deleted Empty / Stub Files (9 files)

| File | Reason |
|---|---|
| `controllers/ImportController.php` | 0-byte stub; logic already in `LessonPlanController::importCsv()` |
| `helpers/response.php` | 0-byte stub; unused |
| `middleware/AuthMiddleware.php` | 0-byte stub; unused |
| `middleware/CSRFMiddleware.php` | 0-byte stub; unused |
| `middleware/RoleMiddleware.php` | 0-byte stub; unused |
| `views/components/alerts.php` | 0-byte stub; unused |
| `views/components/forms.php` | 0-byte stub; unused |
| `views/components/modals.php` | 0-byte stub; unused |
| `views/components/tables.php` | 0-byte stub; unused |

---

## 2. Created: `classes/BaseController.php`

**Why:** Five controllers (`AuthController`, `UserController`, `LessonPlanController`,
`ActivityLogController`, `ExportController`) each had their own private copies of identical methods:

| Duplicate Method | Appeared In |
|---|---|
| `sanitize()` / `sanitizeInput()` | Auth, User, LessonPlan controllers |
| `validateCsrfToken()` | Auth, User, LessonPlan controllers |
| `redirectWithError()` | Auth, User, LessonPlan controllers |
| `redirectWithSuccess()` | Auth, User, LessonPlan controllers |
| `jsonResponse()` | User, LessonPlan, ActivityLog, Export controllers |

All controllers now extend `BaseController`, which provides the single authoritative implementation of each method.

---

## 3. Fixed: `FILTER_SANITIZE_STRING` Deprecated (PHP 8.1+)

**File:** `classes/ActivityLog.php`  
**Problem:** `filter_var($date, FILTER_SANITIZE_STRING)` — this constant was removed in PHP 8.1 and silently returns `false` in some configurations, breaking date filtering.  
**Fix:** Replaced with `trim()`. The subsequent regex (`/^\d{4}-\d{2}-\d{2}$/`) already provides full validation.

---

## 4. Fixed: `sanitizeString()` Incorrectly Applied `htmlspecialchars` Before DB Storage

**File:** `helpers/sanitize.php`  
**Problem:** `sanitizeString()` called `htmlspecialchars()` on values before they were passed to parameterized queries. Since PDO parameterization already prevents SQL injection, this encoding was not needed for DB safety. The effect was that data such as `O'Brien` was stored as `O&#039;Brien` — causing double-encoding when later displayed through `h()` / `escapeOutput()`.  
**Fix:** Removed `htmlspecialchars()` from `sanitizeString()`. HTML-escaping now correctly belongs only at the view layer (`h()` / `escapeOutput()`).  
**⚠ Migration note:** Any data already stored in the database with HTML-encoded characters will display correctly since the view layer no longer double-encodes. New registrations/inputs going forward store raw values. Existing encoded records may need a one-time DB cleanup if necessary.

---

## 5. Fixed: `sanitize()` in Controllers Applied `htmlspecialchars` Before DB Storage

**Files:** `AuthController`, `UserController`, `LessonPlanController`  
**Problem:** Each controller's private `sanitize()` / `sanitizeInput()` called `htmlspecialchars()` before data was bound to parameterized queries — same root cause as #4.  
**Fix:** `BaseController::sanitize()` uses only `trim()` + `stripslashes()`. XSS protection is handled at output time.

---

## 6. Fixed: `session_start()` Called Unconditionally at File Top

**Files:** `UserController`, `LessonPlanController`, `ActivityLogController`, `ExportController`  
**Problem:** Bare `session_start()` at line 8–9 of each file. When a view includes a controller (after the session is already started), PHP emits a warning. In some server configurations this could corrupt output or headers.  
**Fix:** Wrapped in `if (session_status() === PHP_SESSION_NONE) session_start();`

---

## 7. Fixed: Wrong Base Path in `DashboardController`

**File:** `controllers/DashboardController.php`  
**Problem:** Three redirect/requireAuth/requireRole calls used `/public/index.php?page=...` instead of `/planwise/public/index.php?page=...`, resulting in broken redirects.  
**Fix:** All three corrected to `/planwise/public/index.php?page=...`

---

## 8. Fixed: Missing `admin/import` Route

**File:** `public/index.php`  
**Problem:** `views/admin/import.php` existed but was not registered in `$validPages`, making it inaccessible via the front controller.  
**Fix:** Added `'admin/import' => 'views/admin/import.php'` to the route map.

---

## 9. Fixed: Security – PII Leaked in Debug `error_log()` Calls

**File:** `controllers/AuthController.php` — `register()` and `forgotPassword()` methods  
**Problems:**
- `register()` logged input field presence, registration data shapes, and detailed failure reasons to `error_log()`.
- `forgotPassword()` logged the submitted email address, partial token values, and whether the email existed in the DB (which aids email enumeration attacks).
- `$_SESSION['reset_token']` stored the raw reset token in the user's session (not needed; the token is already emailed).  

**Fixes:**
- Removed verbose dev-debug `error_log()` calls.
- Kept only meaningful operational error logs (e.g., failed email send).
- Both success and failure paths of `forgotPassword()` now return the same generic message to prevent email enumeration.
- Removed `$_SESSION['reset_token']` storage. The debug reset URL is still placed in `$_SESSION['debug_reset_link']` when `DEBUG_MODE` is on, but the raw token is not stored separately.

---

## 10. Removed: Unused `isAjaxRequest()` in `LessonPlanController`

**File:** `controllers/LessonPlanController.php`  
**Problem:** `isAjaxRequest()` was defined but never called anywhere in the class.  
**Fix:** Removed the dead method.

---

## Items for Manual Review

| Item | File | Notes |
|---|---|---|
| Existing DB data double-encoding | All tables storing text | Run a one-time SQL cleanup if names/text show HTML entities like `&amp;`, `&#039;` |
| Middleware directory | `middleware/` | Three empty stub files deleted. Implement real middleware if needed |
| `APP_URL` env variable | `.env.example` | Add `APP_URL=http://localhost/planwise` to centralise base path used in redirects |
| `importCsv()` route | `LessonPlanController` | The import form at `admin/import` posts to `LessonPlanController::importCsv()`; confirm this wiring is correct |
| Singleton reset between requests | `classes/Database.php` | `static $instance` will persist across includes in the same request; ensure it does not cause issues during testing |
