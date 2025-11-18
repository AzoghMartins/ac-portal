<?php
/**
 * Character profile view: shows overview data, gear, and the chronicle entries.
 *
 * @var array       $character
 * @var string|null $accountName
 * @var array       $gear
 * @var array|null  $progression
 * @var array|null  $viewer
 * @var bool        $isOwner
 * @var bool        $isStaff
 * @var bool        $canModerate
 * @var array       $chronicleEntries
 */

use App\WowHelper;

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

$avgIlvl = null;
if (!empty($gear)) {
    $sumIlvl = 0;
    $countIlvl = 0;
    foreach ($gear as $g) {
        if (isset($g['ItemLevel']) && is_numeric($g['ItemLevel'])) {
            $sumIlvl += (float)$g['ItemLevel'];
            $countIlvl++;
        }
    }
    if ($countIlvl > 0) {
        $avgIlvl = $sumIlvl / $countIlvl;
    }
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
            <th>Average iLvl</th>
            <td>
              <?php if ($avgIlvl !== null): ?>
                <?= number_format($avgIlvl, 1) ?>
              <?php else: ?>
                <span class="character-muted">Unknown</span>
              <?php endif; ?>
            </td>
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
              </tr>
            </thead>
            <tbody>
            <?php foreach ($gear as $g): ?>
              <?php
                $slotId   = (int)$g['slot'];
                $slotName = $slotNames[$slotId] ?? ('Slot '.$slotId);
                $itemName = $g['name'] ?? 'Unknown item';
                $quality  = $g['Quality']   ?? null;
                $reqLevel = $g['RequiredLevel'] ?? null;
                $itemLevel= $g['ItemLevel'] ?? null;
                $armor    = $g['armor'] ?? null;
                $dmgMin   = isset($g['dmg_min1']) ? (float)$g['dmg_min1'] : null;
                $dmgMax   = isset($g['dmg_max1']) ? (float)$g['dmg_max1'] : null;
                $delayMs  = isset($g['delay']) ? (int)$g['delay'] : null;
                $stats    = $g['stats'] ?? [];
                $socketColors = $g['socketColors'] ?? [];

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

                $statNames = [
                  3  => 'Agility',
                  4  => 'Strength',
                  5  => 'Intellect',
                  6  => 'Spirit',
                  7  => 'Stamina',
                  12 => 'Defense Rating',
                  13 => 'Dodge Rating',
                  14 => 'Parry Rating',
                  15 => 'Block Rating',
                  16 => 'Hit Rating (melee)',
                  17 => 'Hit Rating (ranged)',
                  18 => 'Hit Rating (spell)',
                  19 => 'Crit Rating (melee)',
                  20 => 'Crit Rating (ranged)',
                  21 => 'Crit Rating (spell)',
                  25 => 'Resilience',
                  26 => 'Haste Rating (melee)',
                  27 => 'Haste Rating (ranged)',
                  28 => 'Haste Rating (spell)',
                  30 => 'Hit Rating',
                  31 => 'Crit Rating',
                  32 => 'Haste Rating',
                  33 => 'Hit Avoidance',
                  35 => 'Resilience',
                  36 => 'Haste Rating',
                  38 => 'Attack Power',
                  41 => 'Spell Power',
                  42 => 'Health Regen',
                  43 => 'Spell Penetration',
                  44 => 'Block Value',
                ];

                $socketName = static function (int $color): string {
                    $map = [
                        1 => 'Meta',
                        2 => 'Red',
                        4 => 'Yellow',
                        8 => 'Blue',
                    ];
                    return $map[$color] ?? ('Socket '.$color);
                };

                $dps = null;
                if ($dmgMin !== null && $dmgMax !== null && $delayMs && $delayMs > 0) {
                    $avg = ($dmgMin + $dmgMax) / 2;
                    $dps = $avg / ($delayMs / 1000);
                }

                // CSS class based on quality (fallback to common if null)
                $qualityClass = 'quality-' . ($quality !== null ? (int)$quality : 1);

              ?>
              <tr>
                <td><?= htmlspecialchars($slotName) ?></td>
                <td class="item-cell <?= htmlspecialchars($qualityClass) ?>">
                  <span class="item-name"><?= htmlspecialchars($itemName) ?></span>
                    <div class="item-tooltip">
                      <div class="item-tooltip-name <?= htmlspecialchars($qualityClass) ?>">
                        <?= htmlspecialchars($itemName) ?>
                      </div>
                      <?php if ($itemLevel): ?>
                        <div class="item-tooltip-line">Item Level <?= (int)$itemLevel ?></div>
                      <?php endif; ?>
                      <?php if ($reqLevel !== null && (int)$reqLevel > 1): ?>
                        <div class="item-tooltip-line">
                          Requires level <?= (int)$reqLevel ?>
                        </div>
                      <?php endif; ?>
                      <?php if ($armor !== null && $armor > 0): ?>
                        <div class="item-tooltip-line"><?= (int)$armor ?> Armor</div>
                      <?php endif; ?>
                      <?php if ($dmgMin !== null && $dmgMax !== null && $delayMs): ?>
                        <div class="item-tooltip-line">
                          Damage: <?= number_format($dmgMin, 0) ?> - <?= number_format($dmgMax, 0) ?>
                        </div>
                        <div class="item-tooltip-line">
                          Speed: <?= number_format($delayMs / 1000, 2) ?> s
                        </div>
                        <?php if ($dps !== null): ?>
                          <div class="item-tooltip-line">
                            DPS: <?= number_format($dps, 1) ?>
                          </div>
                        <?php endif; ?>
                      <?php endif; ?>
                      <?php if (!empty($stats)): ?>
                        <?php foreach ($stats as $stat): ?>
                          <?php
                            $sType = (int)($stat['type'] ?? 0);
                            $sVal  = (int)($stat['value'] ?? 0);
                            $sName = $statNames[$sType] ?? ("Stat {$sType}");
                          ?>
                          <div class="item-tooltip-line">+<?= $sVal ?> <?= htmlspecialchars($sName) ?></div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <?php if (!empty($socketColors)): ?>
                        <div class="item-tooltip-line">
                          Sockets:
                          <?= htmlspecialchars(implode(', ', array_map($socketName, $socketColors))) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="card character-card character-card-full character-chronicle">
      <h2 class="section-title character-section-title">
        Chronicle of <?= htmlspecialchars($char['name']) ?>
      </h2>
      <?php if (!empty($chronicleEntries)): ?>
        <div class="character-chronicle-list">
          <?php foreach ($chronicleEntries as $entry): ?>
            <article class="character-chronicle-entry">
              <?php if (!empty($entry['created_at'])): ?>
                <p class="character-chronicle-entry-meta">
                  <?= htmlspecialchars($entry['created_at']) ?>
                </p>
              <?php endif; ?>
              <div class="character-chronicle-entry-text">
                <?php
                $paragraphs = preg_split('/\r?\n+/', (string)$entry['text']);
                foreach ($paragraphs as $paragraph):
                    $paragraph = trim($paragraph);
                    if ($paragraph === '') {
                        continue;
                    }
                ?>
                  <p><?= nl2br(htmlspecialchars($paragraph)) ?></p>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="character-empty">
          Chronicle entries have not been recorded for this hero yet.
        </p>
      <?php endif; ?>
    </section>
  </div>

  <p class="character-backlinks">
    <a href="/account">&larr; Back to My Account</a>
    <span class="character-backlinks-separator">|</span>
    <a href="/armory">Back to Armory</a>
  </p>
</div>

<script>
// Flip tooltips above/below based on viewport position to avoid overflow.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.item-table .item-cell').forEach(function (cell) {
    cell.addEventListener('mouseenter', adjustTooltip);
    cell.addEventListener('mousemove', adjustTooltip);
  });

  function adjustTooltip(event) {
    var cell = event.currentTarget;
    var tooltip = cell.querySelector('.item-tooltip');
    if (!tooltip) return;

    var rect = cell.getBoundingClientRect();
    var midY = rect.top + rect.height / 2;

    // Compare against the midpoint of the whole gear table (better than viewport).
    var table = cell.closest('table');
    var tableRect = table ? table.getBoundingClientRect() : null;
    var threshold = tableRect ? (tableRect.top + tableRect.height / 2) : (window.innerHeight / 2);

    if (midY < threshold) {
      tooltip.classList.add('tooltip-below');
    } else {
      tooltip.classList.remove('tooltip-below');
    }
  }
});
</script>
