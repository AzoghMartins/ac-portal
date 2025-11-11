<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;

final class ArmoryController {
    public function __invoke(): void {
        $charsDb = Db::env('DB_CHARACTERS','acore_characters');
        $rows = Db::pdo($charsDb)
            ->query("SELECT name, level, guid FROM characters ORDER BY level DESC, totaltime DESC LIMIT 10")
            ->fetchAll();
        View::render('armory', ['rows' => $rows, 'title' => 'Armory (Top 10)']);
    }
}
