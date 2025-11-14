<?php
use App\WowHelper;

/** @var array       $character */
/** @var string|null $accountName */
/** @var array       $gear */
/** @var array|null  $progression */
/** @var array|null  $viewer */
/** @var bool        $isOwner */
/** @var bool        $isStaff */
/** @var bool        $canModerate */

$char = $character;

$classId = (int)$char['class'];
$raceId  = (int)$char['race'];
$gender  = isset($char['gender']) ? (int)$char['gender'] : null;

$className = WowHelper::className($classId);
$raceName  = WowHelper::raceName($raceId);
$classIcon = WowHelper::classIcon($classId);
$raceIcon  = WowHelper::raceIcon($raceId, $gender);

$progressionState = $progression['state'] ?? null;
$progressionLabel = $progression['label'] ?? null;

// Gender label
$genderLabel = null;
if ($gender === 0) {
    $genderLabel = 'Male';
} elseif ($gender === 1) {
    $genderLabel = 'Female';
}

// Very rough faction guess
$faction = null;
if (in_array($raceId, [1, 3, 4, 7, 11], true)) {
    $faction = 'Alliance';
} elseif (in_array($raceId, [2, 5, 6, 8, 10], true)) {
    $faction = 'Horde';
}

// Totaltime → hours
$hoursPlayed = number_format(((int)$char['totaltime']) / 3600, 1);

// Copper → gold/silver/copper
$moneyCopper = (int)$char['money'];
$gold  = intdiv($moneyCopper, 10000);
$rem   = $moneyCopper % 10000;
$silver = intdiv($rem, 100);
$copper = $rem % 100;

$mapId  = (int)$char['map'];
$zoneId = (int)$char['zone'];

$zoneName = \App\WowHelper::zoneName($zoneId);
$mapName  = \App\WowHelper::mapName($mapId);

// Base text shown to everyone: prefer zone name, then map name, then raw IDs
if ($zoneName !== null) {
    $locationBase = $zoneName;
} elseif ($mapName !== null) {
    $locationBase = $mapName;
} else {
    $locationBase = sprintf('Map %d, Zone %d', $mapId, $zoneId);
}

// For owner / staff, append exact coordinates for debugging.
// For normal visitors, they just see "Ironforge", "Stormwind City", etc.
$location = $locationBase;

if ($isOwner || $isStaff) {
    $location .= sprintf(
        ' (%.1f, %.1f, %.1f)',
        (float)$char['position_x'],
        (float)$char['position_y'],
        (float)$char['position_z']
    );
}


// Correct 3.3.5a equipment slot mapping (0–18)
$slotNames = [
     0 => 'Head',
     1 => 'Neck',
     2 => 'Shoulder',
     3 => 'Shirt',
     4 => 'Chest',
     5 => 'Waist',
     6 => 'Legs',
     7 => 'Feet',
     8 => 'Wrist',
     9 => 'Hands',
    10 => 'Finger 1',
    11 => 'Finger 2',
    12 => 'Trinket 1',
    13 => 'Trinket 2',
    14 => 'Back',
    15 => 'Main Hand',
    16 => 'Off Hand',
    17 => 'Ranged / Relic',
    18 => 'Tabard',
];
?>

<div class="character-page">
  <header class="character-header">
    <h1 class="character-title">
      <img
        src="<?= htmlspecialchars($classIcon) ?>"
        alt="<?= htmlspecialchars($className) ?>"
        width="28"
        height="28"
        class="character-title-icon"
      >
      <?= htmlspecialchars($char['name']) ?>
    </h1>
    <p class="character-subtitle">
      Level <?= (int)$char['level'] ?>
      <?= htmlspecialchars($raceName) ?>
      <?= htmlspecialchars($className) ?>
      <?php if ($genderLabel): ?>
        • <?= htmlspecialchars($genderLabel) ?>
      <?php endif; ?>
      <?php if ($faction): ?>
        • <?= htmlspecialchars($faction) ?>
      <?php endif; ?>
    </p>
  </header>

  <div class="character-layout">
    <section class="card character-card">
      <h2 class="section-title character-section-title">Overview</h2>
      <table class="character-table">
        <tbody>
        <?php if ($isOwner || $isStaff): ?>
          <tr>
            <th>Account</th>
            <td>
              <?php if ($accountName): ?>
                <?= htmlspecialchars($accountName) ?>
              <?php else: ?>
                <span class="character-muted">Unknown</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>
          <tr>
            <th>Race</th>
            <td>
              <img
                src="<?= htmlspecialchars($raceIcon) ?>"
                alt="<?= htmlspecialchars($raceName) ?>"
                width="18"
                height="18"
                class="character-inline-icon"
              >
              <?= htmlspecialchars($raceName) ?>
            </td>
          </tr>
          <tr>
            <th>Class</th>
            <td>
              <img
                src="<?= htmlspecialchars($classIcon) ?>"
                alt="<?= htmlspecialchars($className) ?>"
                width="18"
                height="18"
                class="character-inline-icon"
              >
              <?= htmlspecialchars($className) ?>
            </td>
          </tr>
          <tr>
            <th>Level</th>
            <td><?= (int)$char['level'] ?></td>
          </tr>
          <tr>
            <th>Progression Tier</th>
            <td>
              <?php if ($progressionLabel !== null): ?>
                <?= htmlspecialchars($progressionLabel) ?>
              <?php else: ?>
                <span class="character-muted">Unknown</span>
              <?php endif; ?>
            </td>
          </tr>

        </tbody>
      </table>
    </section>

    <section class="card character-card">
      <h2 class="section-title character-section-title">Progress</h2>
      <table class="character-table">
        <tbody>
          <tr>
            <th>Time Played</th>
            <td><?= $hoursPlayed ?> hours</td>
          </tr>
          <?php if ($isOwner || $isStaff): ?>
          <tr>
            <th>Money</th>
            <td><?= $gold ?>g <?= $silver ?>s <?= $copper ?>c</td>
          </tr>
          <?php endif; ?>
          <tr>
            <th>Online</th>
            <td><?= ((int)$char['online'] === 1) ? 'Yes' : 'No' ?></td>
          </tr>
          <tr>
            <th>Location</th>
            <td><?= htmlspecialchars($location) ?></td>
          </tr>
        </tbody>
      </table>

      <?php if ($isOwner || $canModerate): ?>
        <div class="character-actions">
          <h3 class="character-actions-title">Character Actions</h3>
          <ul class="character-actions-list">
            <li>
              <a href="/shop?guid=<?= (int)$char['guid'] ?>" class="character-link-strong">
                Open Shop for this character
              </a>
            </li>
            <li>
              <a href="/shop?guid=<?= (int)$char['guid'] ?>&tab=cosmetics">
                Cosmetic items &amp; appearance
              </a>
            </li>
          </ul>
        </div>
      <?php endif; ?>
    </section>

    <section class="card character-card character-card-full">
      <h2 class="section-title character-section-title">Gear</h2>

      <?php if (empty($gear)): ?>
        <p class="character-empty">
          No equipment is currently equipped on this character.
        </p>
      <?php else: ?>
        <div class="character-gear-table-wrap">
          <table class="character-table gear-table item-table">
            <thead>
              <tr>
                <th>Slot</th>
                <th>Item</th>
                <th>iLvl</th>
                <th>Quality</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($gear as $g): ?>
              <?php
                $slotId   = (int)$g['slot'];
                $slotName = $slotNames[$slotId] ?? ('Slot '.$slotId);
                $itemName = $g['name'] ?? 'Unknown item';
                $ilvl     = $g['ItemLevel'] ?? null;
                $quality  = $g['Quality']   ?? null;
                $invType  = $g['InventoryType'] ?? null;
                $reqLevel = $g['RequiredLevel'] ?? null;

                $qualityNames = [
                  0 => 'Poor',
                  1 => 'Common',
                  2 => 'Uncommon',
                  3 => 'Rare',
                  4 => 'Epic',
                  5 => 'Legendary',
                  6 => 'Artifact',
                  7 => 'Heirloom',
                ];
                $qualityLabel = isset($qualityNames[$quality]) ? $qualityNames[$quality] : (string)$quality;

                // CSS class based on quality (fallback to common if null)
                $qualityClass = 'quality-' . ($quality !== null ? (int)$quality : 1);

                // Simple InventoryType label for now
                $invTypeText = $invType !== null ? (string)(int)$invType : '-';
              ?>
              <tr>
                <td><?= htmlspecialchars($slotName) ?></td>
                <td class="item-cell <?= htmlspecialchars($qualityClass) ?>">
                  <span class="item-name"><?= htmlspecialchars($itemName) ?></span>
                  <div class="item-tooltip">
                    <div class="item-tooltip-name <?= htmlspecialchars($qualityClass) ?>">
                      <?= htmlspecialchars($itemName) ?>
                    </div>
                    <?php if ($reqLevel !== null && (int)$reqLevel > 1): ?>
                      <div class="item-tooltip-line">
                        Requires level <?= (int)$reqLevel ?>
                      </div>
                    <?php endif; ?>
                    <div class="item-tooltip-line">Slot: <?= htmlspecialchars($slotName) ?></div>
                    <?php if ($qualityLabel !== ''): ?>
                      <div class="item-tooltip-line">Quality: <?= htmlspecialchars($qualityLabel) ?></div>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= $ilvl !== null ? (int)$ilvl : '-' ?></td>
                <td><?= htmlspecialchars($qualityLabel) ?></td>
                <td><?= $invTypeText ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <p class="character-backlinks">
    <a href="/account">&larr; Back to My Account</a>
    <span class="character-backlinks-separator">|</span>
    <a href="/armory">Back to Armory</a>
  </p>
</div>
