<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;

final class ArmoryController {
    public function __invoke(): void {
        $charsDb = Db::env('DB_CHARACTERS','acore_characters');

        $sql = "
            SELECT
                guid,
                name,
                level,
                class,
                race,
                gender,
                totaltime
            FROM characters
            ORDER BY level DESC, totaltime DESC, name ASC
            LIMIT 10
        ";

        $rows = Db::pdo($charsDb)
            ->query($sql)
            ->fetchAll();

        View::render('armory', [
            'rows'  => $rows,
            'title' => 'Armory (Top 10)',
        ]);
    }
}
