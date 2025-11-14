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

  <!-- Global layout styles -->
  <link rel="stylesheet" href="/assets/css/layout.css">

  <!-- Page-specific styles -->
  <link rel="stylesheet" href="/assets/css/home.css">
  <link rel="stylesheet" href="/assets/css/auth.css">

  <!-- Fantasy + Body fonts -->
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">

</head>
<body>

  <header class="site-header">
    <div class="site-header-inner">
      <div class="nav-logo">
        <a href="/"><span class="logo">Kardinal</span> WoW</a>
      </div>

      <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
        <span class="nav-toggle-bar"></span>
      </button>

      <nav id="main-nav" class="main-nav">
    <div class="nav-left-spacer"></div>

    <ul class="nav-center">
        <li><a href="/">Home</a></li>
        <li><a href="/armory">Armory</a></li>
        <li><a href="/features">Features</a></li>
    </ul>

    <div class="nav-right-spacer"></div>

    <div class="nav-auth">
        <?php if ($user): ?>
            <span class="nav-username">
                <?= htmlspecialchars($user['username'] ?? '') ?>
            </span>
            <a href="/account" class="nav-btn nav-btn--primary">Account</a>
            <a href="/logout" class="nav-btn nav-btn--secondary">Logout</a>
        <?php else: ?>
            <a href="/login" class="nav-btn nav-btn--primary">Login</a>
            <a href="/login?mode=register" class="nav-btn nav-btn--secondary">Register</a>
        <?php endif; ?>
    </div>
</nav>
    </div>
  </header>

  <?php require $templateFile; ?>

    <footer class="site-footer">
    <div class="site-footer-inner">
      <p class="footer-main">
        © <?= date('Y') ?> Kardinal WoW. Not affiliated with or endorsed by Blizzard Entertainment.
      </p>
      <p class="footer-meta">
        Env:
        <code><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'local') ?></code>
        — Debug:
        <code><?= htmlspecialchars($_ENV['APP_DEBUG'] ?? 'false') ?></code>
        — Powered by <span class="footer-highlight">AzerothCore</span>.
      </p>
    </div>
  </footer>


  <script>
    (function () {
      var toggle = document.querySelector('.nav-toggle');
      var nav = document.getElementById('main-nav');
      if (!toggle || !nav) return;

      toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('main-nav--open');
        toggle.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    })();
  </script>
</body>
</html>
