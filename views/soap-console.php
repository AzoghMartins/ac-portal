<?php
/**
 * Admin SOAP console view for GM level 3+ staff.
 *
 * @var string|null $error
 * @var string|null $result
 * @var string      $command
 * @var array       $quickCommands
 * @var array       $activity
 * @var bool        $justLogged
 */

$command = $command ?? '';
$quickCommands = $quickCommands ?? [];
$activity = $activity ?? [];
$justLogged = $justLogged ?? false;
?>

<div class="soap-console-page">
  <header class="soap-console-header">
    <p class="soap-console-kicker">Worldserver Tools</p>
    <h1 class="soap-console-title">SOAP Console</h1>
    <p class="soap-console-subtitle">
      Issue commands to the realm from the portal. Limited to GM level 3+ accounts.
    </p>
  </header>

  <?php if (!empty($error)): ?>
    <div class="soap-console-alert soap-console-alert--error">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($result !== null && $error === null): ?>
    <div class="soap-console-alert soap-console-alert--success">
      Command sent at <?= htmlspecialchars(date('H:i:s')) ?>.
    </div>
  <?php endif; ?>

  <section class="soap-console-card">
    <form method="post" class="soap-console-form">
      <label for="soap-command-input" class="soap-console-label">Command</label>
      <div class="soap-console-input-row">
        <input
          type="text"
          id="soap-command-input"
          name="command"
          class="soap-console-input"
          placeholder="e.g. reload config"
          value="<?= htmlspecialchars($command) ?>"
          autofocus
          required
        >
        <button type="submit" class="soap-console-submit">Send</button>
      </div>
      <p class="soap-console-hint">
        Examples: <code>reload config</code>, <code>server info</code>, <code>account onlinelist</code>.
      </p>
    </form>

    <?php if (!empty($quickCommands)): ?>
      <div class="soap-console-quick">
        <p class="soap-console-label">Quick commands</p>
        <div class="soap-console-chip-row">
          <?php foreach ($quickCommands as $qc): ?>
            <button type="button"
                    class="soap-console-chip"
                    data-command="<?= htmlspecialchars($qc) ?>">
              <?= htmlspecialchars($qc) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <section class="soap-console-output">
    <div class="soap-console-output-header">
      <p class="soap-console-label">Latest response</p>
    </div>

    <?php if ($result === null): ?>
      <p class="soap-console-empty">Run a command to see output here.</p>
    <?php elseif ($result === ''): ?>
      <pre class="soap-console-pre muted">(No content returned.)</pre>
    <?php else: ?>
      <pre class="soap-console-pre"><?= htmlspecialchars($result) ?></pre>
    <?php endif; ?>
  </section>

  <section class="soap-console-log">
    <div class="soap-console-output-header">
      <p class="soap-console-label">Recent activity</p>
      <?php if ($justLogged): ?>
        <span class="soap-console-chip soap-console-chip--small">Updated</span>
      <?php endif; ?>
    </div>

    <?php if (empty($activity)): ?>
      <p class="soap-console-empty">No SOAP commands have been issued yet.</p>
    <?php else: ?>
      <div class="soap-console-log-table-wrap">
        <table class="soap-console-log-table">
          <thead>
            <tr>
              <th>When</th>
              <th>User</th>
              <th>GM</th>
              <th>Command</th>
              <th>Result</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activity as $entry): ?>
              <?php
                $ts = isset($entry['ts']) ? (int)$entry['ts'] : time();
                $tsStr = date('Y-m-d H:i:s', $ts);
                $gm = isset($entry['gmlevel']) ? (int)$entry['gmlevel'] : 0;
                $cmd = $entry['command'] ?? '';
                $usr = $entry['username'] ?? 'unknown';
                $err = $entry['error'] ?? null;
                $res = $entry['result'] ?? '';
                $resPreview = $err ? ('Error: ' . $err) : ($res === '' ? '(no output)' : $res);
                $resPreview = mb_substr($resPreview, 0, 120);
              ?>
              <tr>
                <td><?= htmlspecialchars($tsStr) ?></td>
                <td><?= htmlspecialchars($usr) ?></td>
                <td><?= $gm ?></td>
                <td><code><?= htmlspecialchars($cmd) ?></code></td>
                <td class="<?= $err ? 'soap-log-error' : '' ?>"><?= htmlspecialchars($resPreview) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('soap-command-input');
  document.querySelectorAll('.soap-console-chip').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!input) return;
      input.value = this.getAttribute('data-command') || '';
      input.focus();
    });
  });
});
</script>
