<?php
/**
 * Admin Marks grant page.
 *
 * @var array $errors
 * @var bool $saved
 * @var array $form
 * @var array|null $grantResult
 * @var array $accountOptions
 * @var array $recentGrants
 */

$errors = $errors ?? [];
$saved = $saved ?? false;
$form = $form ?? ['exclude_prefix' => '', 'username' => '', 'amount' => '1000', 'reason' => ''];
$grantResult = $grantResult ?? null;
$accountOptions = $accountOptions ?? [];
$recentGrants = $recentGrants ?? [];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isSoap = str_starts_with($path, '/admin/soap');
$isMetrics = str_starts_with($path, '/admin/metrics');
$isSettings = str_starts_with($path, '/admin/settings');
$isMarks = str_starts_with($path, '/admin/marks');
?>

<div class="soap-console-page admin-settings-page">
  <header class="soap-console-header">
    <p class="soap-console-kicker">Economy Tools</p>
    <h1 class="soap-console-title">Grant Marks</h1>
    <p class="soap-console-subtitle">
      Add Marks to another account through the portal ledger.
    </p>
  </header>

  <nav class="admin-subnav">
    <a href="/admin/metrics" class="<?= $isMetrics ? 'is-active' : '' ?>">Metrics</a>
    <a href="/admin/soap" class="<?= $isSoap ? 'is-active' : '' ?>">SOAP Console</a>
    <a href="/admin/marks" class="<?= $isMarks ? 'is-active' : '' ?>">Marks</a>
    <a href="/admin/settings" class="<?= $isSettings ? 'is-active' : '' ?>">Settings</a>
  </nav>

  <?php if (!empty($errors)): ?>
    <div class="soap-console-alert soap-console-alert--error">
      <ul class="settings-error-list">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($saved && $grantResult): ?>
    <div class="soap-console-alert soap-console-alert--success">
      Granted <?= number_format((int)($grantResult['amount'] ?? 0)) ?> Marks to <?= htmlspecialchars((string)($grantResult['username'] ?? '')) ?>.
      New balance: <?= number_format((int)($grantResult['new_balance'] ?? 0)) ?>.
    </div>
  <?php endif; ?>

  <section class="soap-console-card">
    <form method="post" class="settings-form" autocomplete="off">
      <div class="settings-card-header">
        <div>
          <p class="soap-console-kicker">Ledger Entry</p>
          <h3 class="settings-card-title">Grant to Account</h3>
        </div>
        <p class="settings-note">Looks up the target by exact account username and inserts a positive Marks ledger entry.</p>
      </div>

      <div class="settings-grid settings-grid--2">
        <label class="settings-field">
          <span>Exclude Prefix</span>
          <input
            type="text"
            id="marks-exclude-prefix"
            name="exclude_prefix"
            class="soap-console-input settings-input"
            placeholder="Example: RNDBOT"
            value="<?= htmlspecialchars((string)($form['exclude_prefix'] ?? '')) ?>"
          >
        </label>
        <label class="settings-field">
          <span>Amount</span>
          <input type="number" name="amount" class="soap-console-input settings-input" min="1" step="1" required value="<?= htmlspecialchars((string)($form['amount'] ?? '1000')) ?>">
        </label>
      </div>

      <label class="settings-field">
        <span>Account Username</span>
        <select id="marks-username-select" name="username" class="soap-console-input settings-input" required>
          <option value="">Select an account</option>
          <?php foreach ($accountOptions as $username): ?>
            <?php $selected = (string)($form['username'] ?? '') === (string)$username; ?>
            <option value="<?= htmlspecialchars((string)$username) ?>" <?= $selected ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)$username) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="settings-note" id="marks-account-count"><?= count($accountOptions) ?> matching accounts</span>
      </label>

      <label class="settings-field">
        <span>Reason</span>
        <input type="text" name="reason" class="soap-console-input settings-input" maxlength="80" placeholder="Optional note for the ledger reason" value="<?= htmlspecialchars((string)($form['reason'] ?? '')) ?>">
      </label>

      <section class="settings-submit-row">
        <button type="submit" class="soap-console-submit">Grant Marks</button>
        <p class="settings-note">Stored as an `admin:grant:*` reason in `marks_ledger`.</p>
      </section>
    </form>
  </section>

  <section class="soap-console-card">
    <div class="settings-card-header">
      <div>
        <p class="soap-console-kicker">Audit</p>
        <h3 class="settings-card-title">Recent Grants</h3>
      </div>
      <p class="settings-note">Latest portal-issued admin grants.</p>
    </div>

    <div class="soap-console-log-table-wrap">
      <table class="soap-console-log-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Account</th>
            <th>Delta</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentGrants)): ?>
            <tr><td colspan="4">No recent admin grants found.</td></tr>
          <?php else: ?>
            <?php foreach ($recentGrants as $row): ?>
              <tr>
                <td><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($row['username'] ?? '')) ?></td>
                <td><?= number_format((int)($row['delta'] ?? 0)) ?></td>
                <td><code><?= htmlspecialchars((string)($row['reason'] ?? '')) ?></code></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script>
(function () {
  var prefixInput = document.getElementById('marks-exclude-prefix');
  var select = document.getElementById('marks-username-select');
  var count = document.getElementById('marks-account-count');
  if (!prefixInput || !select) return;

  var allOptions = Array.prototype.slice.call(select.querySelectorAll('option')).map(function (option) {
    return {
      value: option.value,
      text: option.textContent || ''
    };
  });

  function renderOptions() {
    var prefix = (prefixInput.value || '').trim().toUpperCase();
    var selectedValue = select.value;
    var matches = 0;

    select.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select an account';
    select.appendChild(placeholder);

    allOptions.forEach(function (option) {
      if (!option.value) return;

      if (prefix !== '' && option.value.toUpperCase().indexOf(prefix) === 0) {
        return;
      }

      var node = document.createElement('option');
      node.value = option.value;
      node.textContent = option.text;
      if (option.value === selectedValue) {
        node.selected = true;
      }
      select.appendChild(node);
      matches++;
    });

    if (select.value === '' && selectedValue !== '') {
      // previous selection was filtered out
      placeholder.selected = true;
    }

    if (count) {
      count.textContent = matches + ' matching accounts';
    }
  }

  prefixInput.addEventListener('input', renderOptions);
  renderOptions();
})();
</script>
