<?php
/**
 * Auth + RBAC + session security helpers
 */

require_once __DIR__ . '/../config/db.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,     // requires HTTPS in production
            'httponly' => true,     // blocks JS access -> mitigates XSS cookie theft
            'samesite' => 'Strict', // mitigates CSRF
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    startSecureSession();
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    // Session fixation / idle timeout check
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('Access denied: insufficient privileges.');
    }
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function e(string $value): string {
    // Escape output -> mitigates Stored/Reflected XSS
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Ownership check -> mitigates IDOR / Broken Access Control.
 * Every direct object reference (wallet, transaction) MUST be scoped to the
 * logged-in user's own id at the query level, not just checked after fetch.
 */
function assertOwnsResource(int $resourceUserId): void {
    if ($resourceUserId !== currentUserId()) {
        http_response_code(403);
        die('Access denied.');
    }
}

function logAudit(?int $userId, string $action, string $details = ''): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}
