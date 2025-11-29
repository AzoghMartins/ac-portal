<?php
declare(strict_types=1);

namespace App;

/**
 * Simple JSON-backed settings store with env fallbacks.
 */
final class Config
{
    private const FILE = __DIR__ . '/../storage/config.json';

    private static ?array $cache = null;

    /**
     * Fetch a config value using dot notation with env + defaults fallback.
     */
    public static function get(string $path, $default = null)
    {
        $all = self::all();
        $value = self::valueByPath($all, $path);
        return $value !== null ? $value : $default;
    }

    /**
     * Resolve a single value intended to mirror an env var (e.g. DB_HOST).
     */
    public static function env(string $key, $default = null)
    {
        $map = self::envMap();
        if (isset($map[$key])) {
            $fallback = self::baseEnv($key, $default);
            return self::get($map[$key], $fallback);
        }

        return self::baseEnv($key, $default);
    }

    /**
     * Return the merged settings (env defaults + saved overrides).
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $defaults = self::defaults();
        $saved    = self::loadFile();

        return self::$cache = self::merge($defaults, $saved);
    }

    /**
     * Persist selected settings to disk, merging with any existing file data.
     */
    public static function save(array $data): void
    {
        $existing = self::loadFile();
        $payload  = self::merge($existing, $data);

        $dir = dirname(self::FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(self::FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        self::$cache = null; // reset cache so subsequent reads include new values
    }

    /**
     * Convenience helper to fetch the primary world entry (first configured).
     */
    public static function primaryWorld(): array
    {
        $worlds = self::get('servers.worlds', []);
        if (is_array($worlds) && count($worlds) > 0) {
            return $worlds[0];
        }

        return [
            'name' => 'Realm 1',
            'host' => self::baseEnv('WORLD_HOST', '127.0.0.1'),
            'port' => (int)self::baseEnv('WORLD_PORT', 8085),
            'id'   => (int)self::baseEnv('REALM_ID', 1),
        ];
    }

    private static function defaults(): array
    {
        return [
            'db' => [
                'host' => self::baseEnv('DB_HOST', '127.0.0.1'),
                'port' => (int)self::baseEnv('DB_PORT', 3306),
                'user' => self::baseEnv('DB_USER', ''),
                'pass' => self::baseEnv('DB_PASS', ''),
                'databases' => [
                    'auth'       => self::baseEnv('DB_AUTH', 'acore_auth'),
                    'world'      => self::baseEnv('DB_WORLD', 'acore_world'),
                    'characters' => self::baseEnv('DB_CHARACTERS', 'acore_characters'),
                ],
            ],
            'servers' => [
                'auth' => [
                    'host' => self::baseEnv('AUTH_HOST', '127.0.0.1'),
                    'port' => (int)self::baseEnv('AUTH_PORT', 3724),
                ],
                'worlds' => [
                    [
                        'name' => 'Default Realm',
                        'host' => self::baseEnv('WORLD_HOST', '127.0.0.1'),
                        'port' => (int)self::baseEnv('WORLD_PORT', 8085),
                        'id'   => (int)self::baseEnv('REALM_ID', 1),
                    ],
                ],
            ],
            'soap' => [
                'host'    => self::baseEnv('SOAP_HOST', self::baseEnv('WORLD_HOST', '127.0.0.1')),
                'port'    => (int)self::baseEnv('SOAP_PORT', 7878),
                'user'    => self::baseEnv('SOAP_USER', ''),
                'pass'    => self::baseEnv('SOAP_PASS', ''),
                'uri'     => self::baseEnv('SOAP_URI', 'urn:ACSOAP'),
                'scheme'  => self::baseEnv('SOAP_SCHEME', 'http'),
                'timeout' => (int)self::baseEnv('SOAP_TIMEOUT', 10),
            ],
        ];
    }

    private static function envMap(): array
    {
        return [
            'DB_HOST'        => 'db.host',
            'DB_PORT'        => 'db.port',
            'DB_USER'        => 'db.user',
            'DB_PASS'        => 'db.pass',
            'DB_AUTH'        => 'db.databases.auth',
            'DB_WORLD'       => 'db.databases.world',
            'DB_CHARACTERS'  => 'db.databases.characters',
            'AUTH_HOST'      => 'servers.auth.host',
            'AUTH_PORT'      => 'servers.auth.port',
            'WORLD_HOST'     => 'servers.worlds.0.host',
            'WORLD_PORT'     => 'servers.worlds.0.port',
            'REALM_ID'       => 'servers.worlds.0.id',
            'SOAP_HOST'      => 'soap.host',
            'SOAP_PORT'      => 'soap.port',
            'SOAP_USER'      => 'soap.user',
            'SOAP_PASS'      => 'soap.pass',
            'SOAP_URI'       => 'soap.uri',
            'SOAP_SCHEME'    => 'soap.scheme',
            'SOAP_TIMEOUT'   => 'soap.timeout',
        ];
    }

    private static function loadFile(): array
    {
        if (!is_file(self::FILE)) {
            return [];
        }

        $raw = file_get_contents(self::FILE);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function merge(array $base, array $override): array
    {
        $result = $base;
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && self::isAssoc($value) && self::isAssoc($base[$key])) {
                $result[$key] = self::merge($base[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private static function isAssoc(array $arr): bool
    {
        if ($arr === []) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private static function valueByPath(array $data, string $path)
    {
        $parts = explode('.', $path);
        $cursor = $data;
        foreach ($parts as $part) {
            $key = is_numeric($part) ? (int)$part : $part;
            if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
                return null;
            }
            $cursor = $cursor[$key];
        }
        return $cursor;
    }

    private static function baseEnv(string $key, $default = null)
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($val !== false && $val !== null) ? $val : $default;
    }
}
