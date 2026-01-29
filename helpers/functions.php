<?php
/**
 * Helper Functions
 * Common utility functions for the application
 */

/**
 * Log an activity - Convenience function
 *
 * @param int $userId User ID performing the action
 * @param string $action Action performed
 * @param string $description Detailed description
 * @return bool Success status
 */
function activity_log(int $userId, string $action, string $description = ''): bool
{
    require_once __DIR__ . '/../classes/ActivityLog.php';
    $activityLog = new ActivityLog();
    return $activityLog->log($userId, $action, $description);
}

/**
 * Get current user ID from session
 *
 * @return int|null User ID or null if not logged in
 */
function get_current_user_id(): ?int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    
    return null;
}

/**
 * Check if user is logged in
 *
 * @return bool True if logged in, false otherwise
 */
function is_logged_in(): bool
{
    return get_current_user_id() !== null;
}

/**
 * Get current user data
 *
 * @return array|null User data or null if not logged in
 */
function get_current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth();
    return $auth->user();
}

/**
 * Sanitize output for HTML
 *
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function h(string $data): string
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function format_date(string $date, string $format = 'M d, Y'): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date($format, $timestamp);
}

/**
 * Format datetime for display
 *
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted datetime
 */
function format_datetime(string $date, string $format = 'M d, Y h:i A'): string
{
    return format_date($date, $format);
}

/**
 * Truncate text with ellipsis
 *
 * @param string $text Text to truncate
 * @param int $maxLength Maximum length
 * @return string Truncated text
 */
function truncate_text(string $text, int $maxLength = 100): string
{
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

/**
 * Generate a random string
 *
 * @param int $length Length of the string
 * @return string Random string
 */
function random_string(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get flash message and clear it
 *
 * @param string $key Message key
 * @return string|null Message or null
 */
function flash(string $key): ?string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Set flash message
 *
 * @param string $key Message key
 * @param string $message Message content
 * @return void
 */
function set_flash(string $key, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash'][$key] = $message;
}

/**
 * Redirect to URL
 *
 * @param string $url URL to redirect to
 * @return void
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit();
}

/**
 * Get base URL
 *
 * @return string Base URL
 */
function base_url(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return "{$protocol}://{$host}";
}

/**
 * Get asset URL
 *
 * @param string $path Asset path
 * @return string Full asset URL
 */
function asset_url(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * Format bytes to human readable
 *
 * @param int $bytes Bytes
 * @return string Formatted size
 */
function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    $size = $bytes;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * Validate email format
 *
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generate_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 *
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

