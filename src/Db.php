<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Db {
    private static ?array $pool = null;

    public static function env(string $k, $d = null) {
        return $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k) ?: $d;
    }

    private static function dsn(string $db): string {
        $socket = self::env('DB_SOCKET');
        if ($socket) return sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socket, $db);
        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::env('DB_HOST','127.0.0.1'),
            self::env('DB_PORT','3306'),
            $db
        );
    }

    public static function pdo(string $db): PDO {
        if (isset(self::$pool[$db])) return self::$pool[$db];
        $pdo = new PDO(self::dsn($db), self::env('DB_USER'), self::env('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return self::$pool[$db] = $pdo;
    }

    public static function pdoWrite(string $db): PDO {
        $key = $db . ':w';
        if (isset(self::$pool[$key])) {
            return self::$pool[$key];
        }

        // Use DB_USER_WRITE / DB_PASS_WRITE if set, otherwise fall back to DB_USER / DB_PASS
        $user = self::env('DB_USER_WRITE', self::env('DB_USER'));
        $pass = self::env('DB_PASS_WRITE', self::env('DB_PASS'));

        $pdo = new PDO(self::dsn($db), $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pool[$key] = $pdo;
    }



}
