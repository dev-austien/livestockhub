<?php
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

class Auth {
    private static ?array $currentUser = null;

    /**
     * Require a valid JWT token. Returns decoded payload or halts with 401.
     */
    public static function requireAuth(): array {
        $token = self::extractToken();
        if (!$token) Response::unauthorized('No token provided');

        $payload = JWT::decode($token);
        if (!$payload) Response::unauthorized('Invalid or expired token');

        self::$currentUser = $payload;
        return $payload;
    }

    /**
     * Require auth AND one of the given roles.
     * @param string|string[] $roles
     */
    public static function requireRole($roles): array {
        $user = self::requireAuth();
        $roles = (array)$roles;
        if (!in_array($user['role'], $roles)) {
            Response::forbidden("Access restricted to: " . implode(', ', $roles));
        }
        return $user;
    }

    /**
     * Extract Bearer token from Authorization header.
     */
    private static function extractToken(): ?string {
        $headers = getallheaders();
        $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public static function getCurrentUser(): ?array {
        return self::$currentUser;
    }
}
