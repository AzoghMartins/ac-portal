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

        View::render('home', [
            'title'        => 'Home',
            'accounts'     => $accounts,
            'ac_rev'       => $rev,
            'last_restart' => $lastRestart,
            'uptime_human' => $uptimeHuman,
            'last_update'  => $lastUpdate,
        ]);
    }
}
