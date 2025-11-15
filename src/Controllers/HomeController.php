<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * Builds metrics for the home page: uptime, realm stats, population counts.
 */
final class HomeController
{
    /**
     * Renders the landing page with realm telemetry.
     */
    public function __invoke(): void
    {
        $authDb  = Db::env('DB_AUTH', 'acore_auth');
        $worldDb = Db::env('DB_WORLD', 'acore_world');
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');

        $accounts = (int)(Db::pdo($authDb)->query('SELECT COUNT(*) AS c FROM account')->fetch()['c'] ?? 0);

        $rev           = null;
        $lastRestart   = null;
        $uptimeSeconds = null;
        try {
            $pdoAuth = Db::pdo($authDb);
            $row = $pdoAuth
                ->query("SELECT starttime, uptime, revision FROM uptime ORDER BY starttime DESC LIMIT 1")
                ->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $rev           = $row['revision'] ?? null;
                $uptimeSeconds = isset($row['uptime']) ? (int)$row['uptime'] : null;
                $start         = isset($row['starttime']) ? (int)$row['starttime'] : null;
                if ($start) {
                    $dt = (new DateTimeImmutable('@' . $start))
                        ->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    $lastRestart = $dt->format('Y-m-d H:i:s');
                }
            }
        } catch (\Throwable $e) {
            // ignore gracefully
        }

        $lastUpdate = null;
        try {
            $pdoWorld    = Db::pdo($worldDb);
            $haveUpdates = $pdoWorld->query("SHOW TABLES LIKE 'updates'")->fetchColumn();
            if ($haveUpdates) {
                $desc = $pdoWorld->query("DESCRIBE updates")->fetchAll(PDO::FETCH_ASSOC);
                $cols = array_map(static fn ($c) => strtolower($c['Field']), $desc);
                $col  = null;
                if (in_array('applied_time', $cols, true)) {
                    $col = 'applied_time';
                } elseif (in_array('timestamp', $cols, true)) {
                    $col = 'timestamp';
                } elseif (in_array('date', $cols, true)) {
                    $col = 'date';
                }

                if ($col) {
                    $st         = $pdoWorld->query("SELECT MAX($col) FROM updates");
                    $lastUpdate = $st->fetchColumn() ?: null;
                }
            }

            if (!$lastUpdate) {
                $haveVer = $pdoWorld->query("SHOW TABLES LIKE 'version_db_world'")->fetchColumn();
                if ($haveVer) {
                    $st  = $pdoWorld->query("SELECT * FROM version_db_world LIMIT 1");
                    $vdw = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                    if ($vdw) {
                        foreach ($vdw as $value) {
                            if (is_string($value) && preg_match('/\d{4}-\d{2}-\d{2}/', $value, $match)) {
                                $lastUpdate = $match[0];
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $uptimeHuman = null;
        if ($uptimeSeconds !== null) {
            $secs = $uptimeSeconds;
            $d    = intdiv($secs, 86400);
            $secs -= $d * 86400;
            $h    = intdiv($secs, 3600);
            $secs -= $h * 3600;
            $m    = intdiv($secs, 60);
            $s    = $secs - $m * 60;
            $parts = [];
            if ($d) {
                $parts[] = $d . 'd';
            }
            if ($h || $d) {
                $parts[] = $h . 'h';
            }
            if ($m || $h || $d) {
                $parts[] = $m . 'm';
            }
            $parts[]    = $s . 's';
            $uptimeHuman = implode(' ', $parts);
        }

        $realmName   = null;
        $realmOnline = $uptimeHuman !== null;
        try {
            $pdo = Db::pdo($authDb);
            $row = $pdo->query("SELECT name FROM realmlist WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['name'])) {
                $realmName = $row['name'];
            }
        } catch (\Throwable $e) {
            // swallow, show null in view
        }

        $botCharacters    = 0;
        $playerCharacters = 0;
        $allianceOnline   = 0;
        $hordeOnline      = 0;
        try {
            $charsPdo = Db::pdo($charsDb);

            $botCharacters = (int)$charsPdo
                ->query("SELECT COUNT(*) FROM characters WHERE account BETWEEN 1 AND 300")
                ->fetchColumn();

            $playerCharacters = (int)$charsPdo
                ->query("SELECT COUNT(*) FROM characters WHERE account >= 301")
                ->fetchColumn();

            $allianceOnline = (int)$charsPdo
                ->query("
                    SELECT COUNT(*) FROM characters
                    WHERE online = 1
                      AND account >= 301
                      AND race IN (1, 3, 4, 7, 11)
                ")
                ->fetchColumn();

            $hordeOnline = (int)$charsPdo
                ->query("
                    SELECT COUNT(*) FROM characters
                    WHERE online = 1
                      AND account >= 301
                      AND race IN (2, 5, 6, 8, 9, 10)
                ")
                ->fetchColumn();
        } catch (\Throwable $e) {
            // leave counts at 0
        }

        View::render('home', [
            'title'             => 'Home',
            'accounts'          => $accounts,
            'ac_rev'            => $rev,
            'last_restart'      => $lastRestart,
            'uptime_human'      => $uptimeHuman,
            'last_update'       => $lastUpdate,
            'realm_name'        => $realmName,
            'realm_online'      => $realmOnline,
            'bot_characters'    => $botCharacters,
            'player_characters' => $playerCharacters,
            'online_alliance'   => $allianceOnline,
            'online_horde'      => $hordeOnline,
        ]);
    }
}
