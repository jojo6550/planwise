<?php
function h(string $data): string
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function format_date(string $date, string $format = 'M d, Y'): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    return date($format, $timestamp);
}

function format_datetime(string $date, string $format = 'M d, Y h:i A'): string
{
    return format_date($date, $format);
}

function truncate_text(string $text, int $maxLength = 100): string
{
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

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

function validate_csrf_token(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
