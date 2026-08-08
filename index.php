<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <h1>Secure Payment Gateway</h1>
    <p>A demo platform showcasing OWASP Top 10 remediations: parameterized queries, RBAC, CSRF protection, secure sessions, and business-logic-safe payment/refund flows.</p>
    <a class="btn" href="login.php">Login</a>
    <a class="btn" href="register.php" style="background:#334155;color:#e2e8f0;margin-top:10px;">Register</a>
  </div>
</body>
</html>
