<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Db;
use App\View;

final class AuthController
{
    public function login(): void
    {
        // Which tab should be active?
        $mode = $_GET['mode'] ?? 'login';
        $mode = $mode === 'register' ? 'register' : 'login';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postMode = $_POST['mode'] ?? 'login';

            if ($postMode === 'register') {
                $this->handleRegister();
                return;
            }

            $this->handleLogin();
            return;
        }

        View::render('login', [
            'title' => $mode === 'register' ? 'Register' : 'Login',
            'mode'  => $mode,
        ]);
    }

    private function handleLogin(): void
    {
        $u = trim($_POST['username'] ?? '');
        $p = (string)($_POST['password'] ?? '');
        $redirect = $_GET['redirect'] ?? '/account';

        if ($u === '' || $p === '') {
            View::render('login', [
                'title' => 'Login',
                'mode'  => 'login',
                'error' => 'Missing username or password.',
            ]);
            return;
        }

        if (Auth::loginWithUsernamePassword($u, $p)) {
            header('Location: ' . $redirect);
            exit;
        }

        View::render('login', [
            'title' => 'Login',
            'mode'  => 'login',
            'error' => 'Invalid credentials.',
        ]);
    }

    private function handleRegister(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $errors = [];

        // Basic validation
        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($confirm === '' || $confirm !== $password) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (!function_exists('gmp_init')) {
            $errors[] = 'Server error: missing GMP extension for SRP6.';
        }

        if ($errors) {
            View::render('login', [
                'title'         => 'Register',
                'mode'          => 'register',
                'registerError' => implode(' ', $errors),
                'old'           => [
                    'username' => $username,
                    'email'    => $email,
                ],
            ]);
            return;
        }

        $authDb = Db::env('DB_AUTH', 'acore_auth');
        $pdo    = Db::pdoWrite($authDb);

        // Ensure username is unique
        $st = $pdo->prepare('SELECT 1 FROM account WHERE username = :u LIMIT 1');
        $st->execute([':u' => $username]);
        if ($st->fetch()) {
            View::render('login', [
                'title'         => 'Register',
                'mode'          => 'register',
                'registerError' => 'That account name is already in use.',
                'old'           => [
                    'username' => $username,
                    'email'    => $email,
                ],
            ]);
            return;
        }

        try {
            // Generate salt + verifier for AzerothCore SRP6
            $saltBin     = random_bytes(32);
            $verifierBin = Auth::srpCreateVerifier($username, $password, $saltBin);
            $ip          = $_SERVER['REMOTE_ADDR'] ?? '';

            $insert = $pdo->prepare('
                INSERT INTO account (username, salt, verifier, email, reg_mail, joindate, last_ip)
                VALUES (:u, :salt, :verifier, :email, :reg_mail, NOW(), :last_ip)
            ');
            $insert->execute([
                ':u'        => $username,
                ':salt'     => $saltBin,
                ':verifier' => $verifierBin,
                ':email'    => $email,
                ':reg_mail' => $email,
                ':last_ip'  => $ip,
            ]);
        } catch (\Throwable $e) {
            error_log('[AC Portal] Registration failed: ' . $e->getMessage());

            View::render('login', [
                'title'         => 'Register',
                'mode'          => 'register',
                'registerError' => 'Registration failed. Please try again later.',
                'old'           => [
                    'username' => $username,
                    'email'    => $email,
                ],
            ]);
            return;
        }

        // Auto-login after successful registration
        if (Auth::loginWithUsernamePassword($username, $password)) {
            header('Location: /account');
            exit;
        }

        // Fallback: show login form with info
        View::render('login', [
            'title' => 'Login',
            'mode'  => 'login',
            'info'  => 'Account created. Please sign in.',
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
        exit;
    }
}
