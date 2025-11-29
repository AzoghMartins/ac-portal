<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;

/**
 * Admin-only metrics dashboard for quick host/realm health checks.
 */
final class AdminMetricsController
{
    public function __invoke(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            header('Location: /login?redirect=/admin/metrics');
            exit;
        }
        if (($user['gmlevel'] ?? 0) < 3) {
            header('Location: /');
            exit;
        }

        $metrics = [
            // Host uptime (from /proc/uptime)
            'uptime'     => $this->uptime(),
            'load'       => $this->loadAvg(),
            'memory'     => $this->memory(),
            'disk'       => $this->disk('/', '/srv'),
            'services'   => $this->services([
                'azeroth-worldserver.service',
                'azeroth-authserver.service',
            ]),
            'modules'    => $this->modulesList(),
            'realm'      => $this->realmStatus(),
            'timestamp'  => date('Y-m-d H:i:s'),
        ];

        View::render('admin-metrics', [
            'title'   => 'Server Metrics',
            'metrics' => $metrics,
        ]);
    }

    private function uptime(): ?array
    {
        $data = @file_get_contents('/proc/uptime');
        if ($data === false) return null;
        $parts = explode(' ', trim($data));
        $seconds = isset($parts[0]) ? (int)floor((float)$parts[0]) : 0;
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        return ['seconds' => $seconds, 'display' => sprintf('%dd %02dh %02dm', $days, $hours, $mins)];
    }

    private function loadAvg(): ?array
    {
        $load = @sys_getloadavg();
        if (!is_array($load)) return null;
        return ['l1' => $load[0] ?? 0, 'l5' => $load[1] ?? 0, 'l15' => $load[2] ?? 0];
    }

    private function memory(): ?array
    {
        $meminfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($meminfo === false) return null;
        $vals = [];
        foreach ($meminfo as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $vals[$m[1]] = (int)$m[2]; // kB
            }
        }
        if (!isset($vals['MemTotal'])) return null;
        $total = $vals['MemTotal'] * 1024;
        $free = ($vals['MemAvailable'] ?? $vals['MemFree'] ?? 0) * 1024;
        $used = max(0, $total - $free);
        return [
            'total' => $total,
            'free'  => $free,
            'used'  => $used,
            'used_pct' => $total > 0 ? ($used / $total * 100) : 0,
        ];
    }

    private function disk(string $rootPath, string $srvPath): array
    {
        $paths = [$rootPath, $srvPath];
        $out = [];
        foreach ($paths as $p) {
            $total = @disk_total_space($p);
            $free  = @disk_free_space($p);
            if ($total === false || $free === false) continue;
            $used  = $total - $free;
            $out[$p] = [
                'total' => $total,
                'free'  => $free,
                'used'  => $used,
                'used_pct' => $total > 0 ? ($used / $total * 100) : 0,
            ];
        }
        return $out;
    }

    private function modulesList(): array
    {
        $modules = $this->modulesFromDatabase();
        if (!empty($modules)) {
            return $modules;
        }

        $configured = $this->modulesFromConfiguredList();
        if (!empty($configured)) {
            return $configured;
        }

        return $this->modulesFromServerInfo();
    }

    private function modulesFromDatabase(): array
    {
        $dbKeys = ['DB_WORLD', 'DB_AUTH'];
        foreach ($dbKeys as $dbKey) {
            try {
                $db = \App\Db::env($dbKey, $dbKey === 'DB_AUTH' ? 'acore_auth' : 'acore_world');
                if (!$db) continue;
                $pdo = \App\Db::pdo($db);
                $stmt = $pdo->query('SELECT module, config, value FROM module_config ORDER BY module, config');
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                if (!$rows) {
                    continue;
                }

                $modules = [];
                foreach ($rows as $row) {
                    $name = (string)($row['module'] ?? '');
                    if ($name === '') {
                        $name = 'unknown';
                    }
                    if (!isset($modules[$name])) {
                        $modules[$name] = [
                            'name'         => $name,
                            'config_count' => 0,
                            'preview'      => [],
                        ];
                    }

                    $modules[$name]['config_count']++;
                    if (count($modules[$name]['preview']) < 4) {
                        $config = (string)($row['config'] ?? '');
                        $value  = (string)($row['value'] ?? '');
                        $modules[$name]['preview'][] = $config !== '' ? ($config . '=' . $value) : $value;
                    }
                }

                ksort($modules, SORT_STRING | SORT_FLAG_CASE);

                foreach ($modules as &$module) {
                    $module['preview_text'] = implode(', ', $module['preview']);
                }
                unset($module);

                return array_values($modules);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return [];
    }

    private function modulesFromConfiguredList(): array
    {
        $list = \App\Config::get('servers.modules', []);
        if (!is_array($list) || empty($list)) {
            $envList = trim((string)\App\Config::env('SERVER_MODULES', ''));
            if ($envList !== '') {
                $list = array_map('trim', explode(',', $envList));
            }
        }

        $modules = [];
        foreach ($list as $item) {
            if (!is_string($item)) continue;
            $name = trim($item);
            if ($name === '') continue;
            $modules[] = [
                'name'         => $name,
                'config_count' => null,
                'preview_text' => 'Configured',
            ];
        }

        return $modules;
    }

    private function modulesFromServerInfo(): array
    {
        $path = dirname(__DIR__, 2) . '/SERVER_INFO.md';
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $modules = [];
        foreach ($lines as $line) {
            $lineClean = trim($line);
            if ($lineClean === '') continue;
            $lower = strtolower($lineClean);
            if (strpos($lower, 'mods you') !== false && strpos($lower, ':') !== false) {
                [$label, $list] = explode(':', $lineClean, 2);
                $items = array_map('trim', explode(',', $list));
                foreach ($items as $item) {
                    if ($item === '') continue;
                    $modules[] = [
                        'name'         => $item,
                        'config_count' => null,
                        'preview_text' => 'Documented',
                    ];
                }
                break;
            }
        }

        return $modules;
    }

    private function services(array $names): array
    {
        $result = [];
        foreach ($names as $svc) {
            $status = $this->systemctlStatus($svc);
            $result[$svc] = $status;
        }
        return $result;
    }

    /**
     * Realm status: online players, peak, uptime, and socket pings.
     */
    private function realmStatus(): array
    {
        $status = [
            'online_players' => null,
            'online_bots'    => null,
            'peak'           => null,
            'uptime'         => null,
            'started_at'     => null,
            'ping'           => [
                'world' => $this->ping(\App\Db::env('WORLD_HOST', '127.0.0.1'), (int)\App\Db::env('WORLD_PORT', 8085)),
                'auth'  => $this->ping(\App\Db::env('AUTH_HOST', '127.0.0.1'), (int)\App\Db::env('AUTH_PORT', 3724)),
            ],
        ];

        // Online players
        try {
            $charsDb = \App\Db::env('DB_CHARACTERS', 'acore_characters');
            $pdo = \App\Db::pdo($charsDb);
            $row = $pdo->query('
                SELECT
                  SUM(account >= 301) AS players,
                  SUM(account BETWEEN 1 AND 300) AS bots
                FROM characters
                WHERE online = 1
            ')->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $status['online_players'] = isset($row['players']) ? (int)$row['players'] : null;
                $status['online_bots']    = isset($row['bots']) ? (int)$row['bots'] : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Uptime + peak from auth.uptime (latest row for realm)
        try {
            $authDb = \App\Db::env('DB_AUTH', 'acore_auth');
            $pdoAuth = \App\Db::pdo($authDb);
            $realmId = (int)\App\Db::env('REALM_ID', 1);
            $stmt = $pdoAuth->prepare('SELECT starttime, uptime, maxplayers FROM uptime WHERE realmid = :rid ORDER BY starttime DESC LIMIT 1');
            $stmt->execute([':rid' => $realmId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $start = isset($row['starttime']) ? (int)$row['starttime'] : 0;
                $uptimeSec = isset($row['uptime']) ? (int)$row['uptime'] : 0;

                $status['peak'] = isset($row['maxplayers']) ? (int)$row['maxplayers'] : null;

                if ($uptimeSec > 0) {
                    $status['uptime'] = $this->formatDuration($uptimeSec);
                } elseif ($start > 0 && $start <= time()) {
                    $status['uptime'] = $this->formatDuration(time() - $start);
                }

                if ($start > 0 && $start < (time() + 86400)) {
                    $status['started_at'] = date('Y-m-d H:i:s', $start);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $status;
    }

    private function systemctlStatus(string $service): string
    {
        $cmd = sprintf('systemctl is-active %s 2>/dev/null', escapeshellarg($service));
        $out = @shell_exec($cmd);
        $out = trim((string)$out);
        if ($out === '') return 'unknown';
        return $out;
    }

    /**
     * Scan the server log for recent shutdown/restart countdown notices.
     *
     * @return array<int,array{time:string,line:string}>
     */
    private function recentShutdownNotices(): array
    {
        $candidates = [
            $_ENV['ACORE_SERVER_LOG'] ?? null,
            '/home/azoghmartins/Acore/Server.log',       // common local path
            '/home/azoghmartins/Acore/bin/Server.log',   // legacy default
        ];

        $logPath = null;
        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate) && is_readable($candidate)) {
                $logPath = $candidate;
                break;
            }
        }

        if ($logPath === null) {
            return [];
        }

        // Read only the last ~500KB to avoid loading huge logs.
        $tail = $this->tailLines($logPath, 500000, 600);
        $matches = [];
        foreach ($tail as $line) { // already newest first
            if ($this->isShutdownLine($line)) {
                $matches[] = [
                    'time' => $this->extractTimestamp($line),
                    'line' => $line,
                ];
                if (count($matches) >= 1) break;
            }
        }
        if (!empty($matches)) {
            return $matches;
        }

        // Fallback: query worldserver via SOAP for the live shutdown countdown.
        $soapNotice = $this->shutdownNoticeFromSoap();
        return $soapNotice ? [$soapNotice] : [];
    }

    private function extractTimestamp(string $line): string
    {
        // Expect lines like "2024-03-01 12:34:56 ... Shutdown in 30 minute(s)"
        if (preg_match('/^\s*([\d-]{8,}\s+[\d:]{5,})/', $line, $m)) {
            return $m[1];
        }
        return '';
    }

    private function isShutdownLine(string $line): bool
    {
        return (bool)preg_match(
            '/shutdown in|restarting in|time left until shutdown|server shutdown|restart in|server restart/i',
            $line
        );
    }

    private function shutdownNoticeFromSoap(): ?array
    {
        // Only attempt if SOAP is available and configured.
        $timestamp = date('Y-m-d H:i:s');
        try {
            $result = \App\WorldServerSoap::execute('server info');
        } catch (\Throwable $e) {
            return [
                'time' => $timestamp,
                'line' => 'SOAP unavailable: ' . $e->getMessage(),
            ];
        }

        $lines = preg_split('/\r?\n/', (string)$result);
        if (!$lines) {
            return [
                'time' => $timestamp,
                'line' => 'SOAP returned no data for server info.',
            ];
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if ($this->isShutdownLine($line)) {
                return [
                    'time' => $timestamp . ' (SOAP)',
                    'line' => $line,
                ];
            }
        }

        return [
            'time' => $timestamp,
            'line' => 'No shutdown countdown reported via SOAP.',
        ];
    }

    /**
     * Simple TCP ping against a host:port, returns milliseconds or null on failure.
     *
     * @return array{ok:bool,ms:?float}
     */
    private function ping(string $host, int $port, int $timeoutMs = 800): array
    {
        $start = microtime(true);
        $ctx = stream_context_create(['socket' => ['timeout' => $timeoutMs / 1000]]);
        $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeoutMs / 1000, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return ['ok' => false, 'ms' => null];
        }
        @fclose($fp);
        $ms = (microtime(true) - $start) * 1000;
        return ['ok' => true, 'ms' => $ms];
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        return sprintf('%dd %02dh %02dm', $days, $hours, $mins);
    }

    /**
     * Return newest-first lines from the last $maxBytes of the file, up to $maxLines.
     *
     * @return array<int,string>
     */
    private function tailLines(string $path, int $maxBytes, int $maxLines): array
    {
        $size = filesize($path);
        $offset = ($size !== false && $size > $maxBytes) ? $size - $maxBytes : 0;

        $fh = fopen($path, 'r');
        if (!$fh) return [];

        if ($offset > 0) {
            fseek($fh, $offset);
            fgets($fh); // discard partial line
        }

        $buffer = '';
        $lines = [];
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') continue;
            $lines[] = $line;
        }
        fclose($fh);

        $lines = array_slice($lines, -$maxLines);
        return array_reverse($lines); // newest first
    }
}
