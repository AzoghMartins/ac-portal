<?php
/**
 * Shop storefront view.
 *
 * @var array $productsByCategory
 * @var int   $marksBalance
 * @var int|null $selectedGuid
 */

$productsByCategory = $productsByCategory ?? [];
$marksBalance = isset($marksBalance) ? (int)$marksBalance : 0;
$selectedGuid = isset($selectedGuid) ? (int)$selectedGuid : null;
?>

<div class="shop-page">
  <header class="shop-header">
    <h1 class="shop-title">Shop</h1>
    <p class="shop-subtitle">
      Spend Marks on progression boosts and character upgrades. Fulfillment is queued for staff review.
    </p>
  </header>

  <section class="shop-section">
    <div class="shop-balance-card">
      <div>
        <div class="shop-balance-label">Marks Balance</div>
        <div class="shop-balance-value"><?= number_format($marksBalance) ?></div>
      </div>
      <div class="shop-links">
        <a class="shop-button" href="/shop/ledger">View Ledger</a>
        <a class="shop-button" href="/shop/purchases">My Purchases</a>
      </div>
    </div>
  </section>

  <?php if (empty($productsByCategory)): ?>
    <p class="account-subtitle">No shop products are configured yet.</p>
  <?php else: ?>
    <?php foreach ($productsByCategory as $category => $products): ?>
      <section class="shop-section">
        <h2 class="section-title shop-section-title"><?= htmlspecialchars($category) ?></h2>
        <div class="shop-products-grid">
          <?php foreach ($products as $product): ?>
            <?php
              $sku = $product['sku'] ?? '';
              $link = '/shop/product/' . rawurlencode($sku);
              if ($selectedGuid) {
                  $link .= '?guid=' . $selectedGuid;
              }
              $price = ($product['price_type'] ?? '') === 'fixed'
                ? number_format((int)($product['price_marks'] ?? 0)) . ' Marks'
                : 'Variable';
              $scope = ucfirst((string)($product['scope'] ?? 'character'));
            ?>
            <div class="shop-product-card">
              <h3 class="shop-product-name"><?= htmlspecialchars($product['name'] ?? $sku) ?></h3>
              <?php
                $cardDesc = $product['description'] ?? '';
                if (($product['sku'] ?? '') === 'TIER_SKIP') {
                    $cardDesc = 'Spend Marks to skip selected pre-Wrath Tier Locks and advance through earlier progression.';
                }
              ?>
              <p class="shop-product-desc"><?= htmlspecialchars($cardDesc) ?></p>
              <div class="shop-product-meta">
                <span class="shop-product-price"><?= htmlspecialchars($price) ?></span>
                <span class="shop-product-scope"><?= htmlspecialchars($scope) ?> Scope</span>
              </div>
              <div class="shop-product-action">
                <a class="shop-button" href="<?= htmlspecialchars($link) ?>">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
