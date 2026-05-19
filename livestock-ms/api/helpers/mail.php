<?php
/**
 * Simple mail helper — logs always; uses PHP mail() when possible.
 */
function sendUserEmail(string $to, string $subject, string $body): bool {
    $logDir = dirname(__DIR__, 2) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logLine = date('Y-m-d H:i:s') . " | To: {$to} | {$subject}\n{$body}\n---\n";
    @file_put_contents($logDir . '/email.log', $logLine, FILE_APPEND);

    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = "From: LivestockHub <noreply@livestockhub.local>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $body, $headers);
}
