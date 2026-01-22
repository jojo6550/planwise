<?php
/**
 * Dashboard Index
 * Main entry point for authenticated users - redirects to role-specific dashboard
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require authentication classes
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = new Auth();

// Check if user is authenticated
if (!$auth->check()) {
    // Not authenticated - redirect to login
    $_SESSION['error'] = 'Please login to access the dashboard';
    header('Location: /public/index.php?page=login');
    exit();
}

// Get current user data
$user = $auth->user();

// Redirect to appropriate dashboard based on role
if ($user['role_id'] == 1) {
    // Admin role - redirect to admin dashboard
    header('Location: /public/index.php?page=admin/dashboard');
    exit();
} else {
    // Teacher role - redirect to teacher dashboard
    header('Location: /public/index.php?page=teacher/dashboard');
    exit();
}
