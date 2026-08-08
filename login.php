<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
$error = '';

if (isset($_GET['timeout'])) {
    $error = 'Your session expired due to inactivity. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $pdo = getDBConnection();

        // IP Rate Limiting: 5 requests per 60 seconds
        try {
            checkRateLimit($pdo, 'login', 5, 60);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        // Continue login only if rate limit was not exceeded
        if ($error === '') {
            // Prepared statement -> prevents SQL Injection
            $stmt = $pdo->prepare(
                'SELECT * FROM users WHERE email = ?'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Generic error -> prevents user enumeration
            $genericError = 'Invalid email or password.';

            if (!$user) {
                $error = $genericError;
            } else {
                // Account lockout
                try {
                    checkAccountLock($user);
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }

                if ($error === '') {
                    if (password_verify($password, $user['password_hash'])) {

                        resetFailedAttempts($pdo, $user['id']);

                        // Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id']       = $user['id'];
                        $_SESSION['full_name']     = $user['full_name'];
                        $_SESSION['role']          = $user['role'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

                        logAudit($user['id'], 'LOGIN_SUCCESS');

                        header('Location: dashboard.php');
                        exit;

                    } else {
                        registerFailedAttempt(
                            $pdo,
                            $user['id'],
                            $user['failed_attempts']
                        );

                        logAudit($user['id'], 'LOGIN_FAILED');

                        $error = $genericError;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <div class="container">
    <h1>Login</h1>

    <?php if ($error): ?>
    <div class="error" id="errorMessage">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="login.php" autocomplete="off">
    <?= csrfField() ?>

    <label>Email</label>
    <input
        type="email"
        name="email"
        required
        maxlength="150"
    >

    <label>Password</label>
    <input
        type="password"
        name="password"
        required
    >

    <button type="submit">Login</button>
</form>

<p style="margin-top:16px;">
    <a href="register.php" style="color:#38bdf8;">
        Need an account? Register
    </a>
</p>

<?php if ($error): ?>
<script>
setTimeout(function() {
    window.location.reload();
}, 60000);
</script>
<?php endif; ?>