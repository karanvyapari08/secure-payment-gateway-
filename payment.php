<?php
require_once __DIR__ . '/includes/security_headers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
requireLogin();

$pdo = getDBConnection();
$error = '';
$success = '';
$userId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $amount      = $_POST['amount'] ?? '';
    $description = trim($_POST['description'] ?? '');
    // Idempotency key from the client (e.g. generated once per form load)
    // -> mitigates duplicate submission / replay attacks
    $idempotencyKey = $_POST['idempotency_key'] ?? '';

    if (!isValidAmount($amount)) {
        $error = 'Enter a valid amount between 0.01 and 1,000,000.';
    } elseif ($idempotencyKey === '' || !preg_match('/^[a-f0-9]{32,64}$/i', $idempotencyKey)) {
        $error = 'Invalid request. Please reload the page and try again.';
    } else {
        $amount = round((float)$amount, 2);

        // Check idempotency key hasn't been used before (replay protection)
        $dupCheck = $pdo->prepare(
            "SELECT id FROM transactions WHERE user_id = ? AND reference_id = ?"
        );
        $refId = 'IDEMP-' . strtoupper($idempotencyKey);
        $dupCheck->execute([$userId, $refId]);

        if ($dupCheck->fetch()) {
            $error = 'This payment was already submitted.';
        } else {
            $pdo->beginTransaction();
            try {
                // Lock the wallet row for update -> prevents race conditions
                // on concurrent payment requests (business-logic safety)
                $lockStmt = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE');
                $lockStmt->execute([$userId]);
                $wallet = $lockStmt->fetch();

                if (!$wallet || (float)$wallet['balance'] < $amount) {
                    throw new RuntimeException('Insufficient wallet balance.');
                }

                $updateWallet = $pdo->prepare(
                    'UPDATE wallets SET balance = balance - ? WHERE user_id = ?'
                );
                $updateWallet->execute([$amount, $userId]);

                $insertTxn = $pdo->prepare(
                    'INSERT INTO transactions (user_id, type, amount, status, reference_id, description)
                     VALUES (?, "payment", ?, "success", ?, ?)'
                );
                $insertTxn->execute([$userId, $amount, $refId, $description]);

                $pdo->commit();
                logAudit($userId, 'PAYMENT_SUCCESS', "Amount: $amount, Ref: $refId");
                $success = "Payment of ₹$amount processed successfully. Reference: $refId";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage() === 'Insufficient wallet balance.'
                    ? $e->getMessage()
                    : 'Payment failed. Please try again.';
                logAudit($userId, 'PAYMENT_FAILED', $error);
            }
        }
    }
}

$freshIdempotencyKey = bin2hex(random_bytes(20));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Make Payment - Secure Payment Gateway</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav><a href="dashboard.php">&larr; Back to Dashboard</a></nav>
    <h1>Make a Payment</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <form method="POST" action="payment.php">
      <?= csrfField() ?>
      <input type="hidden" name="idempotency_key" value="<?= e($freshIdempotencyKey) ?>">
      <label>Amount (₹)</label>
      <input type="number" step="0.01" min="0.01" max="1000000" name="amount" required>
      <label>Description</label>
      <input type="text" name="description" maxlength="255">
      <button type="submit">Pay</button>
    </form>
  </div>
</body>
</html>
