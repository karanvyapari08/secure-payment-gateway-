<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
requireLogin();

$pdo = getDBConnection();
$userId = currentUserId();
$error = '';
$success = '';

$txnId = (int)($_GET['txn'] ?? $_POST['txn'] ?? 0);

if ($txnId <= 0) {
    die('Invalid transaction reference.');
}

// Always scope by user_id in the query itself -> mitigates IDOR
// (never fetch by id alone and check ownership afterward as an afterthought)
$stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
$stmt->execute([$txnId, $userId]);
$txn = $stmt->fetch();

if (!$txn) {
    http_response_code(404);
    die('Transaction not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $pdo->beginTransaction();
    try {
        // Re-fetch and lock inside the transaction to prevent race conditions
        // (e.g. double-submit clicking "Refund" twice quickly)
        $lockStmt = $pdo->prepare(
            'SELECT * FROM transactions WHERE id = ? AND user_id = ? FOR UPDATE'
        );
        $lockStmt->execute([$txnId, $userId]);
        $txn = $lockStmt->fetch();

        // Business logic checks -> prevent refund abuse
        if ($txn['type'] !== 'payment') {
            throw new RuntimeException('Only payments can be refunded.');
        }
        if ($txn['status'] !== 'success') {
            throw new RuntimeException('Only successful payments can be refunded.');
        }
        // Prevent double refund: check no existing refund already points to this txn
        $existingRefund = $pdo->prepare(
            'SELECT id FROM transactions WHERE related_transaction_id = ? AND type = "refund"'
        );
        $existingRefund->execute([$txnId]);
        if ($existingRefund->fetch()) {
            throw new RuntimeException('This transaction has already been refunded.');
        }
        // Refund window: 30 days
        if (strtotime($txn['created_at']) < strtotime('-30 days')) {
            throw new RuntimeException('Refund window (30 days) has expired for this transaction.');
        }

        $refundAmount = (float)$txn['amount']; // full-amount refund only, no arbitrary client amount
        $refId = generateReferenceId('RFD');

        $updateWallet = $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?');
        $updateWallet->execute([$refundAmount, $userId]);

        $insertRefund = $pdo->prepare(
            'INSERT INTO transactions (user_id, type, amount, status, reference_id, related_transaction_id, description)
             VALUES (?, "refund", ?, "success", ?, ?, ?)'
        );
        $insertRefund->execute([$userId, $refundAmount, $refId, $txnId, 'Refund for ' . $txn['reference_id']]);

        $markOriginal = $pdo->prepare('UPDATE transactions SET status = "refunded" WHERE id = ?');
        $markOriginal->execute([$txnId]);

        $pdo->commit();
        logAudit($userId, 'REFUND_SUCCESS', "Txn: $txnId, Ref: $refId");
        $success = "Refund processed successfully. Reference: $refId";
        $txn['status'] = 'refunded';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
        logAudit($userId, 'REFUND_FAILED', $error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Refund - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav><a href="transactions.php">&larr; Back to Transactions</a></nav>
    <h1>Request Refund</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <p>Reference: <strong><?= e($txn['reference_id']) ?></strong></p>
    <p>Amount: ₹<?= number_format((float)$txn['amount'], 2) ?></p>
    <p>Status: <span class="badge <?= e($txn['status']) ?>"><?= e(ucfirst($txn['status'])) ?></span></p>
    <?php if ($txn['status'] === 'success' && $txn['type'] === 'payment'): ?>
      <form method="POST" action="refund.php?txn=<?= (int)$txn['id'] ?>">
        <?= csrfField() ?>
        <input type="hidden" name="txn" value="<?= (int)$txn['id'] ?>">
        <button type="submit">Confirm Refund</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
