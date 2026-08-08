<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireLogin();

$pdo = getDBConnection();

// Note: query is always scoped to currentUserId() - no wallet id ever
// accepted from the client, which eliminates IDOR on this endpoint entirely.
$stmt = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ?');
$stmt->execute([currentUserId()]);
$wallet = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Wallet - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav><a href="dashboard.php">&larr; Back to Dashboard</a></nav>
    <h1>My Wallet</h1>
    <?php if ($wallet): ?>
      <h2><?= e($wallet['currency']) ?> <?= number_format((float)$wallet['balance'], 2) ?></h2>
      <p style="color:#94a3b8;">Last updated: <?= e($wallet['updated_at']) ?></p>
    <?php else: ?>
      <p>No wallet found.</p>
    <?php endif; ?>
    <a class="btn" href="payment.php">Make a Payment</a>
  </div>
</body>
</html>
