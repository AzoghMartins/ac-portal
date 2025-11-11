<h1>AC Portal bootstrap is alive</h1>
<p>Accounts in <code><?= htmlspecialchars($_ENV['DB_AUTH'] ?? 'acore_auth') ?></code>:
   <strong><?= (int)($accounts ?? 0) ?></strong></p>
<p>Try the <a href="/armory">Armory</a> demo (Top 10).</p>
