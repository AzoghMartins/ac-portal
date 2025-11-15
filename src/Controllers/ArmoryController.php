<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\View;

/**
 * Displays the main Armory page with search UI and top characters.
 */
final class ArmoryController
{
    /**
     * Renders the armory landing page with the top 10 list.
     */
    public function __invoke(): void
    {
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $worldDb = Db::env('DB_WORLD', 'acore_world');
        $authDb  = Db::env('DB_AUTH', 'acore_auth');

        // Safety: basic validation of DB names
        if (!preg_match('/^[A-Za-z0-9_]+$/', $worldDb)) {
            throw new \RuntimeException('Invalid DB_WORLD name: ' . $worldDb);
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $authDb)) {
            throw new \RuntimeException('Invalid DB_AUTH name: ' . $authDb);
        }

        // Top 10 by average equipped item level
        // Now filtered so only characters belonging to accounts >= 301 are included
        $sql = "
            SELECT
                c.guid,
                c.name,
                c.level,
                c.class,
                c.race,
                c.totaltime,
                AVG(it.ItemLevel) AS avg_ilvl
            FROM characters AS c
            JOIN {$authDb}.account AS a
              ON a.id = c.account
             AND a.id >= 301        -- <<<<<<<<<< Account filter (no bots)
            JOIN character_inventory AS ci
              ON ci.guid = c.guid
             AND ci.bag = 0
             AND ci.slot BETWEEN 0 AND 18
            JOIN item_instance AS ii
              ON ii.guid = ci.item
            JOIN {$worldDb}.item_template AS it
              ON it.entry = ii.itemEntry
            GROUP BY c.guid
            HAVING COUNT(*) > 0
            ORDER BY
              avg_ilvl DESC,
              c.level DESC,
              c.totaltime DESC,
              c.name ASC
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
