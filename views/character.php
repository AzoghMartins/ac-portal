<?php
use App\WowHelper;

/** @var array       $character */
/** @var string|null $accountName */
/** @var array       $gear */
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

<style>
  .character-page {
    max-width: 1080px;
    margin: 0 auto;
    padding: 5.5rem 1.5rem 3rem; /* leave room for fixed header */
  }

  .character-header {
    margin-bottom: 2rem;
  }

  .character-title {
    margin: 0 0 0.25rem;
    font-size: 2.1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .character-title-icon {
    vertical-align: -4px;
    border-radius: 6px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.7);
  }

  .character-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #a8b6d4;
  }

  .character-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
  }

  .character-card {
    background: radial-gradient(circle at top left, #101728, #050914 60%);
    border-radius: 12px;
    border: 1px solid rgba(90, 140, 220, 0.4);
    box-shadow: 0 0 18px rgba(0, 0, 0, 0.7);
    padding: 1.25rem 1.3rem 1.1rem;
  }

  .character-card-full {
    grid-column: 1 / -1;
  }

  .character-section-title {
    margin-top: 0;
    margin-bottom: 0.9rem;
    font-size: 1.3rem;
  }

  .character-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }

  .character-table th,
  .character-table td {
    border-bottom: 1px solid #25324c;
    padding: 0.45rem 0.6rem;
    text-align: left;
  }

  .character-table th {
    width: 32%;
    font-weight: 600;
    color: #c3d4ff;
    white-space: nowrap;
  }

  .character-table tr:last-child th,
  .character-table tr:last-child td {
    border-bottom: none;
  }

  .character-inline-icon {
    vertical-align: -3px;
    margin-right: 6px;
    border-radius: 4px;
  }

  .character-muted {
    color: #7e8ca8;
  }

  .character-actions {
    margin-top: 1rem;
    border-top: 1px solid rgba(90, 140, 220, 0.35);
    padding-top: 0.8rem;
  }

  .character-actions-title {
    margin: 0 0 0.4rem;
    font-size: 0.95rem;
    color: #c3ddff;
  }

  .character-actions-list {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .character-actions-list li + li {
    margin-top: 0.25rem;
  }

  .character-link-strong {
    font-weight: 600;
  }

  .character-empty {
    margin: 0.4rem 0 0.2rem;
    font-size: 0.9rem;
    color: #a8b6d4;
  }

  .character-gear-table-wrap {
    overflow-x: auto;
  }

  .gear-table thead th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9eb3e6;
  }

  .gear-table tbody td {
    font-size: 0.88rem;
    white-space: nowrap;
  }

  /* Item cell + tooltip */

  .item-table .item-cell {
    position: relative;
    cursor: default;
  }

  .item-name {
    border-bottom: 1px dashed rgba(180, 200, 255, 0.35);
  }

  .item-tooltip {
    display: none;
    position: absolute;
    z-index: 20;
    left: 0;
    top: 100%;
    margin-top: 4px;

    min-width: 220px;
    max-width: 320px;
    padding: 0.55rem 0.7rem;

    background: radial-gradient(circle at top, #040611, #020309 60%);
    border-radius: 8px;
    border: 1px solid rgba(146, 164, 255, 0.8);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.85);
    font-size: 0.82rem;
  }

  .item-table .item-cell:hover .item-tooltip {
    display: block;
  }

  /* Quality colors */

  .quality-0 .item-name,
  .quality-0 .item-tooltip-name {
    color: #9d9d9d;
  }
  .quality-1 .item-name,
  .quality-1 .item-tooltip-name {
    color: #ffffff;
  }
  .quality-2 .item-name,
  .quality-2 .item-tooltip-name {
    color: #1eff00;
  }
  .quality-3 .item-name,
  .quality-3 .item-tooltip-name {
    color: #0070dd;
  }
  .quality-4 .item-name,
  .quality-4 .item-tooltip-name {
    color: #a335ee;
  }
  .quality-5 .item-name,
  .quality-5 .item-tooltip-name {
    color: #ff8000;
  }
  .quality-6 .item-name,
  .quality-6 .item-tooltip-name {
    color: #e6cc80;
  }
  .quality-7 .item-name,
  .quality-7 .item-tooltip-name {
    color: #00ccff;
  }

  .item-tooltip-name {
    font-weight: 600;
    margin-bottom: 4px;
  }

  .item-tooltip-line {
    color: #e0e0e0;
  }

  .character-backlinks {
    margin: 0;
    font-size: 0.9rem;
    color: #a8b6d4;
  }

  .character-backlinks a {
    color: #c9a34f;
  }

  .character-backlinks a:hover {
    color: #f0c86a;
  }

  .character-backlinks-separator {
    margin: 0 0.5rem;
    opacity: 0.7;
  }

  @media (max-width: 860px) {
    .character-layout {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 600px) {
    .character-page {
      padding: 5.5rem 1rem 2.5rem;
    }

    .character-title {
      font-size: 1.7rem;
    }

    .character-section-title {
      font-size: 1.15rem;
    }
  }
</style>
