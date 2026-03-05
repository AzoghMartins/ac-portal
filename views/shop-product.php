<?php
/**
 * Shop product detail view.
 *
 * @var array $product
 * @var array $characters
 * @var array|null $selectedCharacter
 * @var string|null $targetTier
 * @var array|null $preview
 * @var string|null $error
 * @var string|null $success
 * @var int $marksBalance
 * @var int $maxTierSkip
 * @var array $tierObjectives
 * @var array $tierTotals
 * @var array $orderedTiers
 * @var string|null $nextProgressionLabel
 */

$product = $product ?? [];
$characters = $characters ?? [];
$selectedCharacter = $selectedCharacter ?? null;
$targetTier = $targetTier ?? null;
$preview = $preview ?? null;
$marksBalance = isset($marksBalance) ? (int)$marksBalance : 0;
$maxTierSkip = isset($maxTierSkip) ? (int)$maxTierSkip : 12;
$tierObjectives = $tierObjectives ?? [];
$tierTotals = $tierTotals ?? [];
$orderedTiers = $orderedTiers ?? [];
$nextProgressionLabel = $nextProgressionLabel ?? null;
$tierOptions = [];
$currentKey = $selectedCharacter ? (string)$selectedCharacter['progression_state'] : null;
$currentIndex = ($currentKey !== null && $orderedTiers) ? array_search($currentKey, $orderedTiers, true) : null;

if ($selectedCharacter) {
  if ($currentIndex === null) {
      $tierOptions = [];
  } else {
  foreach ($tierObjectives as $row) {
      $tierValue = (string)($row['tier'] ?? '');
      if ($tierValue === '') {
          continue;
      }
      if (empty($row['selectable'])) {
          continue;
      }
      $tierIndex = $orderedTiers ? array_search($tierValue, $orderedTiers, true) : null;
      if ($currentIndex !== null && $tierIndex !== null && $tierIndex < $currentIndex) {
          continue;
      }
      $requiredLevel = ((int)$tierValue <= 7) ? 60 : 70;
      if ((int)($selectedCharacter['level'] ?? 1) < $requiredLevel) {
          continue;
      }
      $totalCost = $tierTotals[$tierValue] ?? null;
      if ($totalCost === null) {
          continue;
      }
      $tierOptions[] = [
          'tier' => $tierValue,
          'objective' => (string)($row['objective'] ?? ''),
          'total' => (int)$totalCost,
      ];
  }
  }
}

$sku = $product['sku'] ?? '';
$productSku = (string)$sku;
$isTierSkip = ($product['price_type'] ?? '') === 'tier_skip';
$isBoost60 = $productSku === 'BOOST_60';
$isBoost70 = $productSku === 'BOOST_70';
$priceDisplay = $isTierSkip
    ? 'Variable'
    : number_format((int)($product['price_marks'] ?? 0)) . ' Marks';
$buttonTotal = null;
if ($isTierSkip) {
    if ($preview && isset($preview['price'])) {
        $buttonTotal = number_format((int)$preview['price']) . ' Marks';
    }
} else {
    if (isset($product['price_marks'])) {
        $buttonTotal = number_format((int)$product['price_marks']) . ' Marks';
    }
}
$showPurchaseButton = !$isTierSkip || ($targetTier !== null && $targetTier !== '');
?>

<div class="shop-page">
  <header class="shop-header">
    <h1 class="shop-title"><?= htmlspecialchars($product['name'] ?? 'Shop Product') ?></h1>
    <p class="shop-subtitle"><?= htmlspecialchars($product['description'] ?? '') ?></p>
  </header>

  <?php if (!empty($error)): ?>
    <div class="shop-alert shop-alert--error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="shop-alert shop-alert--success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <section class="shop-section">
    <div class="shop-detail-card">
      <?php if ($isTierSkip): ?>
        <h2 class="section-title shop-section-title">Tier Completion (Tier 1-12)</h2>
        <div class="intro-text">
          <p class="section-lead">
            Skip selected Tier Locks from Tier 1 through Tier 12 by spending Marks. Tier 0 and Tier 7.5 are handled as separate standalone shop products.
          </p>
          <p>
            Tier targets up to Tier 7 require level 60. Tier targets from Tier 8 through Tier 12 require level 70.
          </p>
          <p>
            Tier 4 currently has no Marks cost while the server-core progression issue remains unresolved.
          </p>
        </div>
      <?php elseif ($isBoost60): ?>
        <h2 class="section-title shop-section-title">Tier 0 Boost (Level 60 + Gear)</h2>
        <div class="intro-text">
          <p class="section-lead">
            This product completes the Tier 0 boost step for one character: level 60 setup with starter gear.
          </p>
          <p>
            It is only available for characters below level 60.
          </p>
        </div>
      <?php elseif ($isBoost70): ?>
        <h2 class="section-title shop-section-title">Tier 7.5 Boost (Level 70 + Gear)</h2>
        <div class="intro-text">
          <p class="section-lead">
            This product applies the Tier 7.5 boost step for one character: level 70 setup with starter gear.
          </p>
          <p>
            It requires at least Tier 7 progression and is only available below level 70.
          </p>
        </div>
      <?php else: ?>
        <h2 class="section-title shop-section-title">Product Details</h2>
      <?php endif; ?>

      <?php if ($selectedCharacter): ?>
        <div class="shop-kv" style="margin-top: 1rem;">
          <div><span>Character:</span> <?= htmlspecialchars($selectedCharacter['name'] ?? '') ?></div>
          <div><span>Level:</span> <?= (int)($selectedCharacter['level'] ?? 0) ?></div>
          <div><span>Current Progression:</span> <?= htmlspecialchars($selectedCharacter['progression_label'] ?? '') ?></div>
          <?php if ($nextProgressionLabel): ?>
            <div><span>Progression After Purchase:</span> <?= htmlspecialchars($nextProgressionLabel) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($isTierSkip): ?>
        <div class="shop-kv" style="margin-top: 1rem;">
          <div><span>Tier Completion Pricing:</span>
            <?php if ($preview): ?>
              <?= number_format((int)($preview['price'] ?? 0)) ?> Marks
            <?php else: ?>
              Select a character and desired tier to preview pricing.
            <?php endif; ?>
          </div>
          <div><span>Level Requirements:</span> Tier 1-7 requires level 60. Tier 8-12 requires level 70.</div>
        </div>

        <?php if ($preview && !empty($preview['breakdown'])): ?>
          <ul class="shop-inline-list" style="margin-top: 0.6rem;">
            <?php foreach ($preview['breakdown'] as $row): ?>
              <?php
                $cost = isset($row['cost']) ? (int)$row['cost'] : 0;
                $objective = isset($row['objective']) ? (string)$row['objective'] : '';
                $note = $row['note'] ?? '';
              ?>
              <li>
                <?= htmlspecialchars($objective) ?> — <?= number_format($cost) ?> Marks
                <?= $note ? ' • ' . htmlspecialchars($note) : '' ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?php else: ?>
        <div class="shop-kv" style="margin-top: 1rem;">
          <div><span>Price:</span> <?= htmlspecialchars($priceDisplay) ?></div>
          <?php if ($isBoost60): ?>
            <div><span>Requirement:</span> Character must be below level 60.</div>
          <?php elseif ($isBoost70): ?>
            <div><span>Requirement:</span> Character must be Tier 7+ and below level 70.</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="/shop/buy" class="shop-form">
        <input type="hidden" name="sku" value="<?= htmlspecialchars($sku) ?>">

        <?php if (($product['scope'] ?? '') === 'character'): ?>
          <label>
            Character
            <select name="character_guid" required>
              <option value="">Select a character</option>
              <?php foreach ($characters as $char): ?>
                <?php
                  $guid = (int)($char['guid'] ?? 0);
                  $name = $char['name'] ?? '';
                  $level = (int)($char['level'] ?? 0);
                  $selected = $selectedCharacter && (int)$selectedCharacter['guid'] === $guid;
                ?>
                <option value="<?= $guid ?>" <?= $selected ? 'selected' : '' ?>>
                  <?= htmlspecialchars($name) ?> (Level <?= $level ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>

        <?php if ($isTierSkip && $selectedCharacter && !empty($tierOptions)): ?>
          <label>
            Desired Tier to Auto Complete (Max <?= $maxTierSkip ?>)
            <select name="target_tier" id="tier-target-select" required>
              <option value="">Select tier</option>
              <?php foreach ($tierOptions as $opt): ?>
                <?php
                  $tierValue = (string)$opt['tier'];
                  $label = $opt['objective'] . ' (Total ' . number_format((int)$opt['total']) . ' Marks)';
                  $selected = $targetTier !== null && (string)$targetTier === $tierValue;
                ?>
                <option value="<?= htmlspecialchars($tierValue) ?>" <?= $selected ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php elseif ($isTierSkip && $selectedCharacter): ?>
          <p class="muted">No tier advancement options are available for this character.</p>
        <?php elseif ($isTierSkip): ?>
          <p class="muted">Select a character to load available tier completion options.</p>
        <?php endif; ?>

        <?php if ($showPurchaseButton): ?>
          <div class="shop-product-action">
            <button type="submit" class="shop-button">
              Purchase
              <?php if ($buttonTotal): ?>
                <span class="shop-cost">Total <?= htmlspecialchars($buttonTotal) ?></span>
              <?php endif; ?>
            </button>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </section>

  <div class="shop-meta-links">
    <a class="shop-button" href="/shop">← Back to Shop</a>
    <a class="shop-button" href="/shop/purchases">My Purchases</a>
  </div>
</div>

<?php if ($isTierSkip): ?>
<script>
(function () {
  var charSelect = document.querySelector('select[name=\"character_guid\"]');
  var tierSelect = document.getElementById('tier-target-select');

  if (charSelect) {
    charSelect.addEventListener('change', function () {
      if (!this.value) return;
      var url = '/shop/product/<?= htmlspecialchars(rawurlencode($sku)) ?>?guid=' + encodeURIComponent(this.value);
      window.location.href = url;
    });
  }

  if (tierSelect) {
    tierSelect.addEventListener('change', function () {
      if (!this.value || !charSelect || !charSelect.value) return;
      var params = new URLSearchParams();
      params.set('guid', charSelect.value);
      params.set('target_tier', this.value);
      window.location.href = '/shop/product/<?= htmlspecialchars(rawurlencode($sku)) ?>?' + params.toString();
    });
  }
})();
</script>
<?php endif; ?>
