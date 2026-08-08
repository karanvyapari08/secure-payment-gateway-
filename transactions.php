<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireLogin();

$pdo = getDBConnection();
$userId = currentUserId();

// Pagination (avoids unbounded queries)
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $userId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Transactions - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container wide">
    <nav><a href="dashboard.php">&larr; Back to Dashboard</a></nav>
    <h1>Transaction History</h1>
    <table>
      <tr><th>Reference</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr>
      <?php foreach ($transactions as $t): ?>
        <tr>
          <td><?= e($t['reference_id']) ?></td>
          <td><?= e(ucfirst($t['type'])) ?></td>
          <td>₹<?= number_format((float)$t['amount'], 2) ?></td>
          <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td><?= e($t['created_at']) ?></td>
          <td>
            <?php if ($t['type'] === 'payment' && $t['status'] === 'success'): ?>
              <a href="refund.php?txn=<?= (int)$t['id'] ?>" style="color:#38bdf8;">Request Refund</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($transactions)): ?>
        <tr><td colspan="6">No transactions yet.</td></tr>
      <?php endif; ?>
    </table>
    <div style="margin-top:16px;">
      <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" style="color:#38bdf8;">&laquo; Prev</a><?php endif; ?>
      <a href="?page=<?= $page + 1 ?>" style="color:#38bdf8;margin-left:12px;">Next &raquo;</a>
    </div>
  </div>
</body>
</html>
