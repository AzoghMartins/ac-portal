<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;

final class AuthController {
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $u = trim($_POST['username'] ?? '');
            $p = (string)($_POST['password'] ?? '');

            if ($u === '' || $p === '') {
                View::render('login', ['title'=>'Login', 'error'=>'Missing username or password']);
                return;
            }

            $ok = Auth::loginWithUsernamePassword($u, $p);
            if ($ok) {
                header('Location: /');
                exit;
            }
            View::render('login', ['title'=>'Login', 'error'=>'Invalid credentials']);
            return;
        }

        View::render('login', ['title' => 'Login']);
    }

    public function logout(): void {
        Auth::logout();
        header('Location: /');
        exit;
    }
}
