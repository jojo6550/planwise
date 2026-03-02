<?php
/**
 * Sanitization Helper Functions
 * Provides comprehensive input sanitization for SQL injection prevention
 * and XSS protection
 */

/**
 * Sanitize a string value for safe database use.
 * Primary defense against SQL injection is parameterized queries; this is defense-in-depth.
 * NOTE: Do NOT apply htmlspecialchars here — that causes double-encoding when data is later
 *       displayed through h() / escapeOutput(). HTML-escaping belongs at the view layer only.
 *
 * @param mixed $value The value to sanitize
 * @return string Sanitized string
 */
function sanitizeString($value): string
{
    if ($value === null) {
        return '';
    }

    $value = trim((string)$value);
    $value = stripslashes($value);
    // Remove null bytes that could bypass string comparisons
    $value = str_replace("\0", '', $value);

    return $value;
}

/**
 * Sanitize an integer value
 * 
 * @param mixed $value The value to sanitize
 * @param int $default Default value if not valid
 * @return int Sanitized integer
 */
function sanitizeInt($value, int $default = 0): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    
    // Cast to integer
    $value = (int)$value;
    
    // Ensure non-negative for IDs
    if ($value < 0) {
        return $default;
    }
    
    return $value;
}

/**
 * Sanitize a float value
 * 
 * @param mixed $value The value to sanitize
 * @param float $default Default value if not valid
 * @return float Sanitized float
 */
function sanitizeFloat($value, float $default = 0.0): float
{
    if ($value === null || $value === '') {
        return $default;
    }
    
    // Cast to float
    $value = (float)$value;
    
    if ($value < 0) {
        return $default;
    }
    
    return $value;
}

/**
 * Sanitize an email address
 * 
 * @param string $email The email to sanitize
 * @return string Sanitized email (lowercase)
 */
function sanitizeEmail(string $email): string
{
    $email = trim($email);
    $email = strtolower($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    return $email;
}

/**
 * Sanitize a boolean value
 * 
 * @param mixed $value The value to sanitize
 * @return bool Sanitized boolean
 */
function sanitizeBool($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    
    if ($value === 'true' || $value === '1' || $value === 1 || $value === 'on') {
        return true;
    }
    
    if ($value === 'false' || $value === '0' || $value === 0 || $value === 'off') {
        return false;
    }
    
    return (bool)$value;
}

/**
 * Sanitize an array recursively
 * 
 * @param array $array The array to sanitize
 * @param string $type The type of sanitization ('string', 'int', 'email')
 * @return array Sanitized array
 */
function sanitizeArray(array $array, string $type = 'string'): array
{
    $sanitized = [];
    
    foreach ($array as $key => $value) {
        // Sanitize the key
        $sanitizedKey = sanitizeString($key);
        
        // Sanitize the value based on type
        switch ($type) {
            case 'int':
                $sanitized[$sanitizedKey] = sanitizeInt($value);
                break;
            case 'float':
                $sanitized[$sanitizedKey] = sanitizeFloat($value);
                break;
            case 'email':
                $sanitized[$sanitizedKey] = sanitizeEmail($value);
                break;
            case 'bool':
                $sanitized[$sanitizedKey] = sanitizeBool($value);
                break;
            default:
                $sanitized[$sanitizedKey] = sanitizeString($value);
        }
    }
    
    return $sanitized;
}

/**
 * Sanitize a SQL LIKE pattern to prevent wildcard injection
 * 
 * @param string $value The value to sanitize
 * @return string Sanitized LIKE pattern
 */
function sanitizeLikePattern(string $value): string
{
    // Escape special LIKE characters
    $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    
    return $value;
}

/**
 * Sanitize a search query for database use
 * 
 * @param string $query The search query
 * @return string Sanitized query
 */
function sanitizeSearchQuery(string $query): string
{
    // Remove potentially dangerous characters
    $query = preg_replace('/[^\w\s\-.,@]/i', '', $query);
    
    // Trim and limit length
    $query = trim($query);
    $query = mb_substr($query, 0, 255);
    
    return $query;
}

/**
 * Validate and sanitize a username
 * 
 * @param string $username The username to validate
 * @return string|null Sanitized username or null if invalid
 */
function sanitizeUsername(string $username): ?string
{
    // Username should be alphanumeric with underscores, 3-30 chars
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return null;
    }
    
    return $username;
}

/**
 * Validate and sanitize a URL
 * 
 * @param string $url The URL to validate
 * @return string|null Sanitized URL or null if invalid
 */
function sanitizeUrl(string $url): ?string
{
    $url = trim($url);
    
    // Add protocol if missing
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    
    return $url;
}

/**
 * Clean all input data from $_POST, $_GET, or $_REQUEST
 * 
 * @param string $source The input source ('post', 'get', 'request')
 * @param array $fields The fields to sanitize with their types
 * @return array Sanitized data
 */
function cleanInput(string $source = 'post', array $fields = []): array
{
    $input = [];
    
    switch ($source) {
        case 'post':
            $input = $_POST;
            break;
        case 'get':
            $input = $_GET;
            break;
        case 'request':
        default:
            $input = $_REQUEST;
    }
    
    $sanitized = [];
    
    foreach ($fields as $field => $type) {
        if (!isset($input[$field])) {
            $sanitized[$field] = null;
            continue;
        }
        
        $value = $input[$field];
        
        switch ($type) {
            case 'int':
            case 'integer':
                $sanitized[$field] = sanitizeInt($value);
                break;
            case 'float':
            case 'double':
                $sanitized[$field] = sanitizeFloat($value);
                break;
            case 'string':
            case 'text':
                $sanitized[$field] = sanitizeString($value);
                break;
            case 'email':
                $sanitized[$field] = sanitizeEmail($value);
                break;
            case 'bool':
            case 'boolean':
                $sanitized[$field] = sanitizeBool($value);
                break;
            case 'array':
                $sanitized[$field] = is_array($value) ? $value : [];
                break;
            case 'username':
                $sanitized[$field] = sanitizeUsername($value);
                break;
            case 'url':
                $sanitized[$field] = sanitizeUrl($value);
                break;
            case 'search':
                $sanitized[$field] = sanitizeSearchQuery($value);
                break;
            default:
                $sanitized[$field] = sanitizeString($value);
        }
    }
    
    return $sanitized;
}

/**
 * Get clean integer from input (with optional range validation)
 * 
 * @param string $key The key to look for
 * @param string $source The input source
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @param int $default Default value
 * @return int The sanitized integer
 */
function getInt(string $key, string $source = 'request', int $min = 0, int $max = PHP_INT_MAX, int $default = 0): int
{
    $value = $default;
    
    switch ($source) {
        case 'post':
            $value = $_POST[$key] ?? $default;
            break;
        case 'get':
            $value = $_GET[$key] ?? $default;
            break;
        default:
            $value = $_REQUEST[$key] ?? $default;
    }
    
    $value = sanitizeInt($value, $default);
    
    // Apply range validation
    if ($value < $min) {
        $value = $min;
    }
    if ($value > $max) {
        $value = $max;
    }
    
    return $value;
}

/**
 * Get clean string from input
 * 
 * @param string $key The key to look for
 * @param string $source The input source
 * @param string $default Default value
 * @return string The sanitized string
 */
function getString(string $key, string $source = 'request', string $default = ''): string
{
    $value = $default;
    
    switch ($source) {
        case 'post':
            $value = $_POST[$key] ?? $default;
            break;
        case 'get':
            $value = $_GET[$key] ?? $default;
            break;
        default:
            $value = $_REQUEST[$key] ?? $default;
    }
    
    return sanitizeString($value);
}

/**
 * Get clean email from input
 * 
 * @param string $key The key to look for
 * @param string $source The input source
 * @return string|null The sanitized email or null
 */
function getEmail(string $key, string $source = 'request'): ?string
{
    $value = '';
    
    switch ($source) {
        case 'post':
            $value = $_POST[$key] ?? '';
            break;
        case 'get':
            $value = $_GET[$key] ?? '';
            break;
        default:
            $value = $_REQUEST[$key] ?? '';
    }
    
    $value = sanitizeEmail($value);
    
    // Validate email format
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    
    return $value;
}

/**
 * Escape output for safe HTML display
 * This prevents XSS attacks when displaying user input
 * 
 * @param mixed $value The value to escape
 * @return string Escaped string
 */
function escapeOutput($value): string
{
    if ($value === null) {
        return '';
    }
    
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Strip all HTML tags from a string
 * 
 * @param string $value The value to strip
 * @return string Stripped string
 */
function stripHtml(string $value): string
{
    return strip_tags(trim($value));
}
