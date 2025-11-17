<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use App\WowHelper;
use PDO;

/**
 * Displays an individual character profile with gear and chronicle history.
 */
final class CharacterController
{
    /**
     * Entry point for /character?guid=... — aggregates data from multiple databases.
     */
    public function __invoke(): void
    {
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $authDb  = Db::env('DB_AUTH', 'acore_auth');
        $worldDb = Db::env('DB_WORLD', 'acore_world');

        $guid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;
        if ($guid <= 0) {
            http_response_code(400);
            echo 'Invalid character GUID.';
            return;
        }

        Auth::start();
        $viewer = Auth::user(); // ['id','username','gmlevel','role'] or null

        $pdoChars = Db::pdo($charsDb);

        // Core character info
        $stmt = $pdoChars->prepare("
            SELECT
                guid,
                account,
                name,
                level,
                class,
                race,
                gender,
                totaltime,
                money,
                online,
                map,
                zone,
                position_x,
                position_y,
                position_z
            FROM characters
            WHERE guid = :guid
            LIMIT 1
        ");
        $stmt->execute([':guid' => $guid]);
        $char = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$char) {
            http_response_code(404);
            echo 'Character not found.';
            return;
        }

        $accountId = (int)$char['account'];
        $raceId    = isset($char['race']) ? (int)$char['race'] : 0;

        // Account name (nice to have)
        $accountName = null;
        try {
            $pdoAuth = Db::pdo($authDb);
            $st = $pdoAuth->prepare("SELECT username FROM account WHERE id = :id LIMIT 1");
            $st->execute([':id' => $accountId]);
            $accountName = $st->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            // ignore
        }

        // Viewer permissions
        $viewerId    = $viewer['id'] ?? null;
        $viewerLevel = $viewer['gmlevel'] ?? 0;

        $isOwner     = ($viewerId !== null && (int)$viewerId === $accountId);
        $isStaff     = ($viewerLevel >= 1); // gm or higher
        $canModerate = $isStaff;

        // Individual progression tier (mod-individual-progression)
        $progressionState = null;
        $progressionLabel = null;
        try {
            $progStmt = $pdoChars->prepare("
                SELECT value
                FROM character_settings
                WHERE guid = :guid
                  AND source = 'mod-individual-progression'
                  AND setting = 0
                LIMIT 1
            ");
            $progStmt->execute([':guid' => $guid]);
            $value = $progStmt->fetchColumn();
            if ($value !== false) {
                $progressionState = (int)$value;
            }
        } catch (\Throwable $e) {
            $progressionState = null;
        }

        $progressionLabels = [
            0  => 'Tier 0 – Reach level 50',
            1  => 'Tier 1 – Defeat Ragnaros and Onyxia',
            2  => 'Tier 1 – Defeat Ragnaros and Onyxia',
            3  => 'Tier 2 – Defeat Nefarian',
            4  => 'Tier 3 – Complete Might of Kalimdor or Bang a Gong!',
            5  => 'Tier 4 – Complete Chaos and Destruction',
            6  => 'Tier 5 – Defeat C\'thun',
            7  => 'Tier 6 – Defeat Kel\'thuzad',
            8  => 'Tier 7 – Complete Into the Breach',
            9  => 'Tier 8 – Defeat Prince Malchezaar',
            10 => 'Tier 9 – Defeat Kael\'thas',
            11 => 'Tier 10 – Defeat Illidan',
            12 => 'Tier 11 – Defeat Zul\'jin',
            13 => 'Tier 12 – Defeat Kil\'jaeden',
            14 => 'Tier 13 – Defeat Kel\'thuzad (Lvl 80)',
            15 => 'Tier 14 – Defeat Yogg-Saron',
            16 => 'Tier 15 – Defeat Anub\'arak',
            17 => 'Tier 16 – Defeat The Lich King',
            18 => 'Tier 17 – Defeat Halion',
        ];

        if ($progressionState === null) {
            $progressionState = 0; // Default to Tier 0 (PROGRESSION_START) when no data is stored yet
        }

        $progressionLabel = $progressionLabels[$progressionState] ?? sprintf('Tier %d', $progressionState);

        // ---------- Gear loading ----------
        $gear = [];
        try {
            // 1) Equipped items from character_inventory (characters DB)
            $invStmt = $pdoChars->prepare("
                SELECT slot, bag, item
                FROM character_inventory
                WHERE guid = :guid
                  AND bag = 0
                  AND slot BETWEEN 0 AND 18
                ORDER BY slot ASC
            ");
            $invStmt->execute([':guid' => $guid]);
            $invRows = $invStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if ($invRows) {
                // 2) Fetch item_instance rows for these item GUIDs (characters DB)
                $itemGuids = array_column($invRows, 'item');
                $itemGuids = array_map('intval', $itemGuids);
                $itemGuids = array_values(array_unique($itemGuids));

                $itemsByGuid = [];
                if ($itemGuids) {
                    $placeholders = implode(',', array_fill(0, count($itemGuids), '?'));
                    $instStmt = $pdoChars->prepare("
                        SELECT guid, itemEntry
                        FROM item_instance
                        WHERE guid IN ($placeholders)
                    ");
                    $instStmt->execute($itemGuids);
                    foreach ($instStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $itemsByGuid[(int)$row['guid']] = (int)$row['itemEntry'];
                    }
                }

                // 3) Fetch item_template rows from world DB for those entries
                $pdoWorld = Db::pdo($worldDb);
                $entries = array_values(array_unique(array_values($itemsByGuid)));
                $templates = [];
                if ($entries) {
                    $placeholders = implode(',', array_fill(0, count($entries), '?'));
                    $tplStmt = $pdoWorld->prepare("
                        SELECT
                            entry,
                            name,
                            Quality,
                            ItemLevel,
                            InventoryType,
                            RequiredLevel,
                            class,
                            subclass
                        FROM item_template
                        WHERE entry IN ($placeholders)
                    ");
                    $tplStmt->execute($entries);
                    foreach ($tplStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $entryId = (int)$row['entry'];
                        $templates[$entryId] = [
                            'entry'         => $entryId,
                            'name'          => $row['name'] ?? null,
                            'Quality'       => isset($row['Quality']) ? (int)$row['Quality'] : null,
                            'ItemLevel'     => isset($row['ItemLevel']) ? (int)$row['ItemLevel'] : null,
                            'InventoryType' => isset($row['InventoryType']) ? (int)$row['InventoryType'] : null,
                            'RequiredLevel' => isset($row['RequiredLevel']) ? (int)$row['RequiredLevel'] : null,
                            'class'         => isset($row['class']) ? (int)$row['class'] : null,
                            'subclass'      => isset($row['subclass']) ? (int)$row['subclass'] : null,
                        ];
                    }
                }

                // 4) Build final gear array
                foreach ($invRows as $inv) {
                    $slot     = (int)$inv['slot'];
                    $itemGuid = (int)$inv['item'];
                    $entry    = $itemsByGuid[$itemGuid] ?? null;
                    if ($entry === null) {
                        continue;
                    }
                    $tpl = $templates[$entry] ?? null;

                    $gear[] = [
                        'slot'         => $slot,
                        'itemGuid'     => $itemGuid,
                        'itemEntry'    => $entry,
                        'name'         => $tpl['name']          ?? null,
                        'Quality'      => $tpl['Quality']       ?? null,
                        'ItemLevel'    => $tpl['ItemLevel']     ?? null,
                        'InventoryType'=> $tpl['InventoryType'] ?? null,
                        'RequiredLevel'=> $tpl['RequiredLevel'] ?? null,
                        'class'        => $tpl['class']         ?? null,
                        'subclass'     => $tpl['subclass']      ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $gear = [];
        }
        // ---------- end gear loading ----------

        $chronicleEntries = [];
        try {
            $raceNameForChronicle = trim(WowHelper::raceName($raceId));
            $raceSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $raceNameForChronicle), '_'));

            $startPayloadCandidates = [];
            if ($raceNameForChronicle !== '') {
                $startPayloadCandidates[] = 'Start ' . $raceNameForChronicle;
            }
            if ($raceSlug !== '') {
                $startPayloadCandidates[] = 'start_' . $raceSlug;
                $startPayloadCandidates[] = 'Start ' . ucwords(str_replace('_', ' ', $raceSlug));
            }
            if ($raceId > 0) {
                $startPayloadCandidates[] = 'start_' . $raceId;
                $startPayloadCandidates[] = 'Start ' . $raceId;
            }
            $startPayloadCandidates[] = 'Start';

            $startPayloadCandidates = array_values(array_unique($startPayloadCandidates));

            $combineNarrative = static function (
                array $row,
                string $characterName,
                bool $useGroupNarrative = false,
                array $groupNames = []
            ): ?string {
                if ($useGroupNarrative) {
                    $text = trim((string)($row['group_narrative'] ?? ''));
                    if ($text === '') {
                        $text = trim((string)($row['narrative'] ?? ''));
                    }
                } else {
                    $text = trim((string)($row['narrative'] ?? ''));
                }

                if ($text === '') {
                    return null;
                }

                $groupNames = array_values(array_filter(array_map('trim', $groupNames), static function ($v) {
                    return $v !== '';
                }));

                $groupList = '';
                if ($groupNames) {
                    if (count($groupNames) === 1) {
                        $groupList = $groupNames[0];
                    } else {
                        $last = array_pop($groupNames);
                        $groupList = implode(', ', $groupNames) . ' and ' . $last;
                    }
                }

                $replacer = static function (string $content) use ($characterName, $groupList, $groupNames): string {
                    $content = str_replace('<name>', $characterName, $content);
                    if ($groupList !== '') {
                        $content = str_replace('<group>', $groupList, $content);
                    }
                    if (strpos($content, '<member>') !== false) {
                        if (!empty($groupNames)) {
                            $randomName = $groupNames[array_rand($groupNames)];
                            $content = str_replace('<member>', $randomName, $content);
                        } else {
                            $content = str_replace('<member>', $characterName, $content);
                        }
                    }
                    return $content;
                };

                return $replacer($text);
            };

            $startRow = null;
            if (!empty($startPayloadCandidates)) {
                $extraStmt = $pdoChars->prepare("
                    SELECT payload, narrative, `group narrative` AS group_narrative
                    FROM character_chronicle_extras
                    WHERE payload = :payload
                    LIMIT 1
                ");
                foreach ($startPayloadCandidates as $candidatePayload) {
                    $extraStmt->execute([':payload' => $candidatePayload]);
                    $row = $extraStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    if ($row) {
                        $startRow = $row;
                        break;
                    }
                }
            }
            if ($startRow) {
                $startText = $combineNarrative($startRow, $char['name']);
                if ($startText !== null) {
                    $chronicleEntries[] = [
                        'created_at' => null,
                        'text'       => $startText,
                    ];
                }
            }

            $logStmt = $pdoChars->prepare("
                SELECT event_payload, event_type, created_at, group_members
                FROM character_chronicle_log
                WHERE character_guid = :guid
                ORDER BY created_at ASC
            ");
            $logStmt->execute([':guid' => $guid]);
            $logRows = $logStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $validLogRows = [];
            $payloadMap   = [];
            $questIds     = [];
            $allGroupMemberIds = [];
            foreach ($logRows as $row) {
                $eventType = strtolower(trim((string)($row['event_type'] ?? '')));
                if ($eventType === 'achievement') {
                    continue;
                }

                $payload = trim((string)($row['event_payload'] ?? ''));
                if ($payload === '') {
                    continue;
                }

                $groupMembersRaw = trim((string)($row['group_members'] ?? ''));
                $groupMemberIds = [];
                if ($groupMembersRaw !== '') {
                    if (preg_match_all('/\d+/', $groupMembersRaw, $gmatches)) {
                        foreach ($gmatches[0] as $gm) {
                            $gid = (int)$gm;
                            if ($gid > 0 && $gid !== $guid) {
                                $groupMemberIds[] = $gid;
                            }
                        }
                    }
                }
                if ($groupMemberIds) {
                    $allGroupMemberIds = array_merge($allGroupMemberIds, $groupMemberIds);
                }

                if ($eventType === 'quest') {
                    if (preg_match('/\(ID:\s*(\d+)\)/', $payload, $m)) {
                        $questId = (int)$m[1];
                        if ($questId > 0) {
                            $questIds[] = $questId;
                            $row['__quest_id'] = $questId;
                        }
                    }
                    if (!isset($row['__quest_id'])) {
                        continue; // skip malformed quest payload
                    }
                } else {
                    $payloadMap[$payload] = true;
                }

                $row['__group_member_ids'] = $groupMemberIds;
                $validLogRows[] = $row;
            }

            $extrasByPayload = [];
            if ($payloadMap) {
                $payloads = array_keys($payloadMap);
                $chunks   = array_chunk($payloads, 50);
                foreach ($chunks as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $stmt = $pdoChars->prepare("
                        SELECT payload, narrative, `group narrative` AS group_narrative
                        FROM character_chronicle_extras
                        WHERE payload IN ($placeholders)
                    ");
                    $stmt->execute($chunk);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $key = (string)$row['payload'];
                        $extrasByPayload[$key] = $row;
                    }
                }
            }

            $questsById = [];
            if ($questIds) {
                $questIds = array_values(array_unique($questIds));
                $chunks = array_chunk($questIds, 50);
                foreach ($chunks as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $stmt = $pdoChars->prepare("
                        SELECT payload, narrative, `group narrative` AS group_narrative
                        FROM character_chronicle_quests
                        WHERE payload IN ($placeholders)
                    ");
                    $stmt->execute($chunk);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $qid = (int)$row['payload'];
                        $questsById[$qid] = $row;
                    }
                }
            }

            $groupNamesByGuid = [];
            if ($allGroupMemberIds) {
                $allGroupMemberIds = array_values(array_unique($allGroupMemberIds));
                $placeholders = implode(',', array_fill(0, count($allGroupMemberIds), '?'));
                $stmt = $pdoChars->prepare("
                    SELECT guid, name
                    FROM characters
                    WHERE guid IN ($placeholders)
                ");
                $stmt->execute($allGroupMemberIds);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $gid = (int)$row['guid'];
                    $groupNamesByGuid[$gid] = $row['name'] ?? '';
                }
            }

            foreach ($validLogRows as $row) {
                $payload = trim((string)($row['event_payload'] ?? ''));
                if ($payload === '') {
                    continue;
                }
                $hasGroupMembers = isset($row['group_members']) && trim((string)$row['group_members']) !== '';
                $groupMemberIds = $row['__group_member_ids'] ?? [];
                $groupMemberNames = [];
                foreach ($groupMemberIds as $gid) {
                    if (isset($groupNamesByGuid[$gid])) {
                        $groupMemberNames[] = $groupNamesByGuid[$gid];
                    }
                }

                if (isset($row['__quest_id'])) {
                    $questId = (int)$row['__quest_id'];
                    $questRow = $questsById[$questId] ?? null;
                    if ($questRow === null) {
                        continue;
                    }
                    $text = $combineNarrative($questRow, $char['name'], $hasGroupMembers, $groupMemberNames);
                } else {
                    $extraRow = $extrasByPayload[$payload] ?? null;
                    if ($extraRow === null) {
                        continue;
                    }
                    $text = $combineNarrative($extraRow, $char['name'], $hasGroupMembers, $groupMemberNames);
                }
                if ($text === null) {
                    continue;
                }

                $createdAtDisplay = null;
                $createdAtRaw = $row['created_at'] ?? null;
                if ($createdAtRaw) {
                    try {
                        $dateObj = new \DateTimeImmutable($createdAtRaw);
                        $createdAtDisplay = $dateObj->format('F j, Y • H:i');
                    } catch (\Throwable $e) {
                        $createdAtDisplay = (string)$createdAtRaw;
                    }
                }

                $chronicleEntries[] = [
                    'created_at' => $createdAtDisplay,
                    'text'       => $text,
                ];
            }
        } catch (\Throwable $e) {
            $chronicleEntries = [];
        }

        View::render('character', [
            'title'             => sprintf('Character: %s', $char['name']),
            'character'         => $char,
            'accountName'       => $accountName,
            'gear'              => $gear,
            'progression'       => [
                'state' => $progressionState,
                'label' => $progressionLabel,
            ],
            'viewer'            => $viewer,
            'isOwner'           => $isOwner,
            'isStaff'           => $isStaff,
            'canModerate'       => $canModerate,
            'chronicleEntries'  => $chronicleEntries,
        ]);
    }
}
