<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;
use PDO;
use DateTimeImmutable;
use DateTimeZone;

final class HomeController {
    public function __invoke(): void {
        $authDb   = Db::env('DB_AUTH','acore_auth');
        $worldDb  = Db::env('DB_WORLD','acore_world');
        $charsDb  = Db::env('DB_CHARACTERS', 'acore_characters');

        // Basic metric
        $accounts = (int)(Db::pdo($authDb)->query('SELECT COUNT(*) c FROM account')->fetch()['c'] ?? 0);

        // Read realmd uptime info (revision, last restart, uptime)
        $rev = null;
        $lastRestart = null;
        $uptimeSeconds = null;
        try {
            $pdoAuth = Db::pdo($authDb);
            $row = $pdoAuth->query("SELECT starttime, uptime, maxplayers, revision FROM uptime ORDER BY starttime DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $rev = $row['revision'] ?? null;
                $uptimeSeconds = isset($row['uptime']) ? (int)$row['uptime'] : null;
                // starttime is UNIX epoch seconds in realmd.uptime
                $start = isset($row['starttime']) ? (int)$row['starttime'] : null;
                if ($start) {
                    $dt = (new DateTimeImmutable('@'.$start))->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    $lastRestart = $dt->format('Y-m-d H:i:s');
                }
            }
        } catch (\Throwable $e) {
            // ignore gracefully
        }

        // Try to detect last DB update applied (best-effort)
        $lastUpdate = null;
        try {
            $pdoWorld = Db::pdo($worldDb);
            // Prefer 'updates' table if present
            $haveUpdates = $pdoWorld->query("SHOW TABLES LIKE 'updates'")->fetchColumn();
            if ($haveUpdates) {
                $desc = $pdoWorld->query("DESCRIBE updates")->fetchAll(PDO::FETCH_ASSOC);
                $cols = array_map(fn($c) => strtolower($c['Field']), $desc);
                $col = null;
                if (in_array('applied_time', $cols, true)) $col = 'applied_time';
                elseif (in_array('timestamp', $cols, true)) $col = 'timestamp';
                elseif (in_array('date', $cols, true)) $col = 'date';

                if ($col) {
                    $st = $pdoWorld->query("SELECT MAX($col) FROM updates");
                    $lastUpdate = $st->fetchColumn() ?: null;
                }
            }
            // Fallback: version_db_world may contain a date token (e.g., in sql_rev)
            if (!$lastUpdate) {
                $haveVer = $pdoWorld->query("SHOW TABLES LIKE 'version_db_world'")->fetchColumn();
                if ($haveVer) {
                    $st = $pdoWorld->query("SELECT * FROM version_db_world LIMIT 1");
                    $vdw = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    if ($vdw) {
                        foreach ($vdw as $v) {
                            if (is_string($v) && preg_match('/\d{4}-\d{2}-\d{2}/', $v, $m)) {
                                $lastUpdate = $m[0];
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Human readable uptime
        $uptimeHuman = null;
        if ($uptimeSeconds !== null) {
            $secs = $uptimeSeconds;
            $d = intdiv($secs, 86400); $secs -= $d*86400;
            $h = intdiv($secs, 3600);  $secs -= $h*3600;
            $m = intdiv($secs, 60);    $s = $secs - $m*60;
            $parts = [];
            if ($d) $parts[] = $d.'d';
            if ($h || $d) $parts[] = $h.'h';
            if ($m || $h || $d) $parts[] = $m.'m';
            $parts[] = $s.'s';
            $uptimeHuman = implode(' ', $parts);
        }


        // Realm name from realmlist (assuming realm id 1)
$realmName   = null;
$realmOnline = false;

try {
    $pdo = Db::pdo($authDb);
    $stmt = $pdo->query("SELECT name FROM realmlist WHERE id = 1 LIMIT 1");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['name'])) {
        $realmName = $row['name'];
    }
} catch (\Throwable $e) {
    // swallow, show null in view
}

// Decide online/offline based on uptime: if we have a computed uptime, worldserver is up
$realmOnline = $uptimeHuman !== null;

// Character counts
$botCharacters    = 0;
$playerCharacters = 0;
$allianceOnline   = 0;
$hordeOnline      = 0;

try {
    $charsPdo = Db::pdo($charsDb);

    // Bot characters: accounts 1–300
    $botCharacters = (int)$charsPdo
        ->query("SELECT COUNT(*) FROM characters WHERE account BETWEEN 1 AND 300")
        ->fetchColumn();

    // Player characters: accounts 301+
    $playerCharacters = (int)$charsPdo
        ->query("SELECT COUNT(*) FROM characters WHERE account >= 301")
        ->fetchColumn();

    // Online Alliance players (non-bots)
$allianceOnline = (int)$charsPdo
    ->query("
        SELECT COUNT(*) FROM characters
        WHERE online = 1
        AND account >= 301
        AND race IN (1, 3, 4, 7, 11)
    ")
    ->fetchColumn();

    // Online Horde players (non-bots)
$hordeOnline = (int)$charsPdo
    ->query("
        SELECT COUNT(*) FROM characters
        WHERE online = 1
        AND account >= 301
        AND race IN (2, 5, 6, 8, 9, 10)
    ")
    ->fetchColumn();

} catch (\Throwable $e) {
    // If something fails, we just leave counts at 0
}


        View::render('home', [
    'title'            => 'Home',
    'accounts'         => $accounts,
    'ac_rev'           => $rev,
    'last_restart'     => $lastRestart,
    'uptime_human'     => $uptimeHuman,
    'last_update'      => $lastUpdate,
    'realm_name'       => $realmName,
    'realm_online'     => $realmOnline,
    'bot_characters'   => $botCharacters,
    'player_characters'=> $playerCharacters,
    'online_alliance'  => $allianceOnline,
    'online_horde'     => $hordeOnline,
]);

    }
}
