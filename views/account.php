<?php /** @var array $user */ ?>
<?php /** @var string $role */ ?>
<?php /** @var array $characters */ ?>

<?php
use App\WowHelper;
?>

<h1>My Account</h1>

<section class="card">
  <h2>Account</h2>
  <p><strong>Username:</strong> <?= htmlspecialchars($user['username'] ?? '') ?></p>
  <p><strong>Role:</strong> <?= htmlspecialchars($role) ?></p>
</section>

<section class="card">
  <h2>My Characters</h2>
  <?php if (empty($characters)): ?>
    <p>No characters found on this account yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="clickable-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Level</th>
            <th>Class</th>
            <th>Race</th>
            <th>Total Time (h)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($characters as $c): ?>
          <?php
            $guid    = (int)$c['guid'];
            $classId = (int)$c['class'];
            $raceId  = (int)$c['race'];

            $gender = null;
            if (array_key_exists('gender', $c)) {
                $gender = $c['gender'] !== null ? (int)$c['gender'] : null;
            }

            $className = WowHelper::className($classId);
            $raceName  = WowHelper::raceName($raceId);

            $classIcon = WowHelper::classIcon($classId);
            $raceIcon  = WowHelper::raceIcon($raceId, $gender);

            $charUrl = '/character?guid=' . $guid;
          ?>
          <tr class="clickable-row" data-href="<?= htmlspecialchars($charUrl) ?>">
            <td>
              <a href="<?= htmlspecialchars($charUrl) ?>" style="color:inherit;text-decoration:none">
                <?= htmlspecialchars($c['name']) ?>
              </a>
            </td>
            <td><?= (int)$c['level'] ?></td>
            <td>
              <img src="<?= htmlspecialchars($classIcon) ?>"
                   alt="<?= htmlspecialchars($className) ?>"
                   width="18" height="18"
                   style="vertical-align:-3px;margin-right:6px">
              <?= htmlspecialchars($className) ?>
            </td>
            <td>
              <img src="<?= htmlspecialchars($raceIcon) ?>"
                   alt="<?= htmlspecialchars($raceName) ?>"
                   width="18" height="18"
                   style="vertical-align:-3px;margin-right:6px">
              <?= htmlspecialchars($raceName) ?>
            </td>
            <td><?= number_format(((int)$c['totaltime']) / 3600, 1) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<style>
  .card { background:#eee; border:1px solid #333; border-radius:8px; padding:16px; margin:16px 0; }
  .table-wrap { overflow:auto; }
  table { width:100%; border-collapse:collapse; }
  th, td { padding:8px 10px; border-bottom:1px solid #222; text-align:left; }
  thead th { border-bottom:1px solid #333; }

  .clickable-table tr.clickable-row { cursor:pointer; }
  .clickable-table tr.clickable-row:hover { background-color:#ddd; }
</style>

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
