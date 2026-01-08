<?php

/**
 * CSRF Protection Helper
 * Simple and reliable CSRF token management
 */
class CSRF
{
    /**
     * Generate a CSRF token and store it in the session
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
     * Get the current CSRF token, generate if not exists
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            return self::generateToken();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token
     */
    public static function validateToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate a hidden input field with CSRF token
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token from POST request
     */
    public static function validatePost()
    {
        $token = $_POST['csrf_token'] ?? '';
        return self::validateToken($token);
    }

    /**
     * Check CSRF token and return JSON error if invalid
     */
    public static function requireValidToken()
    {
        if (!self::validatePost()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit();
        }
    }
}
