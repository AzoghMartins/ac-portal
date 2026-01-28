<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use PDO;

/**
 * Shop storefront and purchase handling for Marks.
 */
final class ShopController
{
    private const TIER_SKIP_COSTS = [
        '0'   => 25,
        '1'   => 40,
        '2'   => 55,
        '3'   => 70,
        '4'   => 55,
        '5'   => 65,
        '6'   => 85,
        '7'   => 25,
        '7.5' => 100,
        '8'   => 75,
        '9'   => 110,
        '10'  => 110,
        '11'  => 60,
        '12'  => 125,
    ];

    private const MAX_TIER_SKIP = 12;
    private const SKU_TIER_SKIP = 'TIER_SKIP';
    private const SKU_START_TBC = 'START_TBC_AT_60';

    public function __invoke(): void
    {
        $this->index();
    }

    public function index(): void
    {
        Auth::requireLogin('/shop');
        $user = Auth::user();
        $accountId = (int)($user['id'] ?? 0);

        $selectedGuid = isset($_GET['guid']) ? (int)$_GET['guid'] : null;

        $products = $this->fetchActiveProducts();
        $grouped = [];
        foreach ($products as $product) {
            $cat = $product['category'] ?? 'General';
            $grouped[$cat][] = $product;
        }

        View::render('shop', [
            'title' => 'Shop',
            'marksBalance' => $this->marksBalance($accountId),
            'productsByCategory' => $grouped,
            'selectedGuid' => $selectedGuid,
        ]);
    }

    public function product(string $sku): void
    {
        Auth::requireLogin('/shop');
        $user = Auth::user();
        $accountId = (int)($user['id'] ?? 0);

        $product = $this->fetchProduct($sku);
        if (!$product || empty($product['active'])) {
            http_response_code(404);
            echo 'Product not found.';
            return;
        }

        $characters = $this->accountCharacters($accountId);
        $selectedGuid = isset($_GET['guid']) ? (int)$_GET['guid'] : null;
        $targetTier = $this->normalizeTierInput($_GET['target_tier'] ?? null);

        $selectedCharacter = null;
        $error = null;
        $preview = null;

        if ($selectedGuid) {
            $selectedCharacter = $this->loadCharacter($selectedGuid, $accountId);
            if (!$selectedCharacter) {
                $error = 'Selected character was not found on your account.';
            }
        }

        if ($product['sku'] === self::SKU_TIER_SKIP && $selectedCharacter && $targetTier !== null) {
            $preview = $this->tierSkipPreview($selectedCharacter, $targetTier);
            if (isset($preview['error'])) {
                $error = $preview['error'];
                $preview = null;
            }
        }

        $this->renderProductView($product, $characters, $selectedCharacter, $targetTier, $preview, $error, null, $accountId);
    }

    public function buy(): void
    {
        Auth::requireLogin('/shop');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed.';
            return;
        }

        $user = Auth::user();
        $accountId = (int)($user['id'] ?? 0);

        $sku = trim((string)($_POST['sku'] ?? ''));
        if ($sku === '') {
            http_response_code(400);
            echo 'Missing product.';
            return;
        }

        $product = $this->fetchProduct($sku);
        if (!$product || empty($product['active'])) {
            http_response_code(404);
            echo 'Product not found.';
            return;
        }

        $characters = $this->accountCharacters($accountId);
        $selectedGuid = isset($_POST['character_guid']) ? (int)$_POST['character_guid'] : null;
        $targetTier = $this->normalizeTierInput($_POST['target_tier'] ?? null);

        $selectedCharacter = null;
        $error = null;
        $preview = null;

        if ($product['scope'] === 'character') {
            if (!$selectedGuid) {
                $error = 'Select a character for this purchase.';
            } else {
                $selectedCharacter = $this->loadCharacter($selectedGuid, $accountId);
                if (!$selectedCharacter) {
                    $error = 'Selected character was not found on your account.';
                }
            }
        }

        $price = null;
        $details = [
            'sku' => $product['sku'],
            'name' => $product['name'],
            'scope' => $product['scope'],
            'type' => $product['price_type'],
        ];
        if ($selectedCharacter) {
            $details['character'] = [
                'guid' => (int)$selectedCharacter['guid'],
                'name' => (string)($selectedCharacter['name'] ?? ''),
                'level' => (int)($selectedCharacter['level'] ?? 0),
            ];
        }

        if ($error === null) {
            if ($product['price_type'] === 'fixed') {
                $price = isset($product['price_marks']) ? (int)$product['price_marks'] : null;
                if ($price === null || $price <= 0) {
                    $error = 'Product price is not configured.';
                }
            } elseif ($product['price_type'] === 'tier_skip') {
                if (!$selectedCharacter) {
                    $error = 'Select a character to calculate tier skip pricing.';
                } elseif ($targetTier === null) {
                    $error = 'Select a desired tier to auto complete.';
                } else {
                    $preview = $this->tierSkipPreview($selectedCharacter, $targetTier);
                    if (isset($preview['error'])) {
                        $error = $preview['error'];
                    } else {
                        $price = (int)($preview['price'] ?? 0);
                        $details['tier_skip'] = $preview;
                    }
                }
            } else {
                $error = 'Unknown product pricing.';
            }
        }

        if ($error !== null) {
            $this->renderProductView($product, $characters, $selectedCharacter, $targetTier, $preview, $error, null, $accountId);
            return;
        }

        if ($price === null || $price <= 0) {
            $this->renderProductView($product, $characters, $selectedCharacter, $targetTier, $preview, 'Invalid price for purchase.', null, $accountId);
            return;
        }

        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdo = Db::pdoWrite($portalDb);

        try {
            $pdo->beginTransaction();

            $balance = $this->marksBalance($accountId, $pdo, true);
            if ($balance < $price) {
                $pdo->rollBack();
                $this->renderProductView($product, $characters, $selectedCharacter, $targetTier, $preview, 'Not enough Marks to complete this purchase.', null, $accountId, $balance);
                return;
            }

            $purchaseStmt = $pdo->prepare('
                INSERT INTO shop_purchase
                  (account_id, character_guid, sku, product_name, price_marks, status, details)
                VALUES
                  (:account_id, :character_guid, :sku, :product_name, :price_marks, :status, :details)
            ');
            $purchaseStmt->execute([
                ':account_id' => $accountId,
                ':character_guid' => $selectedCharacter ? (int)$selectedCharacter['guid'] : null,
                ':sku' => $product['sku'],
                ':product_name' => $product['name'],
                ':price_marks' => $price,
                ':status' => 'paid',
                ':details' => json_encode($details, JSON_UNESCAPED_SLASHES),
            ]);

            $purchaseId = (int)$pdo->lastInsertId();

            $ledgerStmt = $pdo->prepare('
                INSERT INTO marks_ledger
                  (account_id, delta, reason, purchase_id)
                VALUES
                  (:account_id, :delta, :reason, :purchase_id)
            ');
            $ledgerStmt->execute([
                ':account_id' => $accountId,
                ':delta' => -$price,
                ':reason' => 'purchase:' . $product['sku'],
                ':purchase_id' => $purchaseId,
            ]);

            $fulfillStmt = $pdo->prepare('
                INSERT INTO shop_fulfillment
                  (purchase_id, status)
                VALUES
                  (:purchase_id, :status)
            ');
            $fulfillStmt->execute([
                ':purchase_id' => $purchaseId,
                ':status' => 'queued',
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->renderProductView($product, $characters, $selectedCharacter, $targetTier, $preview, 'Purchase failed: ' . $e->getMessage(), null, $accountId);
            return;
        }

        header('Location: /shop/purchases?new=' . $purchaseId);
        exit;
    }

    public function ledger(): void
    {
        Auth::requireLogin('/shop/ledger');
        $user = Auth::user();
        $accountId = (int)($user['id'] ?? 0);

        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdo = Db::pdo($portalDb);

        $stmt = $pdo->prepare('
            SELECT l.id, l.delta, l.reason, l.purchase_id, l.created_at,
                   p.sku, p.product_name
            FROM marks_ledger l
            LEFT JOIN shop_purchase p ON p.id = l.purchase_id
            WHERE l.account_id = :account_id
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT 200
        ');
        $stmt->execute([':account_id' => $accountId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        View::render('shop-ledger', [
            'title' => 'Marks Ledger',
            'marksBalance' => $this->marksBalance($accountId),
            'rows' => $rows,
        ]);
    }

    public function purchases(): void
    {
        Auth::requireLogin('/shop/purchases');
        $user = Auth::user();
        $accountId = (int)($user['id'] ?? 0);

        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdo = Db::pdo($portalDb);

        $stmt = $pdo->prepare('
            SELECT p.id, p.sku, p.product_name, p.price_marks, p.status,
                   p.character_guid, p.details, p.created_at,
                   f.status AS fulfillment_status, f.updated_at AS fulfillment_updated
            FROM shop_purchase p
            LEFT JOIN shop_fulfillment f ON f.purchase_id = p.id
            WHERE p.account_id = :account_id
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT 200
        ');
        $stmt->execute([':account_id' => $accountId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $newId = isset($_GET['new']) ? (int)$_GET['new'] : null;

        View::render('shop-purchases', [
            'title' => 'My Purchases',
            'marksBalance' => $this->marksBalance($accountId),
            'rows' => $rows,
            'newPurchaseId' => $newId,
        ]);
    }

    private function renderProductView(
        array $product,
        array $characters,
        ?array $selectedCharacter,
        ?string $targetTier,
        ?array $preview,
        ?string $error,
        ?string $success,
        int $accountId,
        ?int $balanceOverride = null
    ): void {
        $nextProgressionLabel = null;
        if ($selectedCharacter && $targetTier !== null && $preview) {
            $nextProgressionLabel = $this->nextProgressionLabel($targetTier);
        }

        View::render('shop-product', [
            'title' => $product['name'] ?? 'Shop Product',
            'product' => $product,
            'characters' => $characters,
            'selectedCharacter' => $selectedCharacter,
            'targetTier' => $targetTier,
            'preview' => $preview,
            'error' => $error,
            'success' => $success,
            'marksBalance' => $balanceOverride ?? $this->marksBalance($accountId),
            'tierCosts' => self::TIER_SKIP_COSTS,
            'maxTierSkip' => self::MAX_TIER_SKIP,
            'tierObjectives' => $this->tierObjectives(),
            'tierTotals' => $selectedCharacter ? $this->tierSkipTotals($selectedCharacter) : [],
            'nextProgressionLabel' => $nextProgressionLabel,
        ]);
    }

    private function fetchActiveProducts(): array
    {
        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdo = Db::pdo($portalDb);
        $stmt = $pdo->query('
            SELECT sku, name, description, category, scope, price_type, price_marks, active
            FROM shop_product
            WHERE active = 1
            ORDER BY category ASC, name ASC
        ');
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    private function fetchProduct(string $sku): ?array
    {
        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdo = Db::pdo($portalDb);
        $stmt = $pdo->prepare('
            SELECT sku, name, description, category, scope, price_type, price_marks, active
            FROM shop_product
            WHERE sku = :sku
            LIMIT 1
        ');
        $stmt->execute([':sku' => $sku]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function accountCharacters(int $accountId): array
    {
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $pdoChars = Db::pdo($charsDb);
        $stmt = $pdoChars->prepare('
            SELECT guid, name, level, class, race, gender
            FROM characters
            WHERE account = :acct
            ORDER BY level DESC, name ASC
        ');
        $stmt->execute([':acct' => $accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function loadCharacter(int $guid, int $accountId): ?array
    {
        $charsDb = Db::env('DB_CHARACTERS', 'acore_characters');
        $pdoChars = Db::pdo($charsDb);
        $stmt = $pdoChars->prepare('
            SELECT guid, account, name, level, class, race, gender
            FROM characters
            WHERE guid = :guid
            LIMIT 1
        ');
        $stmt->execute([':guid' => $guid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row || (int)$row['account'] !== $accountId) {
            return null;
        }

        $row['progression_state'] = $this->progressionState($pdoChars, $guid, (int)$row['level']);
        $row['progression_label'] = $this->progressionLabel((int)$row['progression_state']);

        return $row;
    }

    private function progressionState(PDO $pdoChars, int $guid, int $level): int
    {
        $progressionState = null;
        try {
            $progStmt = $pdoChars->prepare('
                SELECT data
                FROM character_settings
                WHERE guid = :guid
                  AND source = \"mod-individual-progression\"
            ');
            $progStmt->execute([':guid' => $guid]);
            $rows = $progStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $parsedCandidates = [];
            foreach ($rows as $valueRaw) {
                if ($valueRaw === false || $valueRaw === null || $valueRaw === '') {
                    continue;
                }
                $parsed = null;
                if (is_string($valueRaw)) {
                    $trim = trim($valueRaw);
                    $json = json_decode($trim, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (is_int($json)) {
                            $parsed = $json;
                        } elseif (is_array($json)) {
                            if (isset($json['value'])) {
                                $parsed = (int)$json['value'];
                            } elseif (isset($json['state'])) {
                                $parsed = (int)$json['state'];
                            }
                        }
                    }

                    if ($parsed === null) {
                        if (preg_match('/-?\d+/', $trim, $m)) {
                            $parsed = (int)$m[0];
                        }
                    }
                } else {
                    $parsed = (int)$valueRaw;
                }

                if ($parsed !== null) {
                    $parsedCandidates[] = (int)$parsed;
                }
            }

            if ($parsedCandidates) {
                $progressionState = max($parsedCandidates);
            }
        } catch (\Throwable $e) {
            $progressionState = null;
        }

        if ($progressionState === null) {
            $progressionState = 0;
        }

        if ($progressionState === 0 && $level >= 60) {
            $progressionState = 1;
        }

        return (int)$progressionState;
    }

    private function progressionLabel(int $progressionState): string
    {
        $labels = [
            0 => 'Tier 0 – Reach level 60',
            1 => 'Tier 1 – Defeat Ragnaros and Onyxia',
            2 => 'Tier 2 – Defeat Nefarian',
            3 => 'Tier 3 – Complete Might of Kalimdor or Bang a Gong!',
            4 => 'Tier 4 – Complete Chaos and Destruction',
            5 => 'Tier 5 – Defeat C\'thun',
            6 => 'Tier 6 – Defeat Kel\'thuzad',
            7 => 'Tier 7 – Complete Into the Breach',
            8 => 'Tier 8 – Defeat Prince Malchezaar',
            9 => 'Tier 9 – Defeat Kael\'thas',
            10 => 'Tier 10 – Defeat Illidan',
            11 => 'Tier 11 – Defeat Zul\'jin',
            12 => 'Tier 12 – Defeat Kil\'jaeden',
            13 => 'Tier 13 – Defeat Kel\'thuzad (Lvl 80)',
            14 => 'Tier 14 – Defeat Yogg-Saron',
            15 => 'Tier 15 – Defeat Anub\'arak',
            16 => 'Tier 16 – Defeat The Lich King',
            17 => 'Tier 17 – Defeat Halion',
        ];

        return $labels[$progressionState] ?? ('Tier ' . $progressionState);
    }

    private function nextProgressionLabel(string $targetTier): string
    {
        if ($targetTier === '7.5') {
            return $this->progressionLabel(8);
        }
        $base = (int)$targetTier;
        return $this->progressionLabel($base + 1);
    }

    private function tierSkipPreview(array $character, string $targetTier): array
    {
        $currentTier = (int)($character['progression_state'] ?? 0);
        $level = (int)($character['level'] ?? 1);

        $calculated = $this->tierSkipCalculation($currentTier, $level, $targetTier);
        if (isset($calculated['error'])) {
            return $calculated;
        }

        return array_merge($calculated, [
            'current_tier' => $currentTier,
            'target_tier' => $targetTier,
            'level' => $level,
        ]);
    }

    private function marksBalance(int $accountId, ?PDO $pdo = null, bool $forUpdate = false): int
    {
        $pdo = $pdo ?? Db::pdo(Db::env('DB_PORTAL', 'ac_portal'));
        $sql = 'SELECT COALESCE(SUM(delta), 0) FROM marks_ledger WHERE account_id = :id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $accountId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Tier objectives for display in the Tier Completion storefront.
     *
     * @return array<int,array{tier:string,objective:string,selectable:bool,cost?:int}>
     */
    private function tierObjectives(): array
    {
        $tiers = [
            ['tier' => '0', 'objective' => 'Character is set to level 60 and recieve level appropriate gear.', 'selectable' => true],
            ['tier' => '1', 'objective' => 'Ragnaros and Onyxia is marked as defeated.', 'selectable' => true],
            ['tier' => '2', 'objective' => 'Nefarian is marked as defeated.', 'selectable' => true],
            ['tier' => '3', 'objective' => 'Might of Kalimdor or Bang a Gong! is marked as completed.', 'selectable' => true],
            ['tier' => '4', 'objective' => 'Chaos and Destruction is marked as completed.', 'selectable' => true],
            ['tier' => '5', 'objective' => 'C\'thun is marked as defeated.', 'selectable' => true],
            ['tier' => '6', 'objective' => 'Kel\'thuzad is marked as defeated.', 'selectable' => true],
            ['tier' => '7', 'objective' => 'The Dark Portal is opened and TBC is accessable.', 'selectable' => true],
            ['tier' => '7.5', 'objective' => 'Character is set to level 70 and recieve level appropriate gear.', 'selectable' => true],
            ['tier' => '8', 'objective' => 'Prince Malchezaar is marked as defeated.', 'selectable' => true],
            ['tier' => '9', 'objective' => 'Kael\'thas is marked as defeated.', 'selectable' => true],
            ['tier' => '10', 'objective' => 'Illidan is marked as defeated.', 'selectable' => true],
            ['tier' => '11', 'objective' => 'Zul\'jin is marked as defeated.', 'selectable' => true],
            ['tier' => '12', 'objective' => 'Kil\'jaeden is marked as defeated and Wrath of the Lich King is accessible.', 'selectable' => true],
        ];

        foreach ($tiers as &$tier) {
            $key = (string)$tier['tier'];
            if (isset(self::TIER_SKIP_COSTS[$key])) {
                $tier['cost'] = self::TIER_SKIP_COSTS[$key];
            }
        }
        unset($tier);

        return $tiers;
    }

    /**
     * @return array<string,string>
     */
    private function tierObjectiveMap(): array
    {
        $map = [];
        foreach ($this->tierObjectives() as $row) {
            $tierKey = (string)($row['tier'] ?? '');
            if ($tierKey === '') {
                continue;
            }
            $map[$tierKey] = (string)($row['objective'] ?? '');
        }
        return $map;
    }

    /**
     * @return array<string,int|null>
     */
    private function tierSkipTotals(array $character): array
    {
        $totals = [];
        $currentTier = (int)($character['progression_state'] ?? 0);
        $level = (int)($character['level'] ?? 1);

        foreach ($this->tierObjectives() as $row) {
            if (empty($row['selectable'])) {
                continue;
            }
            $tierKey = (string)($row['tier'] ?? '');
            if ($tierKey === '') {
                continue;
            }
            $calc = $this->tierSkipCalculation($currentTier, $level, $tierKey);
            if (isset($calc['error'])) {
                $totals[$tierKey] = null;
                continue;
            }
            $totals[$tierKey] = isset($calc['price']) ? (int)$calc['price'] : null;
        }

        return $totals;
    }

    /**
     * @return array{price?:int,breakdown?:array,bridge_added?:bool,error?:string}
     */
    private function tierSkipCalculation(int $currentTier, int $level, string $targetTier): array
    {
        $ordered = $this->orderedTiers();
        $currentIdx = array_search((string)$currentTier, $ordered, true);
        $targetIdx = array_search($targetTier, $ordered, true);

        if ($currentIdx === false || $targetIdx === false) {
            return ['error' => 'Invalid tier selection.'];
        }
        if ($targetIdx < $currentIdx) {
            return ['error' => 'Desired tier must be at or above the current progression tier.'];
        }

        $idx7 = array_search('7', $ordered, true);
        $idx75 = array_search('7.5', $ordered, true);
        $idx8 = array_search('8', $ordered, true);

        $includeBridge = false;
        if ($targetTier === '7.5') {
            $includeBridge = true;
        } elseif ($idx7 !== false && $idx8 !== false) {
            if ($currentIdx <= $idx7 && $targetIdx >= $idx8 && $level < 70) {
                $includeBridge = true;
            }
        }

        $price = 0;
        $breakdown = [];
        $objectiveMap = $this->tierObjectiveMap();
        for ($i = $currentIdx; $i <= $targetIdx; $i++) {
            $tierKey = $ordered[$i];
            if ($tierKey === '7.5' && !$includeBridge) {
                continue;
            }
            if (!isset(self::TIER_SKIP_COSTS[$tierKey])) {
                continue;
            }
            $cost = self::TIER_SKIP_COSTS[$tierKey];
            $price += $cost;
            $breakdown[] = [
                'tier' => $tierKey,
                'cost' => $cost,
                'objective' => $objectiveMap[$tierKey] ?? null,
            ];
        }

        return [
            'price' => $price,
            'breakdown' => $breakdown,
            'bridge_added' => $includeBridge,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function orderedTiers(): array
    {
        return ['0','1','2','3','4','5','6','7','7.5','8','9','10','11','12'];
    }

    private function normalizeTierInput($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }
        if ($raw === '7.5') {
            return '7.5';
        }
        if (ctype_digit($raw)) {
            $intVal = (int)$raw;
            if ($intVal >= 0 && $intVal <= self::MAX_TIER_SKIP) {
                return (string)$intVal;
            }
        }
        return null;
    }
}
