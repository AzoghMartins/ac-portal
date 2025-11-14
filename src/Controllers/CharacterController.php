<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use PDO;

final class CharacterController
{
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

        View::render('character', [
            'title'       => sprintf('Character: %s', $char['name']),
            'character'   => $char,
            'accountName' => $accountName,
            'gear'        => $gear,
            'viewer'      => $viewer,
            'isOwner'     => $isOwner,
            'isStaff'     => $isStaff,
            'canModerate' => $canModerate,
        ]);
    }
}
