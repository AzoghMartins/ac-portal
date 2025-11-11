<h1>Login</h1>
<?php if (!empty($error)): ?>
  <p style="color:#b00"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="post" action="/login" style="max-width:420px">
  <label>Username<br><input name="username" required autofocus></label><br><br>
  <label>Password<br><input type="password" name="password" required></label><br><br>
  <button type="submit">Sign in</button>
</form>
