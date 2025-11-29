<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use PDO;

/**
 * Read-only auction house browser for logged-in users.
 */
final class AuctionController
{
    private const PAGE_SIZE = 50;

    public function __invoke(): void
    {
        Auth::requireLogin('/auction');

        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $worldDb = Db::env('DB_WORLD', 'acore_world');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PAGE_SIZE;

        $filters = $this->parseFilters();
        [$orderSql, $sort, $dir] = $this->orderClause();

        $pdoChars = Db::pdo($charsDb);
        $cols = $this->auctionColumns($pdoChars);

        [$whereSql, $params, $joins] = $this->buildWhere($filters, $cols);

        $limit = self::PAGE_SIZE;

        $sql = "
            SELECT
                a.{$cols['id']}                   AS auction_id,
                a.{$cols['itemGuid']}             AS item_guid,
                a.{$cols['owner']}                AS owner_guid,
                a.{$cols['buyout']}               AS buyout,
                a.{$cols['startbid']}             AS startbid,
                a.{$cols['lastbid']}              AS lastbid,
                a.{$cols['time']}                 AS expires_at,
                " . ($cols['house'] ? "a.{$cols['house']} AS house_id," : "NULL AS house_id,") . "
                " . ($cols['auctioneer'] ? "a.{$cols['auctioneer']} AS auctioneer_guid," : "NULL AS auctioneer_guid,") . "
                ii.itemEntry                      AS item_entry,
                ii.count                          AS stack_count,
                it.name                           AS item_name,
                it.Quality                        AS quality,
                it.ItemLevel                      AS ilevel,
                it.RequiredLevel                  AS req_level,
                it.class                          AS item_class,
                it.subclass                       AS item_subclass,
                it.InventoryType                  AS inventory_type,
                it.displayid                      AS displayid,
                it.armor                          AS armor,
                it.delay                          AS delay,
                it.dmg_min1                       AS dmg_min1,
                it.dmg_max1                       AS dmg_max1,
                it.socketColor_1                  AS socketColor_1,
                it.socketColor_2                  AS socketColor_2,
                it.socketColor_3                  AS socketColor_3,
                it.stat_type1, it.stat_value1,
                it.stat_type2, it.stat_value2,
                it.stat_type3, it.stat_value3,
                it.stat_type4, it.stat_value4,
                it.stat_type5, it.stat_value5,
                it.stat_type6, it.stat_value6,
                it.stat_type7, it.stat_value7,
                it.stat_type8, it.stat_value8,
                it.stat_type9, it.stat_value9,
                it.stat_type10, it.stat_value10,
                c.name                            AS seller_name
            FROM auctionhouse a
            JOIN item_instance ii ON ii.guid = a.{$cols['itemGuid']}
            JOIN {$worldDb}.item_template it ON it.entry = ii.itemEntry
            LEFT JOIN characters c ON c.guid = a.{$cols['owner']}
            {$joins}
            {$whereSql}
            {$orderSql}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdoChars->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Total count for pagination
        $countSql = "
            SELECT COUNT(*) AS c
            FROM auctionhouse a
            JOIN item_instance ii ON ii.guid = a.{$cols['itemGuid']}
            JOIN {$worldDb}.item_template it ON it.entry = ii.itemEntry
            {$joins}
            {$whereSql}
        ";
        $countStmt = $pdoChars->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?? 0);
        $pages = (int)max(1, (int)ceil($total / self::PAGE_SIZE));

        View::render('auction', [
            'title'      => 'Auction House',
            'rows'       => $rows,
            'filters'    => $filters,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    /**
     * Parse filter inputs from GET.
     */
    private function parseFilters(): array
    {
        $min = isset($_GET['min_level']) ? (int)$_GET['min_level'] : 0;
        $max = isset($_GET['max_level']) ? (int)$_GET['max_level'] : 80;
        $min = max(0, min(80, $min));
        $max = max(0, min(80, $max));
        return [
            'q'          => trim((string)($_GET['q'] ?? '')),
            'min_level'  => $min,
            'max_level'  => $max,
            'faction'    => isset($_GET['faction']) ? (string)$_GET['faction'] : '',
            'category'   => isset($_GET['category']) ? (string)$_GET['category'] : '',
            'subcategory'=> isset($_GET['subcategory']) ? (string)$_GET['subcategory'] : '',
        ];
    }

    /**
     * Determine auctionhouse column names, handling schema variations.
     *
     * @return array{id:string,itemGuid:string,owner:string,buyout:string,startbid:string,lastbid:string,time:string,house:?string,auctioneer:?string}
     */
    private function auctionColumns(PDO $pdo): array
    {
        $cols = [];
        foreach ($pdo->query('SHOW COLUMNS FROM auctionhouse')->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cols[$c['Field']] = true;
        }

        $pick = static function (array $choices) use ($cols): ?string {
            foreach ($choices as $c) if (isset($cols[$c])) return $c;
            return null;
        };

        return [
            'id'         => $pick(['id']) ?? 'id',
            'itemGuid'   => $pick(['itemguid','item_guid','item_guid']) ?? 'itemguid',
            'owner'      => $pick(['itemowner','owner','seller']) ?? 'itemowner',
            'buyout'     => $pick(['buyoutprice','buyout']) ?? 'buyoutprice',
            'startbid'   => $pick(['startbid','startBid']) ?? 'startbid',
            'lastbid'    => $pick(['lastbid','lastBid','curbid','bid']) ?? 'lastbid',
            'time'       => $pick(['time','expires']) ?? 'time',
            'house'      => $pick(['auctionhouseid','houseid']),
            'auctioneer' => $pick(['auctioneerguid','auctioneer']),
        ];
    }

    /**
     * Build WHERE clause and joins for filters.
     *
     * @return array{0:string,1:array,2:string} [$whereSql,$params,$joins]
     */
    private function buildWhere(array $filters, array $cols): array
    {
        $where = [];
        $params = [];
        $joins = '';

        if ($filters['q'] !== '') {
            $where[] = 'it.name LIKE :q';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['min_level'])) {
            $where[] = 'it.RequiredLevel >= :minLevel';
            $params[':minLevel'] = (int)$filters['min_level'];
        }
        if (!empty($filters['max_level'])) {
            $where[] = 'it.RequiredLevel <= :maxLevel';
            $params[':maxLevel'] = (int)$filters['max_level'];
        }

        // Faction filter using house id when available.
        if ($filters['faction'] && $cols['house']) {
            $f = $filters['faction'];
            // Common house ids across cores: Alliance: 1/3/7, Horde: 2/6, Neutral: 6/7.
            $map = [
                'alliance' => [1, 3, 7],
                'horde'    => [2, 6],
                'neutral'  => [6, 7],
            ];
            if (isset($map[$f])) {
                $in = $map[$f];
                $ph = [];
                foreach ($in as $idx => $val) {
                    $name = ':f' . $idx;
                    $ph[] = $name;
                    $params[$name] = $val;
                }
                $where[] = "a.{$cols['house']} IN (" . implode(',', $ph) . ")";
            }
        }

        // Category/subcategory to item class/subclass/inventory filters.
        [$catWhere, $catParams] = $this->categoryFilter($filters['category'], $filters['subcategory']);
        if ($catWhere) {
            $where[] = $catWhere;
            $params = array_merge($params, $catParams);
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$whereSql, $params, $joins];
    }

    /**
     * Determine ORDER BY based on query params.
     *
     * @return array{0:string,1:string,2:string} [$orderSql,$sort,$dir]
     */
    private function orderClause(): array
    {
        $sort = $_GET['sort'] ?? 'time';
        $dir  = strtolower($_GET['dir'] ?? 'asc');
        $dir  = $dir === 'desc' ? 'DESC' : 'ASC';

        // Map to SQL expressions
        $map = [
            'name'   => 'it.name',
            'req'    => 'it.RequiredLevel',
            'ilevel' => 'it.ItemLevel',
            'stack'  => 'ii.count',
            'buyout' => 'a.buyoutprice',
            'bid'    => 'COALESCE(NULLIF(a.lastbid,0), a.startbid)',
            'seller' => 'c.name',
            'time'   => 'a.' . $this->auctionColumns(Db::pdo(Db::env('DB_CHARACTERS', 'acore_characters')))['time'],
            'house'  => 'a.' . ($this->auctionColumns(Db::pdo(Db::env('DB_CHARACTERS', 'acore_characters')))['house'] ?? $this->auctionColumns(Db::pdo(Db::env('DB_CHARACTERS', 'acore_characters')))['time']),
        ];

        $orderExpr = $map[$sort] ?? $map['time'];
        if (str_contains($orderExpr, 'house') && !str_contains($orderExpr, 'auctionhouse')) {
            $orderExpr = $map['time'];
            $sort = 'time';
        }

        return ['ORDER BY ' . $orderExpr . ' ' . $dir, $sort, strtolower($dir)];
    }

    /**
     * Map category/subcategory filters to SQL.
     *
     * @return array{0:string|null,1:array}
     */
    private function categoryFilter(string $cat, string $sub): array
    {
        $params = [];
        switch ($cat) {
            case 'armor':
                $map = [
                    'cloth'   => 1,
                    'leather' => 2,
                    'mail'    => 3,
                    'plate'   => 4,
                ];
                if (isset($map[$sub])) {
                    $params[':sub'] = $map[$sub];
                    return ['(it.class = 4 AND it.subclass = :sub)', $params];
                }
                return ['(it.class = 4)', $params];

            case 'weapon':
                $weaponMap = [
                    'dagger'       => 15,
                    '1h_sword'     => 7,
                    '1h_axe'       => 0,
                    '1h_mace'      => 4,
                    '2h_sword'     => 8,
                    '2h_axe'       => 1,
                    '2h_mace'      => 5,
                    'staff'        => 10,
                    'polearm'      => 6,
                    'fist'         => 13,
                    'bow'          => 2,
                    'gun'          => 3,
                    'thrown'       => 16,
                ];
                if (isset($weaponMap[$sub])) {
                    $params[':wsub'] = $weaponMap[$sub];
                    return ['(it.class = 2 AND it.subclass = :wsub)', $params];
                }
                return ['(it.class = 2)', $params];

            case 'accessory':
                $invMap = [
                    'necklace' => 2,
                    'ring'     => 11,
                    'trinket'  => 12,
                ];
                if (isset($invMap[$sub])) {
                    $params[':inv'] = $invMap[$sub];
                    return ['(it.InventoryType = :inv)', $params];
                }
                return ['(it.InventoryType IN (2,11,12))', $params];

            case 'gem':
                $gemMap = [
                    'meta'  => 6,
                    'red'   => 0,
                    'yellow'=> 2,
                    'blue'  => 1,
                ];
                if (isset($gemMap[$sub])) {
                    $params[':gsub'] = $gemMap[$sub];
                    return ['(it.class = 3 AND it.subclass = :gsub)', $params];
                }
                return ['(it.class = 3)', $params];

            case 'craft':
                $tradeMap = [
                    'alchemy'       => 10, // elemental
                    'blacksmithing' => 7,  // metal & stone
                    'engineering'   => 1,  // parts
                    'herbalism'     => 9,  // herb
                    'enchanting'    => 11, // enchanting
                    'leatherworking'=> 6,  // leather
                    'tailoring'     => 5,  // cloth
                    'jewelcrafting' => 4,  // jewelcrafting
                ];
                if (isset($tradeMap[$sub])) {
                    $params[':tsub'] = $tradeMap[$sub];
                    return ['(it.class = 7 AND it.subclass = :tsub)', $params];
                }
                return ['(it.class = 7)', $params];

            case 'quest':
                return ['(it.class = 12)', $params];
        }

        return [null, $params];
    }
}
