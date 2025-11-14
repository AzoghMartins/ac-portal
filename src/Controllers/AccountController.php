<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use PDO;

final class AccountController
{
    public function __invoke(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            // Optional: add a flash later; for now simple redirect
            header('Location: /login?redirect=/account');
            exit;
        }

        // DBs
        $authDb  = Db::env('DB_AUTH', 'acore_auth');
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');

        // Role detection from account_access
        $pdoAuth = Db::pdo($authDb);
        $stmt    = $pdoAuth->prepare("SELECT MAX(gmlevel) AS gm FROM account_access WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        $row     = $stmt->fetch(PDO::FETCH_ASSOC);
        $gmLevel = (int)($row['gm'] ?? 0);

        $role = match (true) {
            $gmLevel >= 3 => 'Admin',
            $gmLevel === 2 => 'Game Master',
            $gmLevel === 1 => 'Moderator',
            default => 'User',
        };

        // Characters for this account
        $pdoChars = Db::pdo($charsDb);
        $chars    = $pdoChars->prepare("
            SELECT guid, name, level, class, race, gender, totaltime
            FROM characters
            WHERE account = :acct
            ORDER BY level DESC, totaltime DESC, name ASC
        ");
        $chars->execute([':acct' => $user['id']]);
        $characters = $chars->fetchAll(PDO::FETCH_ASSOC) ?: [];

        View::render('account', [
            'title'      => 'My Account',
            'user'       => $user,
            'role'       => $role,
            'gmLevel'    => $gmLevel,
            'characters' => $characters,
        ]);
    }
}
