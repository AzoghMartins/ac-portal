<?php
/**
 * Admin settings panel to manage DB + realm endpoints.
 */

$settings = $settings ?? [];
$servers = $settings['servers'] ?? [];
$worlds = $servers['worlds'] ?? [];
$errors = $errors ?? [];
$saved = $saved ?? false;
$realmNames = $realmNames ?? [];

if (empty($worlds)) {
    $worlds = [
        ['name' => $realmNames[1] ?? 'Realm 1', 'host' => '', 'port' => '', 'id' => 1],
    ];
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isSoap = str_starts_with($path, '/admin/soap');
$isMetrics = str_starts_with($path, '/admin/metrics');
$isSettings = str_starts_with($path, '/admin/settings');
?>

<div class="soap-console-page admin-settings-page">
  <header class="soap-console-header">
    <p class="soap-console-kicker">Configuration</p>
    <h1 class="soap-console-title">Server Settings</h1>
    <p class="soap-console-subtitle">
      Tweak database credentials and realm endpoints without touching the server shell.
    </p>
  </header>

  <nav class="admin-subnav">
    <a href="/admin/metrics" class="<?= $isMetrics ? 'is-active' : '' ?>">Metrics</a>
    <a href="/admin/soap" class="<?= $isSoap ? 'is-active' : '' ?>">SOAP Console</a>
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

  <?php if ($saved): ?>
    <div class="soap-console-alert soap-console-alert--success">
      Settings saved. They apply immediately for new requests.
    </div>
  <?php endif; ?>

  <form method="post" class="settings-form" autocomplete="off">
    <section class="soap-console-card">
      <div class="settings-card-header">
        <div>
          <p class="soap-console-kicker">Login Flow</p>
          <h3 class="settings-card-title">Auth Server</h3>
        </div>
        <p class="settings-note">Used for status pings and uptime tracking.</p>
      </div>

      <div class="settings-grid settings-grid--2">
        <label class="settings-field">
          <span>Host</span>
          <input type="text" name="auth_host" class="soap-console-input settings-input" required value="<?= htmlspecialchars($servers['auth']['host'] ?? '') ?>">
        </label>
        <label class="settings-field">
          <span>Port</span>
          <input type="number" name="auth_port" class="soap-console-input settings-input" min="1" step="1" required value="<?= htmlspecialchars((string)($servers['auth']['port'] ?? '')) ?>">
        </label>
      </div>
    </section>

    <section class="soap-console-card">
      <div class="settings-card-header">
        <div>
          <p class="soap-console-kicker">Realms</p>
          <h3 class="settings-card-title">World Servers</h3>
        </div>
        <p class="settings-note">First entry is treated as the primary realm for pings and stats.</p>
      </div>

      <div class="settings-worlds" id="world-list">
        <?php foreach ($worlds as $idx => $world): ?>
          <?php
            $worldId = isset($world['id']) ? (int)$world['id'] : null;
            $displayName = $world['name'] ?? '';
            if ($worldId !== null && isset($realmNames[$worldId]) && $realmNames[$worldId] !== '') {
                $displayName = $realmNames[$worldId];
            }
          ?>
          <div class="settings-world" data-index="<?= $idx + 1 ?>">
            <div class="settings-world-top">
              <p class="settings-world-label">Realm <?= $idx + 1 ?></p>
              <button type="button" class="settings-remove" aria-label="Remove realm" <?= $idx === 0 ? 'disabled' : '' ?>>Remove</button>
            </div>
            <div class="settings-grid settings-grid--2">
              <label class="settings-field">
                <span>Display Name</span>
                <input type="text" name="world_name[]" class="soap-console-input settings-input" placeholder="Realm name auto-filled" value="<?= htmlspecialchars($displayName) ?>">
              </label>
              <label class="settings-field">
                <span>Realm ID</span>
                <input type="number" name="world_id[]" class="soap-console-input settings-input" min="1" step="1" value="<?= htmlspecialchars((string)($world['id'] ?? '')) ?>">
              </label>
            </div>
            <div class="settings-grid settings-grid--2">
              <label class="settings-field">
                <span>Host</span>
                <input type="text" name="world_host[]" class="soap-console-input settings-input" required value="<?= htmlspecialchars($world['host'] ?? '') ?>">
              </label>
              <label class="settings-field">
                <span>Port</span>
                <input type="number" name="world_port[]" class="soap-console-input settings-input" min="1" step="1" required value="<?= htmlspecialchars((string)($world['port'] ?? '')) ?>">
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="settings-actions">
        <button type="button" class="soap-console-chip" id="add-world">+ Add world server</button>
        <p class="settings-note">Supports multiple realms; SOAP and metrics use the first one by default.</p>
      </div>
    </section>

    <section class="settings-submit-row">
      <button type="submit" class="soap-console-submit">Save Settings</button>
      <p class="settings-note">Values are stored in <code>storage/config.json</code>.</p>
    </section>
  </form>
</div>

<template id="world-template">
  <div class="settings-world" data-index="">
    <div class="settings-world-top">
      <p class="settings-world-label">Realm</p>
      <button type="button" class="settings-remove" aria-label="Remove realm">Remove</button>
    </div>
    <div class="settings-grid settings-grid--2">
      <label class="settings-field">
        <span>Display Name</span>
        <input type="text" name="world_name[]" class="soap-console-input settings-input" placeholder="Realm name auto-filled">
      </label>
      <label class="settings-field">
        <span>Realm ID</span>
        <input type="number" name="world_id[]" class="soap-console-input settings-input" min="1" step="1">
      </label>
    </div>
    <div class="settings-grid settings-grid--2">
      <label class="settings-field">
        <span>Host</span>
        <input type="text" name="world_host[]" class="soap-console-input settings-input" required>
      </label>
      <label class="settings-field">
        <span>Port</span>
        <input type="number" name="world_port[]" class="soap-console-input settings-input" min="1" step="1" required>
      </label>
    </div>
  </div>
</template>

<script>
(function () {
  const realmNames = <?= json_encode($realmNames, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}' ?>;
  const list = document.getElementById('world-list');
  const addBtn = document.getElementById('add-world');
  const tpl = document.getElementById('world-template');

  if (!list || !addBtn || !tpl) return;

  function renumber() {
    list.querySelectorAll('.settings-world').forEach(function (row, idx) {
      const label = row.querySelector('.settings-world-label');
      if (label) label.textContent = 'Realm ' + (idx + 1);

      const removeBtn = row.querySelector('.settings-remove');
      if (removeBtn) removeBtn.disabled = idx === 0 && list.children.length === 1;
    });
  }

  addBtn.addEventListener('click', function () {
    const clone = tpl.content.firstElementChild.cloneNode(true);
    list.appendChild(clone);
    wireRealmInputs(clone);
    renumber();
  });

  list.addEventListener('click', function (ev) {
    const target = ev.target;
    if (!(target instanceof HTMLElement)) return;
    if (!target.classList.contains('settings-remove')) return;

    const row = target.closest('.settings-world');
    if (row && list.children.length > 1) {
      row.remove();
      renumber();
    }
  });

  function wireRealmInputs(scope) {
    scope = scope || document;
    scope.querySelectorAll('input[name="world_id[]"]').forEach(function (input) {
      if (input.dataset.realmBound === '1') return;
      input.dataset.realmBound = '1';
      input.addEventListener('change', function () {
        const id = parseInt(this.value, 10);
        const wrapper = this.closest('.settings-world');
        if (!wrapper) return;
        const nameInput = wrapper.querySelector('input[name="world_name[]"]');
        if (!nameInput) return;
        if (id && realmNames[id]) {
          nameInput.value = realmNames[id];
        }
      });
    });
  }

  wireRealmInputs(document);
  renumber();
})();
</script>
