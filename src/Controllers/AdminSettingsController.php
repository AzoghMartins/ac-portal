<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Db;
use App\View;

/**
 * Admin-only panel for editing server/database settings without touching .env.
 */
final class AdminSettingsController
{
    public function __invoke(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            header('Location: /login?redirect=/admin/settings');
            exit;
        }
        if (($user['gmlevel'] ?? 0) < 3) {
            header('Location: /');
            exit;
        }

        $errors = [];
        $saved  = false;
        $settings = Config::all();
        $realmNames = $this->realmNames();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$payload, $errors] = $this->validatedData($_POST);

            // Merge user input into what we render so the form keeps values on errors.
            $settings = $this->mergeAssoc($settings, $payload);

            if (empty($errors)) {
                Config::save($payload);
                $saved    = true;
                $settings = Config::all(); // reload to reflect canonical saved data
            }
        }

        View::render('admin-settings', [
            'title'    => 'Server Settings',
            'settings' => $settings,
            'errors'   => $errors,
            'saved'    => $saved,
            'realmNames' => $realmNames,
        ]);
    }

    /**
     * @return array{0:array,1:array} [$config, $errors]
     */
    private function validatedData(array $input): array
    {
        $errors = [];

        $authHost = trim($input['auth_host'] ?? '');
        $authPortStr = trim($input['auth_port'] ?? '');

        if ($authHost === '') $errors[] = 'Auth server host is required.';
        if ($authPortStr === '' || !ctype_digit($authPortStr) || (int)$authPortStr <= 0) {
            $errors[] = 'Auth server port must be a positive number.';
        }

        $worlds = $this->validateWorlds($input, $errors);

        $config = [
            'servers' => [
                'auth' => [
                    'host' => $authHost,
                    'port' => (int)$authPortStr,
                ],
                'worlds' => $worlds,
            ],
        ];

        return [$config, $errors];
    }

    /**
     * @param array<int,string> $errors (by ref)
     * @return array<int,array{name:string,host:string,port:int,id:int}>
     */
    private function validateWorlds(array $input, array &$errors): array
    {
        $names = $input['world_name'] ?? [];
        $hosts = $input['world_host'] ?? [];
        $ports = $input['world_port'] ?? [];
        $ids   = $input['world_id'] ?? [];

        if (!is_array($names)) $names = [];
        if (!is_array($hosts)) $hosts = [];
        if (!is_array($ports)) $ports = [];
        if (!is_array($ids)) $ids = [];

        $count = max(count($names), count($hosts), count($ports), count($ids));
        $worlds = [];

        for ($i = 0; $i < $count; $i++) {
            $name = trim((string)($names[$i] ?? ''));
            $host = trim((string)($hosts[$i] ?? ''));
            $portStr = trim((string)($ports[$i] ?? ''));
            $idStr   = trim((string)($ids[$i] ?? ''));

            if ($name === '' && $host === '' && $portStr === '' && $idStr === '') {
                continue; // skip empty rows
            }

            if ($host === '') {
                $errors[] = sprintf('World server #%d host is required.', $i + 1);
                continue;
            }
            if ($portStr === '' || !ctype_digit($portStr) || (int)$portStr <= 0) {
                $errors[] = sprintf('World server #%d port must be a positive number.', $i + 1);
                continue;
            }
            if ($idStr !== '' && !ctype_digit($idStr)) {
                $errors[] = sprintf('World server #%d realm ID must be numeric.', $i + 1);
                continue;
            }

            $worlds[] = [
                'name' => $name !== '' ? $name : sprintf('Realm %d', $i + 1),
                'host' => $host,
                'port' => (int)$portStr,
                'id'   => $idStr !== '' ? (int)$idStr : ($i === 0 ? 1 : $i + 1),
            ];
        }

        if (count($worlds) === 0) {
            $errors[] = 'Add at least one world server entry.';
        }

        return $worlds;
    }

    private function mergeAssoc(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && $this->isAssoc($value) && $this->isAssoc($base[$key])) {
                $base[$key] = $this->mergeAssoc($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    private function isAssoc(array $arr): bool
    {
        if ($arr === []) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function realmNames(): array
    {
        try {
            $authDb = Db::env('DB_AUTH', 'acore_auth');
            $pdo    = Db::pdo($authDb);
            $rows = $pdo->query('SELECT id, name FROM realmlist ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $row) {
                $id = isset($row['id']) ? (int)$row['id'] : null;
                if ($id === null) continue;
                $map[$id] = (string)($row['name'] ?? '');
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
