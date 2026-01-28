<?php
/**
 * Purchases view.
 *
 * @var array $rows
 * @var int $marksBalance
 * @var int|null $newPurchaseId
 */

$rows = $rows ?? [];
$marksBalance = isset($marksBalance) ? (int)$marksBalance : 0;
$newPurchaseId = isset($newPurchaseId) ? (int)$newPurchaseId : null;
?>

<div class="shop-page">
  <header class="shop-header">
    <h1 class="shop-title">My Purchases</h1>
    <p class="shop-subtitle">Track your shop orders and fulfillment queue status.</p>
  </header>

  <?php if ($newPurchaseId): ?>
    <div class="shop-alert shop-alert--success">
      Purchase #<?= $newPurchaseId ?> received and queued for fulfillment.
    </div>
  <?php endif; ?>

  <section class="shop-section">
    <div class="shop-balance-card">
      <div>
        <div class="shop-balance-label">Current Balance</div>
        <div class="shop-balance-value"><?= number_format($marksBalance) ?></div>
      </div>
      <div class="shop-links">
        <a class="shop-button" href="/shop">Back to Shop</a>
        <a class="shop-button" href="/shop/ledger">View Ledger</a>
      </div>
    </div>
  </section>

  <section class="shop-section">
    <div class="shop-table-wrap">
      <table class="shop-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Product</th>
            <th>Character</th>
            <th>Price</th>
            <th>Payment</th>
            <th>Queue</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6">No purchases yet.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php
                $details = [];
                if (!empty($row['details'])) {
                    $decoded = json_decode((string)$row['details'], true);
                    if (is_array($decoded)) {
                        $details = $decoded;
                    }
                }
                $charName = $details['character']['name'] ?? null;
                $charGuid = $row['character_guid'] ?? null;
                $tierNote = '';
                if (!empty($details['tier_skip']['target_tier'])) {
                    $tierNote = ' → Tier ' . (int)$details['tier_skip']['target_tier'];
                }
              ?>
              <tr>
                <td><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></td>
                <td>
                  <?= htmlspecialchars((string)($row['product_name'] ?? $row['sku'] ?? '')) ?>
                  <?php if ($tierNote): ?>
                    <span class="muted"><?= htmlspecialchars($tierNote) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($charName): ?>
                    <?= htmlspecialchars((string)$charName) ?>
                  <?php elseif ($charGuid): ?>
                    #<?= (int)$charGuid ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= number_format((int)($row['price_marks'] ?? 0)) ?> Marks</td>
                <td><?= htmlspecialchars((string)($row['status'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['fulfillment_status'] ?? 'queued')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
