<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password must be 8+ chars with upper, lower, digit, and special character.';
    } else {
        $pdo = getDBConnection();

        // Prepared statement -> prevents SQL Injection
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$fullName, $email, $hash, 'user']);
                $userId = (int) $pdo->lastInsertId();

                $walletStmt = $pdo->prepare(
                    'INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, ?)'
                );
                $walletStmt->execute([$userId, 'INR']);

                $pdo->commit();
                logAudit($userId, 'REGISTER', 'New account created');
                $success = 'Account created successfully. You can now log in.';
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Registration failed: ' . $e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <h1>Create Account</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <form method="POST" action="register.php" autocomplete="off">
      <?= csrfField() ?>
      <label>Full Name</label>
      <input type="text" name="full_name" required maxlength="100">
      <label>Email</label>
      <input type="email" name="email" required maxlength="150">
      <label>Password</label>
      <input type="password" name="password" required minlength="8">
      <button type="submit">Register</button>
    </form>
    <p style="margin-top:16px;"><a href="login.php" style="color:#38bdf8;">Already have an account? Login</a></p>
  </div>
</body>
</html>
