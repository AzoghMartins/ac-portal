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
$nextProgressionLabel = $nextProgressionLabel ?? null;

$sku = $product['sku'] ?? '';
$isTierSkip = ($product['price_type'] ?? '') === 'tier_skip';
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
      <h2 class="section-title shop-section-title">Tier Completion (Individual Progression Skip)</h2>
      <div class="intro-text">
        <p class="section-lead">
          Tier Completion allows a character to bypass selected Tier Locks in the Individual Progression system by spending Marks, reducing the need to replay earlier progression content on alts or late-starting characters. The feature is designed as a convenience option that preserves progression integrity rather than replacing gameplay.
        </p>
        <p>
          Using Tier Completion, a character may automatically complete Tier Locks from Vanilla (Tier 0–7) and The Burning Crusade (Tier 8–12). Each Tier has a fixed Marks cost that reflects the time and effort normally required to complete it. A full skip from Tier 0 through Tier 12 costs 1000 Marks.
        </p>
        <p>
          Tiers that are auto-completed through this feature do not generate Marks for the account. Marks are only awarded for Tiers completed through normal gameplay.
        </p>
        <p>
          The cost of a full Tier 0–12 skip is intentionally set to match the total Marks earned by progressing naturally through all 17 Tiers of the game. Completing the full progression once allows a player to fast-track one character through pre-Wrath content, while spending Marks on a character reduces how much progression can be skipped elsewhere.
        </p>
        <p>
          Tier Completion is strictly limited to earlier content. Wrath of the Lich King progression (Tier 13–17) cannot be skipped and must always be played as intended. All characters are required to level from 70 to 80 and progress through Wrath raid tiers normally. No Wrath levels, gear, reputation, or raid achievements are granted through this feature.
        </p>
        <p>
          Tier Completion exists to reduce repetition, support alt play, and provide a transparent alternative to replaying older Tier Locks—without trivializing endgame progression.
        </p>
      </div>

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

        <?php if ($isTierSkip && $selectedCharacter): ?>
          <label>
            Desired Tier to Auto Complete (Max <?= $maxTierSkip ?>)
            <select name="target_tier" id="tier-target-select" required>
              <option value="">Select tier</option>
              <?php foreach ($tierObjectives as $row): ?>
                <?php
                  $tierValue = (string)($row['tier'] ?? '');
                  $selectable = !empty($row['selectable']);
                  $totalCost = $tierTotals[$tierValue] ?? null;
                  $available = $totalCost !== null;
                  $labelParts = [];
                  $labelParts[] = (string)($row['objective'] ?? '');
                  if ($available) {
                      $labelParts[] = '(Total ' . number_format((int)$totalCost) . ' Marks)';
                  } else {
                      $labelParts[] = '(Unavailable)';
                  }
                  $label = implode(' ', $labelParts);
                  $selected = $selectable && $available && $targetTier !== null && (string)$targetTier === $tierValue;
                ?>
                <option value="<?= ($selectable && $available) ? htmlspecialchars($tierValue) : '' ?>" <?= $selected ? 'selected' : '' ?> <?= ($selectable && $available) ? '' : 'disabled' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
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
