<?php
/**
 * PHPUnit Bootstrap File
 * Sets up testing environment
 */

// Define root directory
define('ROOT_DIR', dirname(__DIR__));

// Load Composer autoloader
require_once ROOT_DIR . '/vendor/autoload.php';

// Load configuration files
require_once ROOT_DIR . '/config/database.php';
require_once ROOT_DIR . '/config/app.php';

// Set test environment
define('TEST_MODE', true);
define('DEBUG_MODE', true);

// Start session for tests that need it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear test database before running tests
echo "PHPUnit Bootstrap: Environment ready for testing\n";
