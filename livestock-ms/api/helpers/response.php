<?php
class Response {
    public static function json(int $status, bool $success, string $message, $data = null): void {
        http_response_code($status);
        $body = ['success' => $success, 'message' => $message];
        if ($data !== null) $body['data'] = $data;
        echo json_encode($body);
        exit;
    }

    public static function success($data = null, string $message = 'Success', int $status = 200): void {
        self::json($status, true, $message, $data);
    }

    public static function created($data = null, string $message = 'Created successfully'): void {
        self::json(201, true, $message, $data);
    }

    public static function error(string $message, int $status = 400, $data = null): void {
        self::json($status, false, $message, $data);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::json(401, false, $message);
    }

    public static function forbidden(string $message = 'Forbidden'): void {
        self::json(403, false, $message);
    }

    public static function notFound(string $message = 'Resource not found'): void {
        self::json(404, false, $message);
    }
}
