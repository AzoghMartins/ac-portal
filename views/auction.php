<?php
/**
 * Auction house browser (logged-in users).
 *
 * @var array $rows
 * @var array $filters
 * @var int   $page
 * @var int   $pages
 * @var int   $total
 */

$filters = $filters ?? [];
$page    = $page ?? 1;
$pages   = $pages ?? 1;
$total   = $total ?? 0;
$sort    = $sort ?? 'time';
$dir     = $dir ?? 'asc';

$q         = $filters['q']          ?? '';
$minLevel  = isset($filters['min_level']) ? (int)$filters['min_level'] : 0;
$maxLevel  = isset($filters['max_level']) ? (int)$filters['max_level'] : 80;
$faction   = $filters['faction']    ?? '';
$category  = $filters['category']   ?? '';
$subcategory = $filters['subcategory'] ?? '';

$catOptions = [
    'armor' => [
        'label' => 'Armor',
        'subs'  => [
            ''        => 'Any armor',
            'cloth'   => 'Cloth',
            'leather' => 'Leather',
            'mail'    => 'Mail',
            'plate'   => 'Plate',
        ],
    ],
    'weapon' => [
        'label' => 'Weapons',
        'subs'  => [
            ''          => 'Any weapon',
            'dagger'    => 'Dagger',
            '1h_sword'  => 'One-Hand Swords',
            '1h_axe'    => 'One-Hand Axes',
            '1h_mace'   => 'One-Hand Maces',
            '2h_sword'  => 'Two-Hand Swords',
            '2h_axe'    => 'Two-Hand Axes',
            '2h_mace'   => 'Two-Hand Maces',
            'staff'     => 'Staves',
            'polearm'   => 'Polearms',
            'fist'      => 'Fist Weapons',
            'bow'       => 'Bows',
            'gun'       => 'Guns',
            'thrown'    => 'Thrown',
        ],
    ],
    'accessory' => [
        'label' => 'Accessories',
        'subs'  => [
            ''         => 'Any accessory',
            'necklace' => 'Necklaces',
            'ring'     => 'Rings',
            'trinket'  => 'Trinkets',
        ],
    ],
    'gem' => [
        'label' => 'Gems',
        'subs'  => [
            ''       => 'Any gem',
            'meta'   => 'Meta',
            'red'    => 'Red',
            'yellow' => 'Yellow',
            'blue'   => 'Blue',
        ],
    ],
    'craft' => [
        'label' => 'Crafting Materials',
        'subs'  => [
            ''              => 'Any material',
            'alchemy'       => 'Alchemy',
            'blacksmithing' => 'Blacksmithing',
            'engineering'   => 'Engineering',
            'herbalism'     => 'Herbalism',
            'enchanting'    => 'Enchanting',
            'leatherworking'=> 'Leatherworking',
            'tailoring'     => 'Tailoring',
            'jewelcrafting' => 'Jewelcrafting',
        ],
    ],
    'quest' => [
        'label' => 'Quest Items',
        'subs'  => [
            '' => 'Quest items',
        ],
    ],
];

function format_money(int $copper): string {
    $g = intdiv($copper, 10000);
    $s = intdiv($copper % 10000, 100);
    $c = $copper % 100;
    $parts = [];
    if ($g > 0) $parts[] = "{$g}g";
    if ($s > 0 || $g > 0) $parts[] = "{$s}s";
    $parts[] = "{$c}c";
    return implode(' ', $parts);
}

function time_left(?int $ts): string {
    if ($ts === null) return '—';
    $diff = $ts - time();
    if ($diff < 0) return 'Expired';
    $hrs = intdiv($diff, 3600);
    $mins = intdiv($diff % 3600, 60);
    if ($hrs > 0) return "{$hrs}h {$mins}m";
    return "{$mins}m";
}

function faction_label($houseId): string {
    $map = [
        1 => 'Alliance',
        3 => 'Alliance',
        7 => 'Alliance',
        2 => 'Horde',
        6 => 'Horde',
    ];
    return $map[$houseId] ?? '—';
}

function item_slot_label(?int $invType, ?int $class, ?int $sub): string {
    $invMap = [
        1 => 'Head', 2 => 'Neck', 3 => 'Shoulder', 5 => 'Chest', 6 => 'Waist', 7 => 'Legs',
        8 => 'Feet', 9 => 'Wrist', 10 => 'Hands', 11 => 'Finger', 12 => 'Trinket',
        15 => 'Back', 16 => '2H Weapon', 17 => 'Shield', 20 => 'Chest', 21 => 'Main Hand',
        22 => 'Off Hand', 23 => 'Held', 25 => 'Thrown', 26 => 'Ranged', 28 => 'Relic'
    ];
    if (isset($invMap[$invType])) return $invMap[$invType];
    // fallback for weapons/armor
    if ($class === 2) return 'Weapon';
    if ($class === 4) return 'Armor';
    return 'Item';
}

function stat_labels(): array {
    return [
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
}

function socket_name(int $color): string {
    $map = [
        1 => 'Meta',
        2 => 'Red',
        4 => 'Yellow',
        8 => 'Blue',
    ];
    return $map[$color] ?? ('Socket '.$color);
}
?>

<div class="account-page">
  <header class="account-header">
    <h1 class="account-title">Auction House</h1>
    <p class="account-subtitle">
      Logged-in users can browse live auctions with filters similar to the in-game AH.
    </p>
  </header>

  <section class="account-section">
    <form class="auction-filters" method="get" action="/auction">
      <div class="auction-filter-grid">
        <div class="auction-filter-block">
          <label class="auction-filter-label" for="q">Search</label>
          <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Item name">
        </div>
        <div class="auction-filter-block">
          <label class="auction-filter-label">Level Requirement</label>
          <div class="auction-filter-inline">
            <input type="number" name="min_level" value="<?= htmlspecialchars((string)$minLevel) ?>" min="0" max="80" placeholder="Min">
            <input type="number" name="max_level" value="<?= htmlspecialchars((string)$maxLevel) ?>" min="0" max="80" placeholder="Max">
          </div>
        </div>
        <div class="auction-filter-block">
          <label class="auction-filter-label" for="faction">Faction</label>
          <select id="faction" name="faction">
            <option value="">Any</option>
            <option value="alliance" <?= $faction === 'alliance' ? 'selected' : '' ?>>Alliance</option>
            <option value="horde" <?= $faction === 'horde' ? 'selected' : '' ?>>Horde</option>
            <option value="neutral" <?= $faction === 'neutral' ? 'selected' : '' ?>>Neutral</option>
          </select>
        </div>
        <div class="auction-filter-block">
          <label class="auction-filter-label" for="category">Category</label>
          <select id="category" name="category">
            <option value="">Any</option>
            <?php foreach ($catOptions as $key => $cat): ?>
              <option value="<?= htmlspecialchars($key) ?>" <?= $category === $key ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="auction-filter-block">
          <label class="auction-filter-label" for="subcategory">Subcategory</label>
          <select id="subcategory" name="subcategory">
            <option value="">Any</option>
            <?php
              $subs = $catOptions[$category]['subs'] ?? [];
              foreach ($subs as $sKey => $sLabel):
            ?>
              <option value="<?= htmlspecialchars($sKey) ?>" <?= $subcategory === $sKey ? 'selected' : '' ?>>
                <?= htmlspecialchars($sLabel) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="auction-filter-actions">
        <button type="submit" class="auth-submit">Search</button>
        <a class="auth-submit auth-submit--ghost" href="/auction">Reset</a>
      </div>
    </form>
  </section>

  <section class="account-section">
    <div class="auction-summary">
      <span><?= number_format($total) ?> auctions</span>
      <?php if ($total > 0): ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php endif; ?>
    </div>

    <div class="account-characters-table-wrap">
      <table class="account-characters-table clickable-table item-table">
        <thead>
          <tr>
            <?php
              $headers = [
                ['label' => 'Item',      'key' => 'name'],
                ['label' => 'Req',       'key' => 'req'],
                ['label' => 'iLvl',      'key' => 'ilevel'],
                ['label' => 'Stack',     'key' => 'stack'],
                ['label' => 'Buyout',    'key' => 'buyout'],
                ['label' => 'Bid',       'key' => 'bid'],
                ['label' => 'Time Left', 'key' => 'time'],
                ['label' => 'House',     'key' => 'house'],
              ];

              $currentDir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
              $toggleDir = $currentDir === 'asc' ? 'desc' : 'asc';

              foreach ($headers as $h):
                $qs = $_GET;
                $qs['sort'] = $h['key'];
                $qs['dir'] = ($sort === $h['key']) ? $toggleDir : 'asc';
                $link = '/auction?' . http_build_query($qs);
                $isActive = ($sort === $h['key']);
                $arrow = $isActive ? ($currentDir === 'asc' ? '↑' : '↓') : '';
            ?>
              <th>
                <a href="<?= htmlspecialchars($link) ?>" class="auction-sort <?= $isActive ? 'is-active' : '' ?>">
                  <span><?= htmlspecialchars($h['label']) ?></span>
                  <?php if ($arrow): ?>
                    <span class="auction-sort-arrow"><?= $arrow ?></span>
                  <?php endif; ?>
                </a>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="9">No auctions found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $qty   = max(1, (int)($r['stack_count'] ?? 1));
                $buy   = isset($r['buyout']) ? (int)$r['buyout'] : 0;
                $bid   = isset($r['lastbid']) ? (int)$r['lastbid'] : 0;
                $start = isset($r['startbid']) ? (int)$r['startbid'] : 0;
                if ($bid <= 0) $bid = $start;
                $per   = $qty > 0 ? (int)floor($buy / $qty) : $buy;
                $timeLeft = isset($r['expires_at']) ? (int)$r['expires_at'] : null;
                $quality  = isset($r['quality']) ? (int)$r['quality'] : 1;
                $qualityClass = 'quality-' . $quality;

                $armor   = isset($r['armor']) ? (int)$r['armor'] : 0;
                $dmgMin  = isset($r['dmg_min1']) ? (float)$r['dmg_min1'] : null;
                $dmgMax  = isset($r['dmg_max1']) ? (float)$r['dmg_max1'] : null;
                $delayMs = isset($r['delay']) ? (int)$r['delay'] : null;
                $dps     = null;
                if ($dmgMin !== null && $dmgMax !== null && $delayMs && $delayMs > 0) {
                    $avg = ($dmgMin + $dmgMax) / 2;
                    $dps = $avg / ($delayMs / 1000);
                }

                $sockets = array_filter([
                    isset($r['socketColor_1']) ? (int)$r['socketColor_1'] : null,
                    isset($r['socketColor_2']) ? (int)$r['socketColor_2'] : null,
                    isset($r['socketColor_3']) ? (int)$r['socketColor_3'] : null,
                ], fn($v) => $v && $v > 0);

                $stats = [];
                $labels = stat_labels();
                for ($i = 1; $i <= 10; $i++) {
                    $tKey = "stat_type{$i}";
                    $vKey = "stat_value{$i}";
                    $tVal = isset($r[$tKey]) ? (int)$r[$tKey] : 0;
                    $vVal = isset($r[$vKey]) ? (int)$r[$vKey] : 0;
                    if ($tVal > 0 && $vVal !== 0) {
                        $stats[] = ['label' => $labels[$tVal] ?? ("Stat {$tVal}"), 'value' => $vVal];
                    }
                }
              ?>
              <tr>
                <td class="item-cell <?= htmlspecialchars($qualityClass) ?>">
                  <span class="item-name"><?= htmlspecialchars($r['item_name'] ?? 'Unknown') ?></span>
                  <div class="item-tooltip">
                    <div class="item-tooltip-name <?= htmlspecialchars($qualityClass) ?>">
                      <?= htmlspecialchars($r['item_name'] ?? 'Unknown') ?>
                    </div>
                    <?php if (!empty($r['req_level'])): ?>
                      <div class="item-tooltip-line">Requires level <?= (int)$r['req_level'] ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['ilevel'])): ?>
                      <div class="item-tooltip-line">Item Level <?= (int)$r['ilevel'] ?></div>
                    <?php endif; ?>
                    <div class="item-tooltip-line"><?= htmlspecialchars(item_slot_label($r['inventory_type'] ?? null, $r['item_class'] ?? null, $r['item_subclass'] ?? null)) ?></div>
                    <?php if ($armor > 0): ?>
                      <div class="item-tooltip-line"><?= $armor ?> Armor</div>
                    <?php endif; ?>
                    <?php if ($dmgMin !== null && $dmgMax !== null && $delayMs): ?>
                      <div class="item-tooltip-line">
                        Damage: <?= number_format($dmgMin, 0) ?> - <?= number_format($dmgMax, 0) ?>
                      </div>
                      <div class="item-tooltip-line">
                        Speed: <?= number_format($delayMs / 1000, 2) ?> s
                      </div>
                      <?php if ($dps !== null): ?>
                        <div class="item-tooltip-line">DPS: <?= number_format($dps, 1) ?></div>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($stats)): ?>
                      <?php foreach ($stats as $st): ?>
                        <div class="item-tooltip-line">+<?= (int)$st['value'] ?> <?= htmlspecialchars($st['label']) ?></div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($sockets)): ?>
                      <div class="item-tooltip-line">
                        Sockets:
                        <?= htmlspecialchars(implode(', ', array_map('socket_name', $sockets))) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= isset($r['req_level']) ? (int)$r['req_level'] : '—' ?></td>
                <td><?= isset($r['ilevel']) ? (int)$r['ilevel'] : '—' ?></td>
                <td><?= $qty ?></td>
                <td>
                  <?php if ($buy > 0): ?>
                    <?= htmlspecialchars(format_money($buy)) ?>
                    <?php if ($qty > 1): ?>
                      <div class="muted">per: <?= htmlspecialchars(format_money($per)) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($bid > 0): ?>
                    <?= htmlspecialchars(format_money($bid)) ?>
                  <?php else: ?>
                    <?= htmlspecialchars(format_money($start)) ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(time_left($timeLeft)) ?></td>
                <td><?= htmlspecialchars(faction_label($r['house_id'] ?? null)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <?php
        $qs = $_GET;
        $qsPrev = $qs;
        $qsPrev['page'] = max(1, $page - 1);
        $qsNext = $qs;
        $qsNext['page'] = min($pages, $page + 1);
      ?>
      <div class="auction-pagination">
        <a
          class="auction-page-link <?= $page <= 1 ? 'is-disabled' : '' ?>"
          href="<?= $page <= 1 ? '#' : htmlspecialchars('/auction?' . http_build_query($qsPrev)) ?>"
          aria-label="Previous page"
        >&larr;</a>
        <span class="auction-page-status"><?= (int)$page ?> / <?= (int)$pages ?></span>
        <a
          class="auction-page-link <?= $page >= $pages ? 'is-disabled' : '' ?>"
          href="<?= $page >= $pages ? '#' : htmlspecialchars('/auction?' . http_build_query($qsNext)) ?>"
          aria-label="Next page"
        >&rarr;</a>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
// Update subcategory options when category changes
(function() {
  const catSelect = document.getElementById('category');
  const subSelect = document.getElementById('subcategory');
  if (!catSelect || !subSelect) return;

  const options = <?= json_encode($catOptions) ?>;
  catSelect.addEventListener('change', function() {
    const cat = this.value;
    const subs = (options[cat] && options[cat].subs) ? options[cat].subs : {'': 'Any'};
    subSelect.innerHTML = '';
    for (const key in subs) {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = subs[key];
      subSelect.appendChild(opt);
    }
  });
})();

// Flip item tooltips above/below based on position in the auction table.
(function() {
  const table = document.querySelector('.item-table');
  const cells = document.querySelectorAll('.item-table .item-cell');
  if (!table || !cells.length) return;

  function adjustTooltip(event) {
    const cell = event.currentTarget;
    const tooltip = cell.querySelector('.item-tooltip');
    if (!tooltip) return;

    const rect = cell.getBoundingClientRect();
    const tableRect = table.getBoundingClientRect();
    const threshold = tableRect.top + tableRect.height / 2;
    const midY = rect.top + rect.height / 2;

    if (midY < threshold) {
      tooltip.classList.add('tooltip-below');
    } else {
      tooltip.classList.remove('tooltip-below');
    }
  }

  cells.forEach((cell) => {
    cell.addEventListener('mouseenter', adjustTooltip);
    cell.addEventListener('mousemove', adjustTooltip);
  });
})();
</script>
