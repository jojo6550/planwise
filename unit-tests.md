# PlanWise — Unit Test Documentation

## Table of Contents
1. [Overview](#overview)
2. [Why These Tests Matter](#why-these-tests-matter)
3. [Project Structure](#project-structure)
4. [Requirements & Setup](#requirements--setup)
5. [Running the Tests](#running-the-tests)
6. [Backend Test Suites (PHPUnit)](#backend-test-suites-phpunit)
   - [ValidatorTest](#validatortest)
   - [SanitizeTest](#sanitizetest)
   - [AuthTest](#authtest)
   - [UserTest](#usertest)
   - [LessonPlanTest](#lessonplantest)
7. [Frontend Test Suite (Jest)](#frontend-test-suite-jest)
   - [app.test.js](#apptestjs)
8. [Mocking Strategy](#mocking-strategy)
9. [Coverage Targets](#coverage-targets)
10. [Troubleshooting](#troubleshooting)

---

## Overview

This document describes the complete unit-test suite for **PlanWise**, a PHP 8.2+ lesson-planning web application. The suite covers:

| Layer | Framework | Test files |
|-------|-----------|-----------|
| PHP backend classes | PHPUnit 9 | `tests/Unit/*.php` |
| JavaScript frontend | Jest 29 | `tests/js/*.test.js` |

All tests run without a live database or mail server — external dependencies are replaced with mocks or in-memory stubs.

---

## Why These Tests Matter

| Risk without tests | How the test catches it |
|--------------------|------------------------|
| `Validator` silently accepts invalid email format | `testEmailFailsForInvalidFormat` asserts `false` return |
| Null-byte injection slips through `sanitizeString` | `testSanitizeStringRemovesNullBytes` checks the output |
| Login succeeds for inactive accounts | `testLoginFailsWhenUserIsInactive` mocks an inactive user row |
| Session stays alive indefinitely | `testCheckReturnsFalseWhenSessionIsExpired` sets `login_time` 31 min ago |
| Lesson plan created with a 2-character title | `testCreateFailsWhenTitleTooShort` expects the "3 characters" error message |
| XSS via unsanitised lesson-plan titles in search results | `escapeHtml` tests reject `<script>` in the output |
| Rapid keystrokes trigger multiple server requests | Debounce test asserts `fetch` called exactly once after 350 ms |

---

## Project Structure

```
planwise/
├── classes/
│   ├── Auth.php
│   ├── Database.php
│   ├── LessonPlan.php
│   ├── User.php
│   └── Validator.php
├── helpers/
│   └── sanitize.php
├── public/js/
│   └── app.js
├── tests/
│   ├── bootstrap.php          ← PHPUnit bootstrap
│   ├── Unit/
│   │   ├── ValidatorTest.php
│   │   ├── SanitizeTest.php
│   │   ├── AuthTest.php
│   │   ├── UserTest.php
│   │   └── LessonPlanTest.php
│   └── js/
│       └── app.test.js
├── phpunit.xml                ← PHPUnit configuration
└── package.json               ← Jest configuration
```

---

## Requirements & Setup

### PHP / Composer
- PHP ≥ 8.2
- Composer (https://getcomposer.org)
- `phpunit/phpunit ^9.0` must be installed (it is already listed as a dev dependency)

```bash
# Install/update Composer dev dependencies
composer install
```

### Node / npm (frontend tests only)
- Node.js ≥ 18
- npm ≥ 9

```bash
# Install Jest and jsdom (listed in package.json devDependencies)
npm install
```

---

## Running the Tests

### All backend tests
```bash
cd C:/xampp/htdocs/planwise
vendor/bin/phpunit --testdox
```

### A single backend test class
```bash
vendor/bin/phpunit --testdox tests/Unit/ValidatorTest.php
```

### With code-coverage HTML report (requires Xdebug or PCOV)
```bash
vendor/bin/phpunit --coverage-html coverage/
# then open coverage/index.html in a browser
```

### All frontend tests
```bash
npm test
```

### Frontend tests in watch mode
```bash
npm run test:watch
```

### Frontend tests with coverage
```bash
npm run test:coverage
```

---

## Backend Test Suites (PHPUnit)

### `ValidatorTest`

**File:** `tests/Unit/ValidatorTest.php`
**Class under test:** `classes/Validator.php`
**Database required:** No

The `Validator` class provides rule-based server-side validation. Tests are grouped by rule name and by the error-reporting helpers.

#### Test cases

| Test method | What it verifies |
|-------------|-----------------|
| `testRequiredFailsOnEmptyString` | Empty string fails the `required` rule |
| `testRequiredFailsOnNull` | Null fails the `required` rule |
| `testRequiredFailsWhenFieldMissing` | Missing array key fails the `required` rule |
| `testRequiredPassesForStringZero` | The string `'0'` is treated as present (not empty) |
| `testRequiredPassesForIntegerZero` | The integer `0` is treated as present |
| `testRequiredPassesForPopulatedString` | Non-empty string passes |
| `testEmailFailsForInvalidFormat` | `'not-an-email'` fails `email` rule |
| `testEmailFailsForMissingAtSign` | `'userdomain.com'` fails `email` rule |
| `testEmailPassesForValidAddress` | `'user@example.com'` passes |
| `testEmailPassesWhenEmpty` | Empty value is ignored by the `email` rule (optional field) |
| `testMinFailsWhenShorterThanMin` | 3-char value fails `min:8` |
| `testMinPassesWhenExactlyMin` | 8-char value passes `min:8` |
| `testMinPassesWhenLongerThanMin` | 9-char value passes `min:8` |
| `testMinPassesWhenEmpty` | Empty value is ignored by `min` (optional field) |
| `testMaxFailsWhenLongerThanMax` | 11-char value fails `max:5` |
| `testMaxPassesWhenExactlyMax` | 5-char value passes `max:5` |
| `testMaxPassesWhenShorterThanMax` | 3-char value passes `max:5` |
| `testNumericFailsForLetters` | `'twenty'` fails `numeric` |
| `testNumericPassesForIntegerString` | `'42'` passes `numeric` |
| `testNumericPassesForFloatString` | `'9.99'` passes `numeric` |
| `testUrlFailsForInvalidUrl` | `'not a url'` fails `url` |
| `testUrlPassesForHttpsUrl` | `'https://example.com'` passes `url` |
| `testAlphaFailsForAlphanumeric` | `'John123'` fails `alpha` |
| `testAlphaPassesForPureLetters` | `'John'` passes `alpha` |
| `testAlphanumFailsForSpecialChars` | `'hello!'` fails `alphanum` |
| `testAlphanumPassesForLettersAndDigits` | `'Hello42'` passes `alphanum` |
| `testPipeChainedRules` | Pipe-separated rule string; stops on first failure |
| `testPipeChainedRulesAsArray` | Rules passed as array work identically to pipe string |
| `testMultipleFieldsIndependent` | Error on one field does not bleed into another |
| `testGetAllErrorsReturnsAllFields` | `getAllErrors()` contains every failing field |
| `testGetErrorsReturnsArrayForField` | `getErrors($field)` returns a non-empty array for a failing field |
| `testGetErrorsReturnsEmptyArrayForValidField` | `getErrors($field)` returns `[]` for a passing field |
| `testHasErrorsReturnsTrueWhenAnyError` | `hasErrors()` is `true` after any failure |
| `testHasErrorsReturnsFalseWhenNoErrors` | `hasErrors()` is `false` when all pass |
| `testHasErrorsWithFieldNameReturnsTrueForInvalidField` | `hasErrors('name')` is `true` for failing field |
| `testHasErrorsWithFieldNameReturnsFalseForValidField` | `hasErrors('name')` is `false` for passing field |
| `testGetFirstErrorReturnsFirstMessage` | `getFirstError()` returns a non-empty string |
| `testGetFirstErrorReturnsNullWhenNoError` | `getFirstError()` returns `null` for a passing field |
| `testValidateResetsErrorsBetweenCalls` | Calling `validate()` twice does not accumulate stale errors |

---

### `SanitizeTest`

**File:** `tests/Unit/SanitizeTest.php`
**Functions under test:** `helpers/sanitize.php`
**Database required:** No

These are global PHP functions providing defence-in-depth sanitisation. Tests verify that each function handles edge cases (nulls, negatives, special characters, length limits) correctly.

#### Key test groups

| Group | Functions tested |
|-------|-----------------|
| String sanitisation | `sanitizeString` — trims, strips backslashes, removes null bytes |
| Integer sanitisation | `sanitizeInt` — negative → default, null/empty → default |
| Float sanitisation | `sanitizeFloat` — negative → default |
| Email sanitisation | `sanitizeEmail` — lowercases, trims, removes non-email chars |
| Boolean coercion | `sanitizeBool` — string/integer truthy & falsy values |
| Array sanitisation | `sanitizeArray` — applies type-specific sanitizer per element |
| SQL LIKE escaping | `sanitizeLikePattern` — escapes `%`, `_`, `\` |
| Search query cleaning | `sanitizeSearchQuery` — strips dangerous chars, 255-char limit |
| Username validation | `sanitizeUsername` — 3-30 alphanumeric+underscore only |
| URL validation | `sanitizeUrl` — adds `http://` prefix, returns null for invalid |
| Output escaping | `escapeOutput` — null → `''`, `<>&"'` → HTML entities |
| HTML stripping | `stripHtml` — removes all tags and trims |

---

### `AuthTest`

**File:** `tests/Unit/AuthTest.php`
**Class under test:** `classes/Auth.php`
**Database required:** No (User is mocked)

`Auth` accepts a `User` instance via its constructor. Tests pass a `PHPUnit\MockObject` of `User`, so no database is needed.

#### Test cases

| Test method | Scenario |
|-------------|---------|
| `testLoginFailsWhenEmailIsEmpty` | Guard: email empty → early return false |
| `testLoginFailsWhenPasswordIsEmpty` | Guard: password empty → early return false |
| `testLoginFailsForInvalidEmailFormat` | `filter_var` rejects malformed address |
| `testLoginFailsWhenUserNotFound` | `User::findByEmail` returns null → false |
| `testLoginFailsWhenUserIsInactive` | User row has `status = 'inactive'` → false |
| `testLoginFailsWhenPasswordIsWrong` | `password_verify` returns false → false |
| `testLoginSucceedsWithCorrectCredentials` | Returns `['success' => true, 'user' => [...]]` |
| `testLoginSetsSessionOnSuccess` | `$_SESSION['authenticated']` and `user_id` are set |
| `testLogoutClearsSessionAndReturnsSuccess` | `$_SESSION` is cleared; returns success |
| `testCheckReturnsFalseWhenSessionEmpty` | No session variables → false |
| `testCheckReturnsTrueWhenSessionIsValid` | Valid session within 30-min window → true |
| `testCheckReturnsFalseWhenSessionIsExpired` | `login_time` > 30 min ago → false |
| `testUserReturnsNullWhenNotAuthenticated` | `user()` returns null if not logged in |
| `testUserReturnsArrayWhenAuthenticated` | `user()` returns array with all session fields |
| `testHasRoleReturnsFalseWhenNotAuthenticated` | No session → false |
| `testHasRoleReturnsFalseForWrongRole` | Role mismatch → false |
| `testHasRoleReturnsTrueForCorrectRole` | Role matches → true |
| `testIdReturnsNullWhenNoSession` | No session → null |
| `testIdReturnsUserIdWhenSessionSet` | Session has `user_id` → returns it |

---

### `UserTest`

**File:** `tests/Unit/UserTest.php`
**Class under test:** `classes/User.php`
**Database required:** No (Database singleton is mocked via Reflection)

`User::__construct()` calls `Database::getInstance()`. Because `Database` has a private constructor (singleton), tests inject a PHPUnit mock into the static `$instance` property using `ReflectionClass`. The property is reset in `tearDown()`.

#### Test cases

| Test method | Scenario |
|-------------|---------|
| `testCreateFailsWhenFirstNameMissing` | Required field guard |
| `testCreateFailsWhenLastNameMissing` | Required field guard |
| `testCreateFailsWhenEmailMissing` | Required field guard |
| `testCreateFailsForInvalidEmailFormat` | `filter_var` rejects bad address |
| `testCreateFailsWhenEmailAlreadyExists` | Mock DB returns existing row → "Email already exists" |
| `testCreateSucceedsWithValidData` | Mock DB returns no duplicate + insert → true + user_id |
| `testFindByEmailReturnsUserRowWhenFound` | Mock `fetch` returns row → array |
| `testFindByEmailReturnsNullWhenNotFound` | Mock `fetch` returns false → null |
| `testFindByIdReturnsUserRowWhenFound` | Mock `fetch` returns row → array |
| `testFindByIdReturnsNullWhenNotFound` | Mock `fetch` returns false → null |
| `testUpdateStatusFailsForInvalidStatus` | `'banned'` is not `active`/`inactive` → false |
| `testUpdateStatusSucceedsForActive` | `'active'` calls DB update → true |
| `testUpdateStatusSucceedsForInactive` | `'inactive'` calls DB update → true |

---

### `LessonPlanTest`

**File:** `tests/Unit/LessonPlanTest.php`
**Class under test:** `classes/LessonPlan.php`
**Database required:** No (same Reflection-based singleton injection as `UserTest`)

#### Test cases

| Test method | Scenario |
|-------------|---------|
| `testCreateFailsWhenUserIdMissing` | Required field guard |
| `testCreateFailsWhenTitleMissing` | Required field guard |
| `testCreateFailsWhenTitleTooShort` | Title < 3 chars → error message |
| `testCreateFailsForNegativeDuration` | Duration < 0 → "positive number" error |
| `testCreateFailsForNonNumericDuration` | Duration `'abc'` → non-numeric error |
| `testCreateSucceedsWithValidData` | Mock `insert` returns id → `['success' => true, 'lesson_id' => ...]` |
| `testCreateSucceedsWithEmptyOptionalDuration` | Empty string duration is treated as null (allowed) |
| `testUpdateFailsWhenTitleTooShort` | Title < 3 chars during update → error |
| `testUpdateFailsForNegativeDuration` | Negative duration during update → error |
| `testUpdateFailsWhenPlanNotFound` | `getById` returns null (wrong user or missing plan) → "not found" |
| `testGetStatsReturnsCastIntsFromDbRow` | String values from DB are cast to `int` |
| `testGetStatsReturnsZerosWhenDbReturnsNothing` | DB returns false → all zeros |

---

## Frontend Test Suite (Jest)

### `app.test.js`

**File:** `tests/js/app.test.js`
**Code under test:** `public/js/app.js`
**Environment:** jsdom (simulated browser DOM)

`app.js` is an IIFE that attaches to `#lessonPlanSearchInput`. Tests cover:

1. The pure `escapeHtml()` utility extracted for direct testing.
2. The `getStatusBadge()` status-to-class mapping extracted for direct testing.
3. The full IIFE via DOM simulation and a mocked `fetch`.

#### `escapeHtml()` test cases

| Test | Input | Expected output |
|------|-------|----------------|
| Empty string | `''` | `''` |
| Angle brackets | `'<p>hello</p>'` | `'&lt;p&gt;hello&lt;/p&gt;'` |
| XSS payload | `'<script>alert(1)</script>'` | fully escaped — no raw `<script>` |
| Double quotes | `'"hello"'` | `'&quot;hello&quot;'` |
| Ampersand | `'a & b'` | `'a &amp; b'` |
| Plain text | `'Hello World'` | `'Hello World'` (unchanged) |
| null coercion | `null` | `'null'` (no throw) |
| undefined coercion | `undefined` | `'undefined'` (no throw) |
| Number coercion | `42` | `'42'` |

#### `getStatusBadge()` test cases

| Status | Expected class |
|--------|---------------|
| `'published'` | `'badge bg-success'` |
| `'draft'` | `'badge bg-warning text-dark'` |
| `'archived'` | `'badge bg-secondary'` |
| `'unknown'` | `'badge bg-light text-dark'` (fallback) |
| `''` | `'badge bg-light text-dark'` (fallback) |
| `undefined` | `'badge bg-light text-dark'` (fallback) |

#### Debounce / IIFE integration test cases

| Test | What it verifies |
|------|-----------------|
| No fetch before 300 ms | `fetch` not called immediately after `input` event |
| Fetch called once after debounce | Rapid typing → single `fetch` call after 350 ms |
| URL contains encoded search term | `encodeURIComponent` applied to search term in URL |

---

## Mocking Strategy

### Why mocking is necessary

PlanWise's `Database` class uses the **Singleton pattern** with a `private` constructor, and `User`/`LessonPlan` hard-wire `Database::getInstance()` inside their own constructors. Without mocking, every test would need a live MySQL connection.

### PHP: Mocking the Database singleton

```php
// 1. Build a mock that bypasses the private constructor
$mockDb = $this->getMockBuilder(Database::class)
    ->disableOriginalConstructor()
    ->getMock();

// 2. Inject the mock into the private static $instance property
$ref  = new ReflectionClass(Database::class);
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, $mockDb);

// 3. Now new User() (or new LessonPlan()) will receive $mockDb
$user = new User();

// 4. Reset in tearDown() so tests stay isolated
$prop->setValue(null, null);
```

### PHP: Mocking the User dependency in Auth

`Auth::__construct(User $user = null)` already supports **constructor injection**. No reflection is needed:

```php
$userMock = $this->createMock(User::class);
$userMock->method('findByEmail')->willReturn(/* ... */);
$auth = new Auth($userMock);
```

### JavaScript: Mocking fetch

Jest's `jest.fn()` replaces the global `fetch` so no real HTTP request is made:

```js
global.fetch = jest.fn().mockResolvedValue({
    json: jest.fn().mockResolvedValue({ success: true, data: [...] }),
});
```

---

## Coverage Targets

| Component | Minimum target |
|-----------|---------------|
| `Validator` | 95% line coverage |
| `helpers/sanitize.php` | 90% line coverage |
| `Auth` | 85% line coverage |
| `User` (validation paths) | 80% line coverage |
| `LessonPlan` (validation paths) | 80% line coverage |
| `app.js` (escapeHtml, badge logic) | 90% line coverage |

To view coverage reports:

```bash
# PHP (requires Xdebug or PCOV)
vendor/bin/phpunit --coverage-html coverage/html
open coverage/html/index.html

# JavaScript
npm run test:coverage
# Coverage report appears in coverage/ directory
```

---

## Troubleshooting

### `Class 'Database' not found`
Ensure `tests/bootstrap.php` is listed as the `bootstrap` attribute in `phpunit.xml` (it is, by default). Run `composer install` to ensure autoloader files are present.

### `Cannot mock class with private constructor`
Use `getMockBuilder(Database::class)->disableOriginalConstructor()->getMock()` instead of `createMock(Database::class)`.

### `session_regenerate_id(): Session ID cannot be regenerated after headers have already been sent`
This is a known XAMPP/PHP CLI quirk: the duplicate `openssl` extension warning is printed to STDOUT before any PHP code runs, which PHP 8 counts as "headers sent". The warning is harmless — `$_SESSION` is still populated correctly, and `phpunit.xml` is configured with `convertWarningsToExceptions="false"` so the warning does not fail the test. The test still verifies that `login()` returns the correct response and sets `$_SESSION`.

### `fetch is not defined` (Jest)
Make sure `jest-environment-jsdom` is installed (`npm install`) and `"testEnvironment": "jsdom"` is set in `package.json`.

### Tests pass locally but fail in CI
Confirm PHP ≥ 8.2 and the `Reflection` extension are available. For Jest, ensure Node ≥ 18 is used and `npm ci` is run instead of `npm install`.
