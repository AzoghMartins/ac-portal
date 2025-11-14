<?php
use App\WowHelper;

/** @var array $character */
/** @var string|null $accountName */
/** @var array $gear */
/** @var array|null $viewer */
/** @var bool $isOwner */
/** @var bool $isStaff */
/** @var bool $canModerate */

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
if ($gender === 0) $genderLabel = 'Male';
elseif ($gender === 1) $genderLabel = 'Female';

// Very rough faction guess
$faction = null;
if (in_array($raceId, [1, 3, 4, 7, 11], true)) {
    $faction = 'Alliance';
} elseif (in_array($raceId, [2, 5, 6, 8, 10], true)) {
    $faction = 'Horde';
}

// Totaltime → hours
$hoursPlayed = number_format(((int)$char['totaltime']) / 3600, 1);

// Gold from copper
$moneyCopper = (int)$char['money'];
$gold  = intdiv($moneyCopper, 10000);
$rem   = $moneyCopper % 10000;
$silver = intdiv($rem, 100);
$copper = $rem % 100;

$location = sprintf(
    'Map %d, Zone %d (%.1f, %.1f, %.1f)',
    (int)$char['map'],
    (int)$char['zone'],
    (float)$char['position_x'],
    (float)$char['position_y'],
    (float)$char['position_z']
);

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

<h1>
  <img src="<?= htmlspecialchars($classIcon) ?>"
       alt="<?= htmlspecialchars($className) ?>"
       width="24" height="24"
       style="vertical-align:-4px;margin-right:8px">
  <?= htmlspecialchars($char['name']) ?>
</h1>

<section class="card">
  <h2>Overview</h2>
  <table>
    <tbody>
      <?php if ($isOwner || $isStaff): ?>
      <tr>
        <th style="text-align:left">Account</th>
        <td>
          <?php if ($accountName): ?>
            <?= htmlspecialchars($accountName) ?> (ID <?= (int)$char['account'] ?>)
          <?php else: ?>
            ID <?= (int)$char['account'] ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endif; ?>
      <tr>
        <th style="text-align:left">Level</th>
        <td><?= (int)$char['level'] ?></td>
      </tr>
      <tr>
        <th style="text-align:left">Class</th>
        <td>
          <img src="<?= htmlspecialchars($classIcon) ?>"
               alt="<?= htmlspecialchars($className) ?>"
               width="18" height="18"
               style="vertical-align:-3px;margin-right:6px">
          <?= htmlspecialchars($className) ?>
        </td>
      </tr>
      <tr>
        <th style="text-align:left">Race</th>
        <td>
          <img src="<?= htmlspecialchars($raceIcon) ?>"
               alt="<?= htmlspecialchars($raceName) ?>"
               width="18" height="18"
               style="vertical-align:-3px;margin-right:6px">
          <?= htmlspecialchars($raceName) ?>
          <?php if ($genderLabel): ?>
            (<?= htmlspecialchars($genderLabel) ?>)
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th style="text-align:left">Faction</th>
        <td><?= $faction ? htmlspecialchars($faction) : '<em>Unknown</em>' ?></td>
      </tr>
      <tr>
        <th style="text-align:left">GUID</th>
        <td><?= (int)$char['guid'] ?></td>
      </tr>
    </tbody>
  </table>
</section>

<section class="card">
  <h2>Progress</h2>
  <table>
    <tbody>
      <tr>
        <th style="text-align:left">Time Played</th>
        <td><?= $hoursPlayed ?> hours</td>
      </tr>
      <?php if ($isOwner || $isStaff): ?>
      <tr>
        <th style="text-align:left">Money</th>
        <td><?= $gold ?>g <?= $silver ?>s <?= $copper ?>c</td>
      </tr>
      <?php endif; ?>
      <tr>
        <th style="text-align:left">Online</th>
        <td><?= ((int)$char['online'] === 1) ? 'Yes' : 'No' ?></td>
      </tr>
      <tr>
        <th style="text-align:left">Location</th>
        <td><?= htmlspecialchars($location) ?></td>
      </tr>
    </tbody>
  </table>
</section>

<?php if ($isOwner): ?>
<section class="card">
  <h2>My Character Tools</h2>
  <p>These links are only visible to the character owner.</p>
  <ul>
    <li>
      <a href="/shop?guid=<?= (int)$char['guid'] ?>">
        Open Shop for this character
      </a>
    </li>
    <li>
      <a href="/shop?guid=<?= (int)$char['guid'] ?>&tab=cosmetics">
        Cosmetic items &amp; appearance
      </a>
    </li>
  </ul>
</section>
<?php endif; ?>

<section class="card">
  <h2>Gear</h2>
  <?php if (empty($gear)): ?>
    <p><em>No equipped items found.</em></p>
  <?php else: ?>
    <table>
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

    // Simple InventoryType label (optional, can be expanded later)
    $invTypeText = $invType !== null ? (string)(int)$invType : '-';
  ?>
  <tr>
    <td><?= htmlspecialchars($slotName) ?></td>
    <td class="item-cell">
      <span class="<?= htmlspecialchars($qualityClass) ?>">
        <?= htmlspecialchars($itemName) ?>
      </span>
      <div class="item-tooltip">
        <div class="item-tooltip-name <?= htmlspecialchars($qualityClass) ?>">
          <?= htmlspecialchars($itemName) ?>
        </div>
        <?php if ($ilvl !== null): ?>
          <div class="item-tooltip-line">Item Level <?= (int)$ilvl ?></div>
        <?php endif; ?>
        <?php if ($reqLevel !== null && (int)$reqLevel > 1): ?>
          <div class="item-tooltip-line">Requires Level <?= (int)$reqLevel ?></div>
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
  <?php endif; ?>
</section>

<p>
  <a href="/account">&larr; Back to My Account</a>
  &nbsp;|&nbsp;
  <a href="/armory">Back to Armory</a>
</p>

<style>
  table { width:100%; border-collapse:collapse; max-width:820px; }
  th, td { padding:8px 10px; border-bottom:1px solid #222; }
  thead th { border-bottom:1px solid #333; }

  /* Item quality colors */
  .quality-0 { color:#9d9d9d; } /* Poor */
  .quality-1 { color:#ffffff; } /* Common */
  .quality-2 { color:#1eff00; } /* Uncommon */
  .quality-3 { color:#0070dd; } /* Rare */
  .quality-4 { color:#a335ee; } /* Epic */
  .quality-5 { color:#ff8000; } /* Legendary */
  .quality-6 { color:#e6cc80; } /* Artifact */
  .quality-7 { color:#00ccff; } /* Heirloom-ish */

  /* Tooltip layout */
  .item-cell {
    position: relative;
  }

  .item-tooltip {
    display: none;
    position: absolute;
    left: 0;
    top: 100%;
    margin-top: 4px;
    z-index: 20;
    background: #050508;
    border: 1px solid #555;
    padding: 8px 10px;
    border-radius: 4px;
    min-width: 220px;
    max-width: 320px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.6);
    font-size: 0.9rem;
  }

  .item-cell:hover .item-tooltip {
    display: block;
  }

  .item-tooltip-name {
    font-weight: 600;
    margin-bottom: 4px;
  }

  .item-tooltip-line {
    color: #e0e0e0;
  }
</style>

