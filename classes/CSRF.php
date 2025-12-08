<?php

/**
 * CSRF Protection Helper
 * Generates and validates CSRF tokens for forms and AJAX requests
 */
class CSRF
{
    /**
     * Generate a CSRF token and store it in the session
     * @return string The generated token
     */
    public static function generateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get the current CSRF token
     * @return string|null
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Validate a CSRF token
     * @param string $token The token to validate
     * @return bool
     */
    public static function validateToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate a hidden input field with CSRF token
     * @return string HTML input element
     */
    public static function getTokenField()
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token from POST request
     * @return bool
     */
    public static function validatePost()
    {
        $token = $_POST['csrf_token'] ?? '';
        return self::validateToken($token);
    }

    /**
     * Validate CSRF token from request (GET or POST)
     * @return bool
     */
    public static function validateRequest()
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        return self::validateToken($token);
    }

    /**
     * Check CSRF token and die with JSON error if invalid
     * Use this in AJAX endpoints
     */
    public static function requireValidToken()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::validatePost()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit();
        }
    }
}
