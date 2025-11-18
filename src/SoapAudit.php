<?php
declare(strict_types=1);

namespace App;

/**
 * Lightweight audit log for SOAP commands issued via the portal.
 * Stored as newline-delimited JSON for easy parsing and tailing.
 */
final class SoapAudit
{
    private const MAX_BYTES = 200000; // cap reads to ~200KB to avoid huge memory use

    private static function path(): string
    {
        $dir = dirname(__DIR__) . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/soap-commands.log';
    }

    /**
     * Append a log entry.
     *
     * @param array       $user     ['id','username','gmlevel','role']
     * @param string      $command
     * @param string|null $result
     * @param string|null $error
     */
    public static function append(array $user, string $command, ?string $result, ?string $error): void
    {
        $entry = [
            'ts'       => time(),
            'user_id'  => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
            'gmlevel'  => $user['gmlevel'] ?? null,
            'command'  => $command,
            'result'   => $result,
            'error'    => $error,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        @file_put_contents(self::path(), $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Return the most recent N entries (default 20).
     *
     * @return array<array<string,mixed>>
     */
    public static function recent(int $limit = 20): array
    {
        $path = self::path();
        if (!is_file($path)) return [];

        // Read only the last MAX_BYTES to avoid loading very large files.
        $size = filesize($path);
        $offset = ($size !== false && $size > self::MAX_BYTES) ? $size - self::MAX_BYTES : 0;
        $fh = fopen($path, 'r');
        if (!$fh) return [];

        if ($offset > 0) {
            fseek($fh, $offset);
            fgets($fh); // discard partial line
        }

        $lines = [];
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') continue;
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $lines[] = $decoded;
            }
        }
        fclose($fh);

        $lines = array_slice($lines, -$limit);
        return array_reverse($lines); // newest first
    }
}
