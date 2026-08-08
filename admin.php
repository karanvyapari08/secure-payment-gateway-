require_once 'includes/security_headers.php';
<?php
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireRole('admin'); // Server-side RBAC check -> mitigates Broken Access Control

$pdo = getDBConnection();

$users = $pdo->query(
    'SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC'
)->fetchAll();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$txnStmt = $pdo->prepare(
    'SELECT t.*, u.email FROM transactions t
     JOIN users u ON u.id = t.user_id
     ORDER BY t.created_at DESC LIMIT ? OFFSET ?'
);
$txnStmt->bindValue(1, $perPage, PDO::PARAM_INT);
$txnStmt->bindValue(2, $offset, PDO::PARAM_INT);
$txnStmt->execute();
$transactions = $txnStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container wide">
    <nav><a href="dashboard.php">&larr; Back to Dashboard</a></nav>
    <h1>Admin Panel</h1>

    <h2>Users</h2>
    <table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['role']) ?></td>
          <td><?= e($u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

    <h2>All Transactions</h2>
    <table>
      <tr><th>Reference</th><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>
      <?php foreach ($transactions as $t): ?>
        <tr>
          <td><?= e($t['reference_id']) ?></td>
          <td><?= e($t['email']) ?></td>
          <td><?= e(ucfirst($t['type'])) ?></td>
          <td>₹<?= number_format((float)$t['amount'], 2) ?></td>
          <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td><?= e($t['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <div style="margin-top:16px;">
      <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" style="color:#38bdf8;">&laquo; Prev</a><?php endif; ?>
      <a href="?page=<?= $page + 1 ?>" style="color:#38bdf8;margin-left:12px;">Next &raquo;</a>
    </div>
  </div>
</body>
</html>
