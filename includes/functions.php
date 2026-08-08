<?php
/**
 * Validation + business logic helpers
 */

function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isStrongPassword(string $password): bool {
    // Min 8 chars, at least one upper, one lower, one digit, one special char
    return (bool) preg_match(
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
        $password
    );
}

function isValidAmount($amount): bool {
    return is_numeric($amount)
        && (float)$amount > 0
        && (float)$amount <= 1000000;
}

function generateReferenceId(string $prefix = 'TXN'): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(8)));
}

/**
 * Account lockout
 * Locks account for 15 minutes after 10 failed login attempts.
 */
function checkAccountLock(array $user): void {
    if (
        !empty($user['locked_until']) &&
        strtotime($user['locked_until']) > time()
    ) {
        die(
            'Account temporarily locked due to multiple failed login attempts. ' .
            'Try again later.'
        );
    }
}

function registerFailedAttempt(
    PDO $pdo,
    int $userId,
    int $currentFailedAttempts
): void {
    $attempts = $currentFailedAttempts + 1;

    if ($attempts >= 10) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET failed_attempts = 0,
                 locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
             WHERE id = ?'
        );
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET failed_attempts = ?
             WHERE id = ?'
        );
        $stmt->execute([$attempts, $userId]);
    }
}

function resetFailedAttempts(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_attempts = 0,
             locked_until = NULL
         WHERE id = ?'
    );
    $stmt->execute([$userId]);
}

/**
 * IP-based Rate Limiting
 * Default: 5 requests per 60 seconds per IP/endpoint.
 */
function checkRateLimit(
    PDO $pdo,
    string $endpoint,
    int $limit = 5,
    int $window = 60
): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $pdo->prepare(
        'SELECT *
         FROM rate_limits
         WHERE ip_address = ?
           AND endpoint = ?'
    );
    $stmt->execute([$ip, $endpoint]);

    $row = $stmt->fetch();

    // First request from this IP for this endpoint
    if (!$row) {
        $stmt = $pdo->prepare(
            'INSERT INTO rate_limits
             (ip_address, endpoint, request_count)
             VALUES (?, ?, 1)'
        );
        $stmt->execute([$ip, $endpoint]);
        return;
    }

    $elapsed = time() - strtotime($row['last_request']);

    // Start a new rate-limit window
    if ($elapsed >= $window) {
        $stmt = $pdo->prepare(
            'UPDATE rate_limits
             SET request_count = 1,
                 last_request = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$row['id']]);
        return;
    }

    // Limit exceeded
    if ($row['request_count'] >= $limit) {
    throw new RuntimeException(
        'Too many login attempts. Please try again after 1 minute.'
    );
}

    // Increment request counter
    $stmt = $pdo->prepare(
        'UPDATE rate_limits
         SET request_count = request_count + 1
         WHERE id = ?'
    );
    $stmt->execute([$row['id']]);
}