<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;
use PDO;

final class AdminMarksController
{
    public function __invoke(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            header('Location: /login?redirect=/admin/marks');
            exit;
        }
        if (($user['gmlevel'] ?? 0) < 3) {
            header('Location: /');
            exit;
        }

        $form = [
            'exclude_prefix' => '',
            'username' => '',
            'amount' => '1000',
            'reason' => '',
        ];
        $errors = [];
        $saved = false;
        $grantResult = null;

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $form['exclude_prefix'] = trim((string)($_POST['exclude_prefix'] ?? ''));
            $form['username'] = trim((string)($_POST['username'] ?? ''));
            $form['amount'] = trim((string)($_POST['amount'] ?? ''));
            $form['reason'] = trim((string)($_POST['reason'] ?? ''));

            $username = $form['username'];
            $amount = ctype_digit($form['amount']) ? (int)$form['amount'] : 0;
            $reasonText = $form['reason'];

            if ($username === '') {
                $errors[] = 'Account username is required.';
            }
            if ($amount <= 0) {
                $errors[] = 'Grant amount must be a positive whole number.';
            }
            if (strlen($reasonText) > 80) {
                $errors[] = 'Reason must be 80 characters or fewer.';
            }

            if (empty($errors)) {
                $grantResult = $this->grantMarks($username, $amount, $reasonText, (string)($user['username'] ?? 'admin'));
                if (!empty($grantResult['error'])) {
                    $errors[] = (string)$grantResult['error'];
                    $grantResult = null;
                } else {
                    $saved = true;
                    $form = [
                        'exclude_prefix' => trim((string)($_POST['exclude_prefix'] ?? '')),
                        'username' => '',
                        'amount' => '1000',
                        'reason' => '',
                    ];
                }
            }
        }

        View::render('admin-marks', [
            'title' => 'Grant Marks',
            'errors' => $errors,
            'saved' => $saved,
            'form' => $form,
            'grantResult' => $grantResult,
            'accountOptions' => $this->accountOptions(),
            'recentGrants' => $this->recentGrants(),
        ]);
    }

    /**
     * @return array{username?:string,account_id?:int,amount?:int,old_balance?:int,new_balance?:int,error?:string}
     */
    private function grantMarks(string $username, int $amount, string $reasonText, string $adminUsername): array
    {
        $authDb = Db::env('DB_AUTH', 'acore_auth');
        $portalDb = Db::env('DB_PORTAL', 'ac_portal');
        $pdoAuth = Db::pdo($authDb);
        $pdoPortal = Db::pdoWrite($portalDb);

        $st = $pdoAuth->prepare('SELECT id, username FROM account WHERE username = :username LIMIT 1');
        $st->execute([':username' => $username]);
        $account = $st->fetch(PDO::FETCH_ASSOC);
        if (!$account) {
            return ['error' => 'Account not found for that username.'];
        }

        $accountId = (int)($account['id'] ?? 0);
        if ($accountId <= 0) {
            return ['error' => 'Resolved account ID is invalid.'];
        }

        $reason = $this->buildReason($adminUsername, $reasonText);

        try {
            $pdoPortal->beginTransaction();

            $balanceStmt = $pdoPortal->prepare('SELECT COALESCE(SUM(delta), 0) AS balance FROM marks_ledger WHERE account_id = :id FOR UPDATE');
            $balanceStmt->execute([':id' => $accountId]);
            $oldBalance = (int)($balanceStmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0);

            $ins = $pdoPortal->prepare('
                INSERT INTO marks_ledger (account_id, delta, reason)
                VALUES (:account_id, :delta, :reason)
            ');
            $ins->execute([
                ':account_id' => $accountId,
                ':delta' => $amount,
                ':reason' => $reason,
            ]);

            $newBalance = $oldBalance + $amount;
            $pdoPortal->commit();

            return [
                'username' => (string)($account['username'] ?? $username),
                'account_id' => $accountId,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
            ];
        } catch (\Throwable $e) {
            if ($pdoPortal->inTransaction()) {
                $pdoPortal->rollBack();
            }
            return ['error' => 'Grant failed: ' . $e->getMessage()];
        }
    }

    private function buildReason(string $adminUsername, string $reasonText): string
    {
        $parts = ['admin', 'grant', trim($adminUsername)];
        $reasonText = trim($reasonText);
        if ($reasonText !== '') {
            $parts[] = preg_replace('/\s+/', '-', $reasonText) ?? $reasonText;
        }

        $reason = implode(':', array_filter($parts, static fn(string $part): bool => $part !== ''));
        if (strlen($reason) > 120) {
            $reason = substr($reason, 0, 120);
        }

        return $reason;
    }

    /**
     * @return string[]
     */
    private function accountOptions(): array
    {
        try {
            $authDb = Db::env('DB_AUTH', 'acore_auth');
            $pdoAuth = Db::pdo($authDb);
            $rows = $pdoAuth->query('SELECT username FROM account ORDER BY username ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $usernames = [];
            foreach ($rows as $row) {
                $username = trim((string)($row['username'] ?? ''));
                if ($username === '') {
                    continue;
                }
                $usernames[] = $username;
            }

            return $usernames;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function recentGrants(): array
    {
        try {
            $authDb = Db::env('DB_AUTH', 'acore_auth');
            $portalDb = Db::env('DB_PORTAL', 'ac_portal');
            $pdoAuth = Db::pdo($authDb);
            $pdoPortal = Db::pdo($portalDb);

            $rows = $pdoPortal->query("
                SELECT id, account_id, delta, reason, created_at
                FROM marks_ledger
                WHERE reason LIKE 'admin:grant:%'
                ORDER BY created_at DESC, id DESC
                LIMIT 25
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!$rows) {
                return [];
            }

            $usernames = [];
            $ids = array_values(array_unique(array_map(static fn(array $row): int => (int)($row['account_id'] ?? 0), $rows)));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $st = $pdoAuth->prepare("SELECT id, username FROM account WHERE id IN ($placeholders)");
                $st->execute($ids);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $usernames[(int)$row['id']] = (string)($row['username'] ?? '');
                }
            }

            foreach ($rows as &$row) {
                $accountId = (int)($row['account_id'] ?? 0);
                $row['username'] = $usernames[$accountId] ?? ('#' . $accountId);
            }
            unset($row);

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
