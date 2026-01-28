<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Db;
use App\WorldServerSoap;

$once = in_array('--once', $argv, true);
$limit = 10;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int)substr($arg, 8);
    }
}
if ($limit <= 0) {
    $limit = null;
}

function log_line(string $msg): void
{
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
}

function update_character_skills(PDO $pdoChars, int $guid, int $level): void
{
    $max = $level * 5;
    $stmt = $pdoChars->prepare('UPDATE character_skills SET value = :max, max = :max WHERE guid = :guid');
    $stmt->execute([':max' => $max, ':guid' => $guid]);
}

function tiers_between(string $currentTier, string $skipToTier): array
{
    $ordered = ['0','1','2','3','4','5','6','7','7.5','8','9','10','11','12','13','14','15','16','17'];
    $currentIdx = array_search($currentTier, $ordered, true);
    $targetIdx = array_search($skipToTier, $ordered, true);
    if ($currentIdx === false || $targetIdx === false || $targetIdx < $currentIdx) {
        return [];
    }

    $idx7 = array_search('7', $ordered, true);
    $idx8 = array_search('8', $ordered, true);
    $include75 = false;
    if ($skipToTier === '7.5') {
        $include75 = true;
    } elseif ($idx7 !== false && $idx8 !== false) {
        if ($currentIdx <= $idx7 && $targetIdx >= $idx8) {
            $include75 = true;
        }
    }

    $tiers = [];
    for ($i = $currentIdx; $i <= $targetIdx; $i++) {
        $tierKey = $ordered[$i];
        if ($tierKey === '7.5' && !$include75) {
            continue;
        }
        $tiers[] = $tierKey;
    }

    return $tiers;
}

function record_skipped_tiers(PDO $pdoPortal, int $accountId, int $guid, int $purchaseId, array $payload): void
{
    if (($payload['action'] ?? '') !== 'tier_purchase') {
        return;
    }
    $currentTier = isset($payload['current_tier']) ? (string)$payload['current_tier'] : '';
    $skipToTier = isset($payload['skip_to_tier']) ? (string)$payload['skip_to_tier'] : '';
    if ($currentTier === '' || $skipToTier === '') {
        return;
    }

    $tiers = tiers_between($currentTier, $skipToTier);
    if (!$tiers) {
        return;
    }

    $ins = $pdoPortal->prepare('
        INSERT IGNORE INTO character_tier_awards (character_guid, account_id, tier, source, purchase_id)
        VALUES (:guid, :account_id, :tier, :source, :purchase_id)
    ');
    foreach ($tiers as $tierKey) {
        $ins->execute([
            ':guid' => $guid,
            ':account_id' => $accountId,
            ':tier' => $tierKey,
            ':source' => 'skipped',
            ':purchase_id' => $purchaseId,
        ]);
    }
}

function upsert_progression(PDO $pdoChars, int $guid, int $tier): void
{
    $stmt = $pdoChars->prepare("SELECT 1 FROM character_settings WHERE guid = :guid AND source = 'mod-individual-progression' LIMIT 1");
    $stmt->execute([':guid' => $guid]);
    if ($stmt->fetchColumn()) {
        $upd = $pdoChars->prepare("UPDATE character_settings SET data = :tier WHERE guid = :guid AND source = 'mod-individual-progression'");
        $upd->execute([':tier' => (string)$tier, ':guid' => $guid]);
    } else {
        $ins = $pdoChars->prepare("INSERT INTO character_settings (guid, source, data) VALUES (:guid, 'mod-individual-progression', :tier)");
        $ins->execute([':guid' => $guid, ':tier' => (string)$tier]);
    }
}

function class_profile(int $classId, PDO $pdoWorld, int $targetLevel): array
{
    $profiles = [
        'mage' => ['int' => 2.0, 'spell' => 2.0, 'spirit' => 0.5, 'stam' => 1.0],
        'warlock' => ['int' => 2.0, 'spell' => 2.0, 'spirit' => 0.5, 'stam' => 1.0],
        'priest' => ['int' => 2.0, 'spell' => 2.0, 'spirit' => 1.0, 'stam' => 1.0],
        'rogue' => ['agi' => 2.0, 'ap' => 1.5, 'hit' => 0.5, 'crit' => 0.5, 'stam' => 1.0],
        'hunter' => ['agi' => 2.0, 'ap' => 1.5, 'hit' => 0.5, 'crit' => 0.5, 'stam' => 1.0],
        'warrior' => ['str' => 2.0, 'ap' => 1.0, 'hit' => 0.5, 'crit' => 0.5, 'stam' => 1.2],
        'paladin' => ['str' => 2.0, 'hit' => 0.5, 'crit' => 0.5, 'stam' => 1.2],
    ];

    if ($classId === 7) { // shaman
        $caster = ['int' => 2.0, 'spell' => 2.0, 'spirit' => 0.8, 'stam' => 1.0];
        $melee = ['agi' => 1.5, 'str' => 1.5, 'ap' => 1.2, 'stam' => 1.0];
        return choose_dual_profile($pdoWorld, $classId, $caster, $melee, $targetLevel);
    }
    if ($classId === 11) { // druid
        $caster = ['int' => 2.0, 'spell' => 2.0, 'spirit' => 1.0, 'stam' => 1.0];
        $feral = ['agi' => 2.0, 'ap' => 1.2, 'stam' => 1.0];
        return choose_dual_profile($pdoWorld, $classId, $caster, $feral, $targetLevel);
    }

    return match ($classId) {
        8 => $profiles['mage'],
        9 => $profiles['warlock'],
        5 => $profiles['priest'],
        4 => $profiles['rogue'],
        3 => $profiles['hunter'],
        1 => $profiles['warrior'],
        2 => $profiles['paladin'],
        default => ['stam' => 1.0],
    };
}

function choose_dual_profile(PDO $pdoWorld, int $classId, array $caster, array $melee, int $targetLevel): array
{
    $testSlots = [5, 7, 17];
    $minLevel = $targetLevel >= 70 ? 68 : 58;
    $maxLevel = $targetLevel >= 70 ? 70 : 60;
    $casterScore = 0.0;
    $meleeScore = 0.0;

    foreach ($testSlots as $invType) {
        $item = pick_best_item($pdoWorld, $invType, $classId, $minLevel, $maxLevel, $caster);
        if ($item) {
            $casterScore += $item['score'];
        }
        $item = pick_best_item($pdoWorld, $invType, $classId, $minLevel, $maxLevel, $melee);
        if ($item) {
            $meleeScore += $item['score'];
        }
    }

    return $casterScore >= $meleeScore ? $caster : $melee;
}

function stat_weights(array $profile): array
{
    return [
        3 => $profile['agi'] ?? 0.0,      // Agility
        4 => $profile['str'] ?? 0.0,      // Strength
        5 => $profile['int'] ?? 0.0,      // Intellect
        6 => $profile['spirit'] ?? 0.0,   // Spirit
        7 => $profile['stam'] ?? 0.0,     // Stamina
        38 => $profile['ap'] ?? 0.0,      // Attack Power
        41 => $profile['spell'] ?? 0.0,   // Spell Power
        16 => $profile['hit'] ?? 0.0,     // Hit Rating
        17 => $profile['hit'] ?? 0.0,
        18 => $profile['hit'] ?? 0.0,
        19 => $profile['crit'] ?? 0.0,
        20 => $profile['crit'] ?? 0.0,
        21 => $profile['crit'] ?? 0.0,
        30 => $profile['hit'] ?? 0.0,
        31 => $profile['crit'] ?? 0.0,
    ];
}

function score_item(array $row, array $weights): float
{
    $score = 0.0;
    $nonStam = [];
    for ($i = 1; $i <= 10; $i++) {
        $type = (int)($row['stat_type' . $i] ?? 0);
        $val = (int)($row['stat_value' . $i] ?? 0);
        if ($type <= 0 || $val === 0) {
            continue;
        }
        $weight = $weights[$type] ?? 0.0;
        $score += $val * $weight;
        if ($type !== 7) {
            $nonStam[] = ['weight' => $weight, 'value' => $val];
        }
    }

    if (count($nonStam) >= 2) {
        usort($nonStam, static fn($a, $b) => $b['value'] <=> $a['value']);
        $top = array_slice($nonStam, 0, 2);
        if (($top[0]['weight'] ?? 0) == 0.0 && ($top[1]['weight'] ?? 0) == 0.0) {
            $score -= 500.0;
        }
    }

    return $score;
}

function armor_subclass(int $classId): ?int
{
    return match ($classId) {
        1, 2 => 4,  // Warrior, Paladin => Plate
        3, 7 => 3,  // Hunter, Shaman => Mail
        4, 11 => 2, // Rogue, Druid => Leather
        5, 8, 9 => 1, // Priest, Mage, Warlock => Cloth
        default => null,
    };
}

function pick_best_item(PDO $pdoWorld, int $inventoryType, int $classId, int $minLevel, int $maxLevel, array $profile, ?int $subclass = null): ?array
{
    $weights = stat_weights($profile);
    $sql = '
        SELECT entry, stat_type1, stat_value1, stat_type2, stat_value2, stat_type3, stat_value3,
               stat_type4, stat_value4, stat_type5, stat_value5, stat_type6, stat_value6,
               stat_type7, stat_value7, stat_type8, stat_value8, stat_type9, stat_value9,
               stat_type10, stat_value10
        FROM item_template
        WHERE Quality = 2
          AND RequiredLevel BETWEEN :minLevel AND :maxLevel
          AND InventoryType = :inv
    ';
    $params = [':minLevel' => $minLevel, ':maxLevel' => $maxLevel, ':inv' => $inventoryType];

    if ($inventoryType === 5 || $inventoryType === 6 || $inventoryType === 7 || $inventoryType === 8 || $inventoryType === 9 || $inventoryType === 10 || $inventoryType === 1 || $inventoryType === 3 || $inventoryType === 20) {
        $sql .= ' AND class = 4';
        if ($subclass !== null) {
            $sql .= ' AND subclass = :subclass';
            $params[':subclass'] = $subclass;
        }
    } elseif ($inventoryType === 16) {
        $sql .= ' AND class = 4';
    } elseif ($inventoryType === 11 || $inventoryType === 12 || $inventoryType === 2) {
        $sql .= ' AND class IN (4, 2, 7, 3)';
    } else {
        $sql .= ' AND class = 2';
    }

    $stmt = $pdoWorld->prepare($sql);
    $stmt->execute($params);
    $best = null;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $score = score_item($row, $weights);
        if ($best === null || $score > $best['score']) {
            $best = ['entry' => (int)$row['entry'], 'score' => $score];
        }
    }

    return $best;
}

function pick_gear(PDO $pdoWorld, int $classId, int $targetLevel): array
{
    $minLevel = $targetLevel >= 70 ? 68 : 58;
    $maxLevel = $targetLevel >= 70 ? 70 : 60;
    $profile = class_profile($classId, $pdoWorld, $targetLevel);
    $armorSub = armor_subclass($classId);

    $slots = [
        1,  // head
        2,  // neck
        3,  // shoulder
        6,  // waist
        7,  // legs
        8,  // feet
        9,  // wrist
        10, // hands
        16, // back
        11, // ring
        12, // trinket
    ];

    $items = [];

    // Chest slot (shirt/robe) choose best between 5 and 20.
    $chestCandidates = [];
    $chestCandidates[] = pick_best_item($pdoWorld, 5, $classId, $minLevel, $maxLevel, $profile, $armorSub);
    $chestCandidates[] = pick_best_item($pdoWorld, 20, $classId, $minLevel, $maxLevel, $profile, $armorSub);
    $chestBest = null;
    foreach ($chestCandidates as $candidate) {
        if (!$candidate) {
            continue;
        }
        if ($chestBest === null || $candidate['score'] > $chestBest['score']) {
            $chestBest = $candidate;
        }
    }
    if ($chestBest) {
        $items[] = $chestBest['entry'];
    }

    foreach ($slots as $inv) {
        if ($inv === 11 || $inv === 12) {
            // handled separately for duplicates
            continue;
        }
        $item = pick_best_item($pdoWorld, $inv, $classId, $minLevel, $maxLevel, $profile, $armorSub);
        if ($item) {
            $items[] = $item['entry'];
        }
    }

    // Rings (2)
    $rings = pick_multiple_items($pdoWorld, 11, $classId, $minLevel, $maxLevel, $profile, 2, null);
    $items = array_merge($items, $rings);

    // Trinkets (2)
    $trinkets = pick_multiple_items($pdoWorld, 12, $classId, $minLevel, $maxLevel, $profile, 2, null);
    $items = array_merge($items, $trinkets);

    // Weapons by class
    $weaponItems = pick_weapons($pdoWorld, $classId, $minLevel, $maxLevel, $profile);
    $items = array_merge($items, $weaponItems);

    return array_values(array_unique(array_filter($items)));
}

function pick_multiple_items(PDO $pdoWorld, int $inventoryType, int $classId, int $minLevel, int $maxLevel, array $profile, int $count, ?int $subclass): array
{
    $weights = stat_weights($profile);
    $sql = '
        SELECT entry, stat_type1, stat_value1, stat_type2, stat_value2, stat_type3, stat_value3,
               stat_type4, stat_value4, stat_type5, stat_value5, stat_type6, stat_value6,
               stat_type7, stat_value7, stat_type8, stat_value8, stat_type9, stat_value9,
               stat_type10, stat_value10
        FROM item_template
        WHERE Quality = 2
          AND RequiredLevel BETWEEN :minLevel AND :maxLevel
          AND InventoryType = :inv
    ';
    $params = [':minLevel' => $minLevel, ':maxLevel' => $maxLevel, ':inv' => $inventoryType];
    if ($subclass !== null) {
        $sql .= ' AND subclass = :subclass';
        $params[':subclass'] = $subclass;
    }

    $stmt = $pdoWorld->prepare($sql);
    $stmt->execute($params);
    $candidates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $score = score_item($row, $weights);
        $candidates[] = ['entry' => (int)$row['entry'], 'score' => $score];
    }

    usort($candidates, static fn($a, $b) => $b['score'] <=> $a['score']);
    $entries = [];
    foreach ($candidates as $cand) {
        $entries[] = $cand['entry'];
        if (count($entries) >= $count) {
            break;
        }
    }
    return $entries;
}

function pick_weapons(PDO $pdoWorld, int $classId, int $minLevel, int $maxLevel, array $profile): array
{
    $weaponInv = [];
    if (in_array($classId, [8, 9, 5, 11], true)) { // Mage, Warlock, Priest, Druid
        $weaponInv = [17]; // 2H staff
    } elseif ($classId === 3) { // Hunter
        $weaponInv = [26, 15, 17];
    } elseif ($classId === 4) { // Rogue
        $weaponInv = [13, 21, 22];
    } else {
        $weaponInv = [17];
    }

    $items = [];
    foreach ($weaponInv as $inv) {
        $item = pick_best_item($pdoWorld, $inv, $classId, $minLevel, $maxLevel, $profile, null);
        if ($item) {
            $items[] = $item['entry'];
            if ($classId === 4 && count($items) >= 2) {
                break;
            }
            if ($classId !== 3 && $classId !== 4) {
                break;
            }
        }
    }

    return $items;
}

function send_items(string $name, array $itemIds): void
{
    $subject = 'Tier Advancement';
    $body = 'Your purchased content.';
    $chunks = array_chunk($itemIds, 12);
    foreach ($chunks as $chunk) {
        $parts = [];
        foreach ($chunk as $itemId) {
            $parts[] = (string)$itemId;
        }
        $cmd = sprintf('send items %s "%s" "%s" %s', $name, $subject, $body, implode(' ', $parts));
        WorldServerSoap::execute($cmd);
    }
}

function send_money(string $name, int $copper): void
{
    if ($copper <= 0) {
        return;
    }
    $subject = 'Tier Advancement';
    $body = 'Your purchased content.';
    $cmd = sprintf('send money %s "%s" "%s" %d', $name, $subject, $body, $copper);
    WorldServerSoap::execute($cmd);
}

$portalDb = Db::env('DB_PORTAL', 'ac_portal');
$charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
$worldDb = Db::env('DB_WORLD', 'acore_world');

$pdoPortal = Db::pdoWrite($portalDb);
$pdoChars = Db::pdoWrite($charsDb);
$pdoWorld = Db::pdo($worldDb);

$processed = 0;

while (true) {
    $pdoPortal->beginTransaction();
    $row = $pdoPortal->query("SELECT * FROM shop_fulfillment WHERE status = 'queued' ORDER BY created_at ASC LIMIT 1 FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $pdoPortal->commit();
        if ($once || ($limit !== null && $processed >= $limit)) {
            break;
        }
        sleep(5);
        continue;
    }

    $fulfillmentId = (int)$row['id'];
    $purchaseId = (int)$row['purchase_id'];
    $attempt = (int)($row['attempt_count'] ?? 0) + 1;

    $upd = $pdoPortal->prepare("UPDATE shop_fulfillment SET status = 'processing', attempt_count = :attempt, updated_at = NOW() WHERE id = :id");
    $upd->execute([':attempt' => $attempt, ':id' => $fulfillmentId]);
    $pdoPortal->commit();

    $notes = [];
    try {
        $purchaseStmt = $pdoPortal->prepare('SELECT * FROM shop_purchase WHERE id = :id');
        $purchaseStmt->execute([':id' => $purchaseId]);
        $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
        if (!$purchase) {
            throw new RuntimeException('Purchase not found.');
        }

        $payload = $row['payload_json'] ? json_decode((string)$row['payload_json'], true) : null;
        if (!is_array($payload)) {
            throw new RuntimeException('Missing fulfillment payload.');
        }

        $charGuid = isset($purchase['character_guid']) ? (int)$purchase['character_guid'] : 0;
        if ($charGuid <= 0) {
            throw new RuntimeException('Missing character guid.');
        }

        $charStmt = $pdoChars->prepare('SELECT guid, account, name, level, class FROM characters WHERE guid = :guid LIMIT 1');
        $charStmt->execute([':guid' => $charGuid]);
        $char = $charStmt->fetch(PDO::FETCH_ASSOC);
        if (!$char) {
            throw new RuntimeException('Character not found.');
        }
        if ((int)$char['account'] !== (int)$purchase['account_id']) {
            throw new RuntimeException('Character ownership mismatch.');
        }

        $characterName = (string)$char['name'];
        $classId = (int)$char['class'];
        $targetTier = (int)($payload['result_tier'] ?? 0);

        $boost = $payload['boost'] ?? [];
        $targetLevel = isset($boost['target_level']) ? (int)$boost['target_level'] : null;
        $gearProfile = $boost['gear_profile'] ?? null;
        $goldCopper = isset($payload['gold_copper']) ? (int)$payload['gold_copper'] : 0;

        if ($targetLevel !== null && $targetLevel > 0) {
            WorldServerSoap::execute(sprintf('character level %s %d', $characterName, $targetLevel));
            WorldServerSoap::execute(sprintf('reset talents %s', $characterName));
            update_character_skills($pdoChars, $charGuid, $targetLevel);
        }

        if ($gearProfile && $classId !== 6 && $targetLevel) {
            $items = pick_gear($pdoWorld, $classId, (int)$targetLevel);
            if (!empty($items)) {
                send_items($characterName, $items);
            } else {
                $notes[] = 'No gear candidates found for profile ' . $gearProfile . '.';
            }
        }

        if ($goldCopper > 0) {
            send_money($characterName, $goldCopper);
        }

        upsert_progression($pdoChars, $charGuid, $targetTier);
        try {
            record_skipped_tiers($pdoPortal, (int)$purchase['account_id'], $charGuid, $purchaseId, $payload);
        } catch (Throwable $e) {
            $notes[] = 'Skipped tiers not recorded: ' . $e->getMessage();
        }

        $noteText = $notes ? implode(' ', $notes) : null;
        $done = $pdoPortal->prepare("UPDATE shop_fulfillment SET status = 'done', applied_at = NOW(), last_error = NULL, notes = :notes, updated_at = NOW() WHERE id = :id");
        $done->execute([':notes' => $noteText, ':id' => $fulfillmentId]);

        $processed++;
        log_line("Fulfilled purchase {$purchaseId} for {$characterName}.");
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $errStmt = $pdoPortal->prepare("UPDATE shop_fulfillment SET status = 'error', last_error = :err, updated_at = NOW() WHERE id = :id");
        $errStmt->execute([':err' => $error, ':id' => $fulfillmentId]);
        log_line("Error on fulfillment {$purchaseId}: {$error}");
    }

    if ($once || ($limit !== null && $processed >= $limit)) {
        break;
    }
}

log_line('Worker finished.');
