<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;
use App\WorldServerSoap;
use App\SoapAudit;

/**
 * Admin-only SOAP console for issuing worldserver commands via the portal.
 */
final class SoapConsoleController
{
    public function __invoke(): void
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Location: /login?redirect=/admin/soap');
            exit;
        }

        if (($user['gmlevel'] ?? 0) < 3) {
            header('Location: /');
            exit;
        }

        $command = '';
        $result  = null;
        $error   = null;
        $justLogged = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $command = trim($_POST['command'] ?? '');

            if ($command === '') {
                $error = 'Command cannot be empty.';
            } else {
                try {
                    $result = WorldServerSoap::execute($command);
                    $justLogged = true;
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }

                SoapAudit::append($user, $command, $result, $error);
            }
        }

        $quickCommands = [
            'reload config',
            'server info',
            'account onlinelist',
            'account set gmlevel <account> <level>',
        ];

        $activity = SoapAudit::recent(20);

        View::render('soap-console', [
            'title'         => 'SOAP Console',
            'user'          => $user,
            'command'       => $command,
            'result'        => $result,
            'error'         => $error,
            'quickCommands' => $quickCommands,
            'activity'      => $activity,
            'justLogged'    => $justLogged,
        ]);
    }
}
