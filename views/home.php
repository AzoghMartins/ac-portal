<h1>AC Portal bootstrap is alive</h1>
<p>Accounts in <code><?= htmlspecialchars($_ENV['DB_AUTH'] ?? 'acore_auth') ?></code>:
   <strong><?= (int)($accounts ?? 0) ?></strong></p>
<p>Try the <a href="/armory">Armory</a> demo (Top 10).</p>

<section class="card">
  <h2>Server Status</h2>
  <table>
    <tbody>
      <tr>
        <th style="text-align:left">Core Revision</th>
        <td><?= $ac_rev ? htmlspecialchars($ac_rev) : '<em>Unknown</em>' ?></td>
      </tr>
      <tr>
        <th style="text-align:left">Last Restart</th>
        <td><?= $last_restart ? htmlspecialchars($last_restart) : '<em>Unknown</em>' ?></td>
      </tr>
      <tr>
        <th style="text-align:left">Uptime</th>
        <td><?= $uptime_human ? htmlspecialchars($uptime_human) : '<em>Unknown</em>' ?></td>
      </tr>
      <tr>
        <th style="text-align:left">Last DB Update</th>
        <td><?= $last_update ? htmlspecialchars($last_update) : '<em>Unknown</em>' ?></td>
      </tr>
    </tbody>
  </table>
</section>

<style>
  .card { background:#ccc; border:1px solid #333; border-radius:8px; padding:16px; margin:16px 0; }
  table { width:100%; border-collapse:collapse; max-width:720px; }
  th, td { padding:8px 10px; border-bottom:1px solid #222; }
  thead th { border-bottom:1px solid #333; }
</style>
