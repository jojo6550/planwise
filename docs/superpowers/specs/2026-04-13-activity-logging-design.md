# Activity Logging Improvements — Design Spec
**Date:** 2026-04-13  
**Status:** Approved

## Problem

Several user actions produce no activity log entry despite the `ActivityLog` class and DB table being fully in place. The `ACTION_LESSON_PLAN_VIEWED`, `ACTION_FILE_UPLOADED`, `ACTION_FILE_DOWNLOADED`, and `ACTION_FILE_DELETED` constants exist but are never called. Failed logins and profile updates are also untracked.

## Goals

Log every meaningful user action so the admin activity-log view gives a complete audit trail. Specifically, close these gaps:

1. Failed login attempts
2. Teacher profile updates
3. Lesson plan views (every view, per user decision)
4. File upload, download, and delete
5. Add missing action constants

## Out of Scope

- Admin system-settings page views (read-only info page, low audit value)
- Refactoring model classes or adding middleware
- Changes to the activity-log UI beyond updating the whitelist/badge map

## Approach

**Direct surgical additions (Option A).** Add `ActivityLog::log()` calls at the exact location where each action completes successfully. This matches the existing pattern used across all controllers and keeps each change isolated to the file that owns the action.

## Changes

### 1. `classes/ActivityLog.php`

Add four missing action constants:

```php
public const ACTION_LOGIN_FAILED              = 'login_failed';
public const ACTION_PROFILE_UPDATED          = 'profile_updated';
public const ACTION_LESSON_PLAN_EMAILED      = 'lesson_plan_emailed';
public const ACTION_LESSON_PLAN_EMAILED_MULTIPLE = 'lesson_plan_emailed_multiple';
```

(`lesson_plan_emailed` and `lesson_plan_emailed_multiple` are already being logged as raw strings — this just adds the constants for consistency.)

### 2. `controllers/AuthController.php` — Failed login

In the `login()` failure branch, after the `$result['success']` check fails, add:

```php
$this->activityLog->log(
    null,  // no authenticated user
    ActivityLog::ACTION_LOGIN_FAILED,
    "Failed login attempt for email: {$email}"
);
```

> `user_id` is NULL for failed logins since there may be no matching user. `ActivityLog::log()` signature must accept `?int $userId` and the `activity_logs` table `user_id` column must allow NULL (no FK constraint, or FK with `ON DELETE SET NULL`).

### 3. `views/teacher/profile.php` — Profile update

After `$result = $userModel->update(...)` succeeds (inside the `if ($result['success'])` block), add:

```php
require_once __DIR__ . '/../../classes/ActivityLog.php';
$activityLog = new ActivityLog();
$activityLog->log(
    $user['user_id'],
    ActivityLog::ACTION_PROFILE_UPDATED,
    "User updated their profile (User ID: {$user['user_id']})"
);
```

### 4. `views/teacher/lesson-plans/view.php` — Lesson plan viewed

After `$plan` is confirmed non-null (after line 45), add:

```php
require_once __DIR__ . '/../../../classes/ActivityLog.php';
$activityLog = new ActivityLog();
$activityLog->log(
    $user['user_id'],
    ActivityLog::ACTION_LESSON_PLAN_VIEWED,
    "Viewed lesson plan: '{$plan['title']}' (ID: {$lessonPlanId})"
);
```

### 5. `controllers/FileController.php` — File actions

> **Pre-condition:** This file has a git merge conflict and is currently broken. Resolve the conflict first, then add logging.

After each successful file operation, add the corresponding log:

- **Upload success:** `ACTION_FILE_UPLOADED` — `"Uploaded file: '{$originalName}' to lesson plan ID: {$lessonPlanId}"`
- **Download:** `ACTION_FILE_DOWNLOADED` — `"Downloaded file: '{$file['original_name']}' (File ID: {$fileId})"`
- **Delete success:** `ACTION_FILE_DELETED` — `"Deleted file: '{$file['original_name']}' (File ID: {$fileId})"`

### 6. `views/admin/activity-logs.php` — UI whitelist and badge map

Add the new actions to:
- The allowed-actions whitelist array in `ActivityLog::getAll()`
- The badge colour map in the view
- The human-readable label map in the view

New entries:

| Action key | Label | Badge style |
|---|---|---|
| `login_failed` | Login Failed | `bg-danger text-white` |
| `profile_updated` | Profile Updated | `bg-info text-white` |
| `lesson_plan_emailed` | Lesson Emailed | `bg-secondary text-white` |
| `lesson_plan_emailed_multiple` | Lesson Emailed (Multi) | `bg-secondary text-white` |

## Description Field Conventions

All descriptions follow the existing pattern:
- Human-readable sentence
- Include the entity name in quotes where applicable
- Include the ID in parentheses

## Testing Notes

- Trigger a failed login → confirm `login_failed` row appears in admin logs
- Update teacher profile → confirm `profile_updated` row appears
- Open a lesson plan view page → confirm `lesson_plan_viewed` row appears
- Upload/delete a file attachment → confirm respective rows appear
- Verify `user_id = 0` rows display gracefully in the admin log view (no JOIN crash)
