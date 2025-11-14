<?php
/** @var array  $user */
/** @var string $role */
/** @var array  $characters */
/** @var int|null $gmLevel */

use App\WowHelper;
?>

<div class="account-page">
  <header class="account-header">
    <h1 class="account-title">My Account</h1>
    <p class="account-subtitle">
      Overview of your realm access and the characters bound to this shard of Azeroth.
    </p>
  </header>

  <section class="account-section account-summary">
    <div class="account-summary-card">
      <h2 class="section-title account-section-title">Account Overview</h2>

      <dl class="account-summary-list">
        <div class="account-summary-row">
          <dt>Username</dt>
          <dd><?= htmlspecialchars($user['username'] ?? '') ?></dd>
        </div>
        <div class="account-summary-row">
          <dt>Role</dt>
          <dd><?= htmlspecialchars($role) ?></dd>
        </div>
        <?php if (isset($gmLevel) && $gmLevel !== null): ?>
          <div class="account-summary-row">
            <dt>GM Level</dt>
            <dd><?= (int)$gmLevel ?></dd>
          </div>
        <?php endif; ?>
        <div class="account-summary-row">
          <dt>Account ID</dt>
          <dd><?= isset($user['id']) ? (int)$user['id'] : 0 ?></dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="account-section account-characters">
    <h2 class="section-title account-section-title">Your Characters</h2>

    <?php if (empty($characters)): ?>
      <p class="account-empty">
        No characters are currently associated with this account.
      </p>
    <?php else: ?>
      <div class="account-characters-table-wrap">
        <table class="account-characters-table clickable-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Level</th>
              <th>Race</th>
              <th>Class</th>
              <th>Online</th>
              <th>Played</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($characters as $char): ?>
            <?php
              $guid    = (int)$char['guid'];
              $name    = $char['name'] ?? '';
              $level   = (int)$char['level'];
              $raceId  = (int)$char['race'];
              $classId = (int)$char['class'];
              $online  = !empty($char['online']);
              $total   = isset($char['totaltime']) ? (int)$char['totaltime'] : 0;

              $hours   = intdiv($total, 3600);
              $minutes = intdiv($total % 3600, 60);

              $raceName  = WowHelper::raceName($raceId);
              $className = WowHelper::className($classId);
            ?>
            <tr class="clickable-row" data-href="/character?guid=<?= $guid ?>">
              <td class="char-name">
                <?= htmlspecialchars($name) ?>
              </td>
              <td><?= $level ?></td>
              <td><?= htmlspecialchars($raceName) ?></td>
              <td><?= htmlspecialchars($className) ?></td>
              <td>
                <?php if ($online): ?>
                  <span class="status-dot status-dot--online"></span> Online
                <?php else: ?>
                  <span class="status-dot status-dot--offline"></span> Offline
                <?php endif; ?>
              </td>
              <td>
                <?php if ($total > 0): ?>
                  <?= $hours ?>h <?= $minutes ?>m
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
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
  document.querySelectorAll('table.clickable-table tr.clickable-row').forEach(function (row) {
    row.addEventListener('click', function (e) {
      // Let normal <a> clicks behave as usual
      if (e.target.closest('a')) {
        return;
      }
      const href = this.getAttribute('data-href');
      if (href) {
        window.location.href = href;
      }
    });
  });
});
</script>
