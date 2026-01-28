<?php
/**
 * Marks ledger view.
 *
 * @var array $rows
 * @var int $marksBalance
 */

$rows = $rows ?? [];
$marksBalance = isset($marksBalance) ? (int)$marksBalance : 0;
?>

<div class="shop-page">
  <header class="shop-header">
    <h1 class="shop-title">Marks Ledger</h1>
    <p class="shop-subtitle">A record of all Marks credits and debits on your account.</p>
  </header>

  <section class="shop-section">
    <div class="shop-balance-card">
      <div>
        <div class="shop-balance-label">Current Balance</div>
        <div class="shop-balance-value"><?= number_format($marksBalance) ?></div>
      </div>
      <div class="shop-links">
        <a class="shop-button" href="/shop">Back to Shop</a>
        <a class="shop-button" href="/shop/purchases">My Purchases</a>
      </div>
    </div>
  </section>

  <section class="shop-section">
    <div class="shop-table-wrap">
      <table class="shop-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Delta</th>
            <th>Reason</th>
            <th>Purchase</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="4">No ledger entries found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php
                $delta = (int)($row['delta'] ?? 0);
                $purchaseId = $row['purchase_id'] ?? null;
                $productName = $row['product_name'] ?? null;
              ?>
              <tr>
                <td><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></td>
                <td><?= ($delta >= 0 ? '+' : '') . number_format($delta) ?></td>
                <td><?= htmlspecialchars((string)($row['reason'] ?? '')) ?></td>
                <td>
                  <?php if ($purchaseId): ?>
                    #<?= (int)$purchaseId ?>
                    <?php if ($productName): ?>
                      — <?= htmlspecialchars($productName) ?>
                    <?php endif; ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
