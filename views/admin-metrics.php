<?php
/**
 * Admin metrics dashboard (GM level 3+).
 *
 * @var array $metrics
 */

$m = $metrics ?? [];

function format_bytes(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    $v = $bytes;
    while ($v >= 1024 && $i < count($units)-1) {
        $v /= 1024;
        $i++;
    }
    return sprintf('%.1f %s', $v, $units[$i]);
}
?>

<div class="soap-console-page">
  <header class="soap-console-header">
    <p class="soap-console-kicker">Server Health</p>
    <h1 class="soap-console-title">Metrics</h1>
    <p class="soap-console-subtitle">
      Quick host and realm status snapshot.
    </p>
  </header>

  <?php
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $isSoap = str_starts_with($path, '/admin/soap');
    $isMetrics = str_starts_with($path, '/admin/metrics');
    $isSettings = str_starts_with($path, '/admin/settings');
  ?>
  <nav class="admin-subnav">
    <a href="/admin/metrics" class="<?= $isMetrics ? 'is-active' : '' ?>">Metrics</a>
    <a href="/admin/soap" class="<?= $isSoap ? 'is-active' : '' ?>">SOAP Console</a>
    <a href="/admin/settings" class="<?= $isSettings ? 'is-active' : '' ?>">Settings</a>
  </nav>

  <section class="soap-console-card">
    <h3 class="section-title account-section-title">Overview</h3>
    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <tbody>
          <tr>
            <th>Timestamp</th>
            <td><?= htmlspecialchars($m['timestamp'] ?? '') ?></td>
          </tr>
          <tr>
            <th>OS Uptime</th>
            <td><?= htmlspecialchars($m['uptime']['display'] ?? '—') ?></td>
          </tr>
          <tr>
            <th>Load Avg</th>
            <td>
              <?php if (!empty($m['load'])): ?>
                1m <?= number_format((float)$m['load']['l1'], 2) ?> |
                5m <?= number_format((float)$m['load']['l5'], 2) ?> |
                15m <?= number_format((float)$m['load']['l15'], 2) ?>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>Memory</th>
            <td>
              <?php if (!empty($m['memory'])): ?>
                <?= format_bytes((int)$m['memory']['used']) ?> used /
                <?= format_bytes((int)$m['memory']['total']) ?> total
                (<?= number_format((float)$m['memory']['used_pct'], 1) ?>%)
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="soap-console-card">
    <h3 class="section-title account-section-title">Disk</h3>
    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <thead>
          <tr>
            <th>Mount</th>
            <th>Usage</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($m['disk'])): ?>
            <?php foreach ($m['disk'] as $mount => $d): ?>
              <tr>
                <td><?= htmlspecialchars($mount) ?></td>
                <td>
                  <?= format_bytes((int)$d['used']) ?> used /
                  <?= format_bytes((int)$d['total']) ?> total
                  (<?= number_format((float)$d['used_pct'], 1) ?>%)
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2">No disk data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="soap-console-card">
    <h3 class="section-title account-section-title">Services</h3>
    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <thead>
          <tr>
            <th>Service</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($m['services'])): ?>
            <?php foreach ($m['services'] as $name => $status): ?>
              <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= htmlspecialchars($status) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2">No service data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="soap-console-card">
    <h3 class="section-title account-section-title">Modules</h3>
    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <thead>
          <tr>
            <th>Module</th>
            <th>Config Preview</th>
            <th>Entries</th>
          </tr>
        </thead>
        <tbody>
          <?php $modules = $m['modules'] ?? []; ?>
          <?php if (empty($modules)): ?>
            <tr><td colspan="3">No module data found.</td></tr>
          <?php else: ?>
            <?php foreach ($modules as $module): ?>
              <tr>
                <td><?= htmlspecialchars($module['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($module['preview_text'] ?? '') ?></td>
                <td><?= isset($module['config_count']) ? (int)$module['config_count'] : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="soap-console-card">
    <h3 class="section-title account-section-title">Realm Status</h3>
    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <tbody>
          <tr>
            <th>Online Players</th>
            <td><?= isset($m['realm']['online_players']) ? (int)$m['realm']['online_players'] : '—' ?></td>
          </tr>
          <tr>
            <th>Online Bots</th>
            <td><?= isset($m['realm']['online_bots']) ? (int)$m['realm']['online_bots'] : '—' ?></td>
          </tr>
          <tr>
            <th>Session Peak</th>
            <td><?= isset($m['realm']['peak']) ? (int)$m['realm']['peak'] : '—' ?></td>
          </tr>
          <tr>
            <th>Realm Uptime</th>
            <td><?= htmlspecialchars($m['realm']['uptime'] ?? '—') ?></td>
          </tr>
          <tr>
            <th>Uptime Started</th>
            <td><?= htmlspecialchars($m['realm']['started_at'] ?? '—') ?></td>
          </tr>
          <tr>
            <th>World Ping</th>
            <td>
              <?php if (!empty($m['realm']['ping']['world']['ok'])): ?>
                <?= number_format((float)$m['realm']['ping']['world']['ms'], 1) ?> ms
              <?php else: ?>
                Unreachable
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>Auth Ping</th>
            <td>
              <?php if (!empty($m['realm']['ping']['auth']['ok'])): ?>
                <?= number_format((float)$m['realm']['ping']['auth']['ms'], 1) ?> ms
              <?php else: ?>
                Unreachable
              <?php endif; ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

</div>
