<?php
/**
 * Authentication view handling both sign-in and registration tabs.
 */

// Active tab (login/register)
$mode = $mode ?? ($_GET['mode'] ?? 'login');
$mode = $mode === 'register' ? 'register' : 'login';

// Old form values for re-populating registration fields
$old = $old ?? ['username' => '', 'email' => ''];

// Build action URL (keep ?redirect=... if present)
$redirect = $_GET['redirect'] ?? '';
$actionUrl = '/login';
if ($redirect !== '') {
    $actionUrl .= '?redirect=' . rawurlencode($redirect);
}
?>
<div class="auth-page">
  <div class="auth-card">

    <div class="auth-tabs">
      <a href="/login<?= $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : '' ?>"
         class="auth-tab <?= $mode === 'login' ? 'is-active' : '' ?>">
        Sign In
      </a>
      <a href="/login?mode=register<?= $redirect !== '' ? '&redirect=' . rawurlencode($redirect) : '' ?>"
         class="auth-tab <?= $mode === 'register' ? 'is-active' : '' ?>">
        Register
      </a>
    </div>

    <?php if (!empty($info) && $mode === 'login'): ?>
      <div class="auth-info">
        <?= htmlspecialchars($info) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error) && $mode === 'login'): ?>
      <div class="auth-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($registerError) && $mode === 'register'): ?>
      <div class="auth-error">
        <?= htmlspecialchars($registerError) ?>
      </div>
    <?php endif; ?>

    <?php if ($mode === 'login'): ?>

      <form method="post" action="<?= htmlspecialchars($actionUrl) ?>" class="auth-form">
        <input type="hidden" name="mode" value="login">

        <div class="auth-field">
          <label for="login-username" class="auth-label">Account Name</label>
          <input
            type="text"
            id="login-username"
            name="username"
            class="auth-input"
            autocomplete="username"
            required
          >
        </div>

        <div class="auth-field">
          <label for="login-password" class="auth-label">Password</label>
          <input
            type="password"
            id="login-password"
            name="password"
            class="auth-input"
            autocomplete="current-password"
            required
          >
        </div>

        <div class="auth-actions">
          <button type="submit" class="auth-submit">Sign In</button>
        </div>

        <p class="auth-note">
          Use the same account credentials you use to log in to the WoW realm.
        </p>
      </form>

    <?php else: ?>

      <form method="post" action="<?= htmlspecialchars($actionUrl) ?>" class="auth-form">
        <input type="hidden" name="mode" value="register">

        <div class="auth-field">
          <label for="reg-username" class="auth-label">Account Name</label>
          <input
            type="text"
            id="reg-username"
            name="username"
            class="auth-input"
            autocomplete="username"
            value="<?= htmlspecialchars($old['username'] ?? '') ?>"
            required
          >
        </div>

        <div class="auth-field">
          <label for="reg-email" class="auth-label">Email (optional)</label>
          <input
            type="email"
            id="reg-email"
            name="email"
            class="auth-input"
            autocomplete="email"
            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
          >
        </div>

        <div class="auth-field">
          <label for="reg-password" class="auth-label">Password</label>
          <input
            type="password"
            id="reg-password"
            name="password"
            class="auth-input"
            autocomplete="new-password"
            required
          >
        </div>

        <div class="auth-field">
          <label for="reg-password-confirm" class="auth-label">Confirm Password</label>
          <input
            type="password"
            id="reg-password-confirm"
            name="password_confirm"
            class="auth-input"
            autocomplete="new-password"
            required
          >
        </div>

        <div class="auth-actions">
          <button type="submit" class="auth-submit">Register</button>
        </div>

        <p class="auth-note">
          This will create a new AzerothCore account on the realm. Usernames are
          case-insensitive; passwords are case-sensitive.
        </p>
      </form>

    <?php endif; ?>

  </div>
</div>
