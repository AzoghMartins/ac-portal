<?php
$mode = $_GET['mode'] ?? 'login';
$mode = $mode === 'register' ? 'register' : 'login';
?>
<div class="auth-page">
  <div class="auth-card">

    <div class="auth-tabs">
      <a href="/login" class="auth-tab <?= $mode === 'login' ? 'is-active' : '' ?>">Sign In</a>
      <a href="/login?mode=register" class="auth-tab <?= $mode === 'register' ? 'is-active' : '' ?>">Register</a>
    </div>

    <?php if (!empty($error) && $mode === 'login'): ?>
      <div class="auth-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($mode === 'login'): ?>

      <h1 class="auth-title">Sign in to Kardinal WoW</h1>
      <p class="auth-subtitle">
        Use your realm account credentials to access the portal and armory.
      </p>

      <form method="post" action="/login" class="auth-form">
        <div class="auth-field">
          <label class="auth-label" for="username">Username</label>
          <input id="username" name="username" class="auth-input" required autofocus>
        </div>

        <div class="auth-field">
          <label class="auth-label" for="password">Password</label>
          <input id="password" type="password" name="password" class="auth-input" required>
        </div>

        <div class="auth-actions">
          <button type="submit" class="auth-submit">Sign In</button>
        </div>

        <p class="auth-note">
          Accounts are shared between the realm and the portal.<br>
          Change your password using the usual realm tools.
        </p>
      </form>

    <?php else: ?>

      <h1 class="auth-title">Create an Account</h1>
      <p class="auth-subtitle">
        Registration through the portal can be enabled here once the backend is ready.
        For now, this layout defines the look and feel of the page.
      </p>

      <form method="post" action="/register" class="auth-form">
        <div class="auth-field">
          <label class="auth-label" for="reg-username">Username</label>
          <input id="reg-username" name="username" class="auth-input" required>
        </div>

        <div class="auth-field">
          <label class="auth-label" for="reg-email">Email (optional)</label>
          <input id="reg-email" type="email" name="email" class="auth-input">
        </div>

        <div class="auth-field">
          <label class="auth-label" for="reg-password">Password</label>
          <input id="reg-password" type="password" name="password" class="auth-input" required>
        </div>

        <div class="auth-field">
          <label class="auth-label" for="reg-password-confirm">Confirm Password</label>
          <input id="reg-password-confirm" type="password" name="password_confirm" class="auth-input" required>
        </div>

        <div class="auth-actions">
          <button type="submit" class="auth-submit">Register</button>
        </div>

        <p class="auth-note">
          Registration handling is not yet wired to the realm core. This form defines
          the visual design and can be connected to your registration flow later.
        </p>
      </form>

    <?php endif; ?>

  </div>
</div>
