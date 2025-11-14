<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Auth {
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], !empty($p["secure"]), !empty($p["httponly"]));
        }
        session_destroy();
    }

    public static function loginWithUsernamePassword(string $username, string $password): bool {
        self::start();
    $authDb = Db::env('DB_AUTH', 'acore_auth');
    $pdo    = Db::pdo($authDb);

    error_log("LOGIN start for user={$username}");

    static $cols = null;
    if ($cols === null) {
        $cols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM account")->fetchAll() as $c) {
            $cols[$c['Field']] = true;
        }
        error_log("LOGIN columns: " . implode(',', array_keys($cols)));
    }

    $u = $username;
    $p = $password;

    if (isset($cols['sha_pass_hash'])) {
        $sha = sha1(strtoupper($u) . ':' . strtoupper($p));
        $st  = $pdo->prepare("SELECT id, username FROM account WHERE username = :u AND sha_pass_hash = :h LIMIT 1");
        $st->execute([':u' => $u, ':h' => $sha]);
        $row = $st->fetch();
        error_log("LOGIN sha path row? " . ($row ? 'yes' : 'no'));
        if ($row) {
            $_SESSION['user'] = self::buildSessionUser((int)$row['id'], $row['username'], $pdo);
            return true;
        }
    }

    // 2) SRP6 (salt+verifier)
    if (isset($cols['salt']) && isset($cols['verifier'])) {
        $st = $pdo->prepare("SELECT id, username, salt, verifier FROM account WHERE username = :u LIMIT 1");
        $st->execute([':u' => $u]);
        $row = $st->fetch();

        if ($row && function_exists('gmp_init')) {
            $ok = self::srpVerify($u, $p, $row['salt'], $row['verifier']);
            if ($ok) {
                $_SESSION['user'] = self::buildSessionUser((int)$row['id'], $row['username'], $pdo);
                return true;
            }
        }
    }

        return false;
    }

    private static function srpVerify(string $username, string $password, string $saltBin, string $verifierBin): bool {
       // Constants used by WoW/AzerothCore (SRP-6, SHA1)
        // Safe prime N (hex) and generator g=7
        $Nhex = '894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7';
        $N = gmp_init($Nhex, 16);
        $g = gmp_init(7, 10);

        // 1) H1 = SHA1( UPPER(USERNAME) + ":" + UPPER(PASSWORD) )
        $ihash = sha1(strtoupper($username) . ':' . strtoupper($password), true); // raw 20 bytes

        // 2) x = SHA1( salt || H1 ), then little-endian to integer
        $xH = sha1($saltBin . $ihash, true);           // raw 20 bytes
        $xLE = strrev($xH);                             // WoW uses little-endian for SRP ints
        $x   = gmp_import($xLE, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);

        // 3) v = g^x mod N
        $v = gmp_powm($g, $x, $N);

        // 4) Export v as 32-byte little-endian blob to compare with stored verifier
        $vBE = gmp_export($v, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        if ($vBE === false) $vBE = "";                 // safety
        // pad to 32 bytes big-endian, then flip to little-endian
        $vBE = str_pad($vBE, 32, "\0", STR_PAD_LEFT);
        $vLE = strrev($vBE);

        // Stored verifier in AzerothCore is a 32-byte little-endian blob
        return hash_equals($vLE, $verifierBin);
    }

    private static function buildSessionUser(int $accountId, string $username, PDO $pdoAuth): array {
        // Resolve GM level (account_access)
        $gmLevel = 0;
        try {
            $st = $pdoAuth->prepare("SELECT MAX(gmlevel) AS g FROM account_access WHERE id = :id");
            $st->execute([':id' => $accountId]);
            $gmLevel = (int)($st->fetch()['g'] ?? 0);
        } catch (\Throwable $e) {
            $gmLevel = 0;
        }

        $role = 'user';
        if ($gmLevel >= 1) $role = 'gm';
        if ($gmLevel >= 3) $role = 'admin';

        return [
            'id'       => $accountId,
            'username' => $username,
            'gmlevel'  => $gmLevel,
            'role'     => $role,
        ];
    }

    public static function requireLogin(string $redirectTo = '/account'): void {
        self::start();
        if (!self::user()) {
            header('Location: /login?redirect=' . rawurlencode($redirectTo));
            exit;
        }
    }
}
