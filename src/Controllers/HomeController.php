<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;

final class HomeController {
    public function __invoke(): void {
        $authDb   = Db::env('DB_AUTH','acore_auth');
        $accounts = (int)(Db::pdo($authDb)->query('SELECT COUNT(*) c FROM account')->fetch()['c'] ?? 0);
        View::render('home', ['accounts' => $accounts, 'title' => 'Home']);
    }
}
