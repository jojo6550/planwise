<?php
/**
 * BaseController
 * Abstract base class providing shared utilities for all controllers.
 * Eliminates duplicate sanitize / CSRF / redirect / JSON methods.
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/sanitize.php';

abstract class BaseController
{
    /**
     * Sanitize a string for safe DB storage.
     * NOTE: Do NOT use htmlspecialchars here — parameterized queries protect the DB.
     *       Use h() / escapeOutput() at the view layer for XSS protection.
     *
     * @param string $input
     * @return string
     */
    protected function sanitize(string $input): string
    {
        return trim(stripslashes($input));
    }

    /**
     * Validate the submitted CSRF token against the session token.
     *
     * @param string $token
     * @return bool
     */
    protected function validateCsrfToken(string $token): bool
    {
        return validate_csrf_token($token);
    }

    /**
     * Redirect to a page with an error flash message.
     *
     * @param string $message
     * @param string $page   Page key (appended to ?page=)
     */
    protected function redirectWithError(string $message, string $page): void
    {
        $_SESSION['error'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Redirect to a page with a success flash message.
     *
     * @param string $message
     * @param string $page
     */
    protected function redirectWithSuccess(string $message, string $page): void
    {
        $_SESSION['success'] = $message;
        header("Location: /planwise/public/index.php?page={$page}");
        exit();
    }

    /**
     * Emit a JSON response and exit.
     *
     * @param array $data
     * @param int   $statusCode
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
