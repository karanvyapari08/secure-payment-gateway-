<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireLogin();

$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT balance, currency FROM wallets WHERE user_id = ?');
$stmt->execute([currentUserId()]);
$wallet = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container wide">
    <nav>
      <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="wallet.php">Wallet</a>
        <a href="payment.php">Make Payment</a>
        <a href="transactions.php">Transactions</a>
        <a href="refund.php">Refunds</a>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <a href="admin.php">Admin Panel</a>
        <?php endif; ?>
      </div>
      <a href="logout.php" class="btn danger" style="width:auto;padding:6px 14px;">Logout</a>
    </nav>
    <h1>Welcome, <?= e($_SESSION['full_name']) ?></h1>
    <p>Role: <strong><?= e($_SESSION['role']) ?></strong></p>
    <?php if ($wallet): ?>
      <h2>Wallet Balance: <?= e($wallet['currency']) ?> <?= number_format((float)$wallet['balance'], 2) ?></h2>
    <?php endif; ?>
  </div>
</body>
</html>
