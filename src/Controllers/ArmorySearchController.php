<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use PDO;

/**
 * JSON search endpoint backing the Armory autocomplete/grid.
 */
final class ArmorySearchController
{
    /**
     * Handles GET /armory/search?q=... returning character matches.
     */
    public function __invoke(): void
    {
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $worldDb = Db::env('DB_WORLD', 'acore_world');
        $authDb  = Db::env('DB_AUTH', 'acore_auth');

        header('Content-Type: application/json; charset=utf-8');

        if (!preg_match('/^[A-Za-z0-9_]+$/', $worldDb)) {
            echo json_encode([
                'results' => [],
                'error'   => 'Invalid world database name.',
            ]);
            return;
        }

        $q = $_GET['q'] ?? '';
        $q = trim($q);

        if ($q === '' || strlen($q) < 3) {
            echo json_encode([
                'results' => [],
                'error'   => 'Query must be at least 3 characters.',
            ]);
            return;
        }

        try {
            $pdo  = Db::pdo($charsDb);
            $like = $q . '%';

            // NOTE: Now includes account.id >= 301 filter
            $sql = "
                SELECT
                    c.guid,
                    c.name,
                    c.level,
                    c.class,
                    c.race,
                    c.gender,
                    AVG(it.ItemLevel) AS avg_ilvl,
                    CASE c.class
                        WHEN 1  THEN 'Warrior'
                        WHEN 2  THEN 'Paladin'
                        WHEN 3  THEN 'Hunter'
                        WHEN 4  THEN 'Rogue'
                        WHEN 5  THEN 'Priest'
                        WHEN 6  THEN 'Death Knight'
                        WHEN 7  THEN 'Shaman'
                        WHEN 8  THEN 'Mage'
                        WHEN 9  THEN 'Warlock'
                        WHEN 11 THEN 'Druid'
                        ELSE CONCAT('Class ', c.class)
                    END AS class_name,
                    CASE c.race
                        WHEN 1  THEN 'Human'
                        WHEN 2  THEN 'Orc'
                        WHEN 3  THEN 'Dwarf'
                        WHEN 4  THEN 'Night Elf'
                        WHEN 5  THEN 'Undead'
                        WHEN 6  THEN 'Tauren'
                        WHEN 7  THEN 'Gnome'
                        WHEN 8  THEN 'Troll'
                        WHEN 10 THEN 'Blood Elf'
                        WHEN 11 THEN 'Draenei'
                        ELSE CONCAT('Race ', c.race)
                    END AS race_name
                FROM characters AS c
                JOIN {$authDb}.account AS a
                  ON a.id = c.account
                 AND a.id >= 301                       -- <<<<<<<<<< LIMIT TO ACCOUNT 301+
                LEFT JOIN character_inventory AS ci
                  ON ci.guid = c.guid
                 AND ci.bag = 0
                 AND ci.slot BETWEEN 0 AND 18
                LEFT JOIN item_instance AS ii
                  ON ii.guid = ci.item
                LEFT JOIN {$worldDb}.item_template AS it
                  ON it.entry = ii.itemEntry
                WHERE c.name LIKE :nameLike
                GROUP BY c.guid
                ORDER BY
                  avg_ilvl DESC,
                  c.level DESC,
                  c.name ASC
                LIMIT 50
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nameLike' => $like]);

            $rows = $stmt->fetchAll();

            $results = [];
            foreach ($rows as $row) {
                $results[] = [
                    'guid'       => (int)$row['guid'],
                    'name'       => $row['name'],
                    'level'      => (int)$row['level'],
                    'class'      => (int)$row['class'],
                    'class_name' => $row['class_name'],
                    'race'       => (int)$row['race'],
                    'gender'     => isset($row['gender']) ? (int)$row['gender'] : 0,
                    'race_name'  => $row['race_name'],
                    'avg_ilvl'   => $row['avg_ilvl'] !== null ? (float)$row['avg_ilvl'] : null,
                ];
            }

            echo json_encode(['results' => $results]);

        } catch (\Throwable $e) {
            echo json_encode([
                'results' => [],
                'error'   => 'Search failed: ' . $e->getMessage(),
            ]);
        }
    }
}
