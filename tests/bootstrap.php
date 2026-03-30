<?php
/**
 * PHPUnit Bootstrap
 * Loads Composer autoloader and all required helper/class files for the test suite.
 */

// Configure sessions so session_start() works in CLI even after PHP has emitted
// startup warnings (e.g. duplicate openssl extension in XAMPP). Suppress both
// cookie-header and cache-limiter-header emissions.
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.cache_limiter', ''); // empty string = no cache-limiter header

// Redirect error_log() output to a log file so Auth/LessonPlan error_log() calls
// do not appear on STDERR and confuse PHPUnit's separate-process runner.
$testLogDir = __DIR__ . '/../logs';
if (!is_dir($testLogDir)) {
    mkdir($testLogDir, 0755, true);
}
ini_set('error_log', $testLogDir . '/test.log');

// Require Composer autoloader (provides PHPUnit and any PSR-4 mapped classes)
require_once __DIR__ . '/../vendor/autoload.php';

// Load helper functions (sanitize.php must come before Database.php)
require_once __DIR__ . '/../helpers/sanitize.php';

// Load core classes in dependency order
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/LessonPlan.php';
require_once __DIR__ . '/../classes/Validator.php';
