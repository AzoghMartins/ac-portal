<?php
use App\Auth;
$user = Auth::user();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'AC Portal') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,sans-serif;margin:2rem;}
    nav a{margin-right:1rem;text-decoration:none}
    table{border-collapse:collapse;width:100%;max-width:800px}
    th,td{border-bottom:1px solid #ddd;padding:.5rem .75rem;text-align:left}
    th{font-weight:600}
    .muted{color:#666}
  </style>
</head>
<body>
  <nav>
    <a href="/">Home</a>
    <a href="/armory">Armory</a>
    <?php if ($user): ?>
      <a href="/account">My Account</a>
      <span class="muted">| Signed in as <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a style="margin-left:1rem" href="/logout">Logout</a>
    <?php else: ?>
      <a href="/login">Login</a>
    <?php endif; ?>
  </nav>
  <hr>

  <?php require $templateFile; ?>

  <p class="muted" style="margin-top:2rem">
    Env: <code><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'local') ?></code>
    — Debug: <code><?= htmlspecialchars($_ENV['APP_DEBUG'] ?? 'false') ?></code>
  </p>
</body>
</html>
