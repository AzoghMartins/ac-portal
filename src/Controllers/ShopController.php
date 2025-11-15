<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;

/**
 * Placeholder shop endpoint until the purchasing UI is implemented.
 */
final class ShopController
{
    /**
     * Renders the in-progress shop view.
     */
    public function __invoke(): void
    {
        // Ensure session + user context
        Auth::start();
        $viewer = Auth::user(); // ['id','username','gmlevel','role'] or null

        $guid = isset($_GET['guid']) ? (int)$_GET['guid'] : null;
        $tab  = isset($_GET['tab']) ? (string)$_GET['tab'] : null;

        View::render('shop', [
            'title'  => 'Shop (Under Construction)',
            'viewer' => $viewer,
            'guid'   => $guid,
            'tab'    => $tab,
        ]);
    }
}
