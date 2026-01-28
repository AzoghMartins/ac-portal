<?php
declare(strict_types=1);

namespace App;

use PDO;

final class MarksAwarder
{
    private const EARNED_MARKS = [
        0 => 20,
        1 => 30,
        2 => 30,
        3 => 40,
        4 => 30,
        5 => 40,
        6 => 40,
        7 => 20,
        8 => 40,
        9 => 60,
        10 => 60,
        11 => 30,
        12 => 60,
        13 => 90,
        14 => 100,
        15 => 90,
        16 => 130,
        17 => 90,
    ];

    private const ORDERED_TIERS = ['0','1','2','3','4','5','6','7','7.5','8','9','10','11','12','13','14','15','16','17'];

    public static function syncAccount(int $accountId): void
    {
        if ($accountId <= 0) {
            return;
        }

        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $pdoPortal = Db::pdoWrite($portalDb);
        $pdoChars = Db::pdo($charsDb);

        $charsStmt = $pdoChars->prepare('SELECT guid, name, level, class FROM characters WHERE account = :acct');
        $charsStmt->execute([':acct' => $accountId]);
        $characters = $charsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($characters as $char) {
            $guid = (int)($char['guid'] ?? 0);
            if ($guid <= 0) {
                continue;
            }

            $level = (int)($char['level'] ?? 1);
            $classId = (int)($char['class'] ?? 0);
            $currentTier = self::progressionState($pdoChars, $guid, $level);

            self::backfillSkippedTiers($pdoPortal, $accountId, $guid);

            $existing = self::existingTierAwards($pdoPortal, $guid);
            $minAwardTier = ($classId === 6) ? 13 : 0;
            $maxTier = min($currentTier - 1, 17);

            if ($maxTier < $minAwardTier) {
                continue;
            }

            try {
                $pdoPortal->beginTransaction();
                for ($tier = $minAwardTier; $tier <= $maxTier; $tier++) {
                    $tierKey = (string)$tier;
                    if (isset($existing[$tierKey])) {
                        continue;
                    }
                    $marks = self::EARNED_MARKS[$tier] ?? null;
                    if ($marks === null) {
                        continue;
                    }

                    $insert = $pdoPortal->prepare('
                        INSERT IGNORE INTO character_tier_awards (character_guid, account_id, tier, source)
                        VALUES (:guid, :account_id, :tier, :source)
                    ');
                    $insert->execute([
                        ':guid' => $guid,
                        ':account_id' => $accountId,
                        ':tier' => $tierKey,
                        ':source' => 'earned',
                    ]);

                    if ($insert->rowCount() === 0) {
                        continue;
                    }

                    $ledger = $pdoPortal->prepare('
                        INSERT INTO marks_ledger (account_id, delta, reason)
                        VALUES (:account_id, :delta, :reason)
                    ');
                    $ledger->execute([
                        ':account_id' => $accountId,
                        ':delta' => $marks,
                        ':reason' => sprintf('earned:tier:%s:guid:%d', $tierKey, $guid),
                    ]);
                }
                $pdoPortal->commit();
            } catch (\Throwable $e) {
                if ($pdoPortal->inTransaction()) {
                    $pdoPortal->rollBack();
                }
            }
        }
    }

    private static function existingTierAwards(PDO $pdoPortal, int $guid): array
    {
        $stmt = $pdoPortal->prepare('SELECT tier, source FROM character_tier_awards WHERE character_guid = :guid');
        $stmt->execute([':guid' => $guid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existing = [];
        foreach ($rows as $row) {
            $tier = (string)($row['tier'] ?? '');
            if ($tier !== '') {
                $existing[$tier] = (string)($row['source'] ?? '');
            }
        }
        return $existing;
    }

    private static function backfillSkippedTiers(PDO $pdoPortal, int $accountId, int $guid): void
    {
        $stmt = $pdoPortal->prepare('
            SELECT sf.purchase_id, sf.payload_json
            FROM shop_fulfillment sf
            JOIN shop_purchase sp ON sp.id = sf.purchase_id
            WHERE sp.account_id = :account_id
              AND sp.character_guid = :guid
              AND sf.status = "done"
              AND sf.payload_json IS NOT NULL
        ');
        $stmt->execute([':account_id' => $accountId, ':guid' => $guid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $payload = json_decode((string)$row['payload_json'], true);
            if (!is_array($payload)) {
                continue;
            }
            if (($payload['action'] ?? '') !== 'tier_purchase') {
                continue;
            }
            $currentTier = (int)($payload['current_tier'] ?? 0);
            $skipTo = (string)($payload['skip_to_tier'] ?? '');
            $tiers = self::tiersBetween((string)$currentTier, $skipTo);

            foreach ($tiers as $tierKey) {
                $ins = $pdoPortal->prepare('
                    INSERT IGNORE INTO character_tier_awards (character_guid, account_id, tier, source, purchase_id)
                    VALUES (:guid, :account_id, :tier, :source, :purchase_id)
                ');
                $ins->execute([
                    ':guid' => $guid,
                    ':account_id' => $accountId,
                    ':tier' => $tierKey,
                    ':source' => 'skipped',
                    ':purchase_id' => (int)$row['purchase_id'],
                ]);
            }
        }
    }

    private static function tiersBetween(string $currentTier, string $skipToTier): array
    {
        $ordered = self::ORDERED_TIERS;
        $currentIdx = array_search($currentTier, $ordered, true);
        $targetIdx = array_search($skipToTier, $ordered, true);
        if ($currentIdx === false || $targetIdx === false || $targetIdx < $currentIdx) {
            return [];
        }

        $idx7 = array_search('7', $ordered, true);
        $idx75 = array_search('7.5', $ordered, true);
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

    private static function progressionState(PDO $pdoChars, int $guid, int $level): int
    {
        $progressionState = null;
        try {
            static $settingsCols = null;
            if ($settingsCols === null) {
                $settingsCols = [];
                foreach ($pdoChars->query('SHOW COLUMNS FROM character_settings')->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    $settingsCols[$col['Field']] = true;
                }
            }

            $selectCols = [];
            if (isset($settingsCols['value'])) {
                $selectCols[] = 'value';
            }
            if (isset($settingsCols['data'])) {
                $selectCols[] = 'data';
            }
            if (!$selectCols) {
                throw new \RuntimeException('character_settings missing value/data columns.');
            }

            $progStmt = $pdoChars->prepare(sprintf(
                "SELECT %s FROM character_settings WHERE guid = :guid AND source = 'mod-individual-progression'",
                implode(',', $selectCols)
            ));
            $progStmt->execute([':guid' => $guid]);
            $rows = $progStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $parsedCandidates = [];
            foreach ($rows as $row) {
                $candidates = [];
                if (isset($row['value']) && $row['value'] !== '' && $row['value'] !== null) {
                    $candidates[] = $row['value'];
                }
                if (isset($row['data']) && $row['data'] !== '' && $row['data'] !== null) {
                    $candidates[] = $row['data'];
                }

                foreach ($candidates as $candidate) {
                    $parsed = null;
                    if (is_string($candidate)) {
                        $trim = trim($candidate);
                        $json = json_decode($trim, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            if (is_int($json)) {
                                $parsed = $json;
                            } elseif (is_array($json)) {
                                if (isset($json['value'])) {
                                    $parsed = (int)$json['value'];
                                } elseif (isset($json['state'])) {
                                    $parsed = (int)$json['state'];
                                }
                            }
                        }

                        if ($parsed === null) {
                            if (preg_match('/-?\d+/', $trim, $m)) {
                                $parsed = (int)$m[0];
                            }
                        }
                    } else {
                        $parsed = (int)$candidate;
                    }

                    if ($parsed !== null) {
                        $parsedCandidates[] = (int)$parsed;
                    }
                }
            }

            if ($parsedCandidates) {
                $progressionState = max($parsedCandidates);
            }
        } catch (\Throwable $e) {
            $progressionState = null;
        }

        if ($progressionState === null) {
            $progressionState = 0;
        }

        if ($progressionState === 0 && $level >= 60) {
            $progressionState = 1;
        }

        return (int)$progressionState;
    }
}
