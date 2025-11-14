<?php
use App\WowHelper;

/** @var array $rows Top 10 rows: each has guid, name, level, class, race, avg_ilvl */
?>

<div class="armory-page">
  <header class="armory-header">
    <h1 class="armory-title">Armory</h1>
    <p class="armory-subtitle">
      Search for characters on this shard and inspect their public equipment.
    </p>
  </header>

  <div class="armory-layout">
    <!-- SEARCH PANEL -->
    <section class="card armory-card armory-search-card">
      <h2 class="section-title armory-section-title">Search Characters</h2>
      <p class="armory-help-text">
        Type at least <strong>3 letters</strong> to begin searching by character name.
      </p>

      <div class="armory-search-bar">
        <input
          type="text"
          id="armory-search-input"
          class="armory-search-input"
          placeholder="Enter character name…"
          autocomplete="off"
        >
      </div>

      <div id="armory-search-status" class="armory-search-status"></div>

      <div id="armory-search-results-wrap" class="armory-search-results-wrap" hidden>
        <table class="armory-table armory-search-results-table clickable-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Level</th>
              <th>Class</th>
              <th>Race</th>
              <th>Avg iLvl</th>
            </tr>
          </thead>
          <tbody id="armory-search-results-body">
          <!-- Filled dynamically via JS -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- TOP 10 PANEL -->
    <section class="card armory-card armory-top-card">
      <h2 class="section-title armory-section-title">Top 10 by Item Level</h2>
      <p class="armory-help-text">
        Highest average equipped item level on the shard.
      </p>

      <?php if (empty($rows)): ?>
        <p class="armory-empty">
          No characters found for the top list yet.
        </p>
      <?php else: ?>
        <div class="armory-top-table-wrap">
          <table class="armory-table armory-top-table clickable-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Level</th>
                <th>Class</th>
                <th>Avg iLvl</th>
              </tr>
            </thead>
            <tbody>
            <?php
              $rank = 1;
              foreach ($rows as $r):
                  $classId   = (int)$r['class'];
                  $raceId    = (int)$r['race'];
                  $className = WowHelper::className($classId);
                  $classIcon = WowHelper::classIcon($classId);

                  $avgIlvl   = isset($r['avg_ilvl']) ? (float)$r['avg_ilvl'] : null;
                  $href      = '/character?guid=' . (int)$r['guid'];
            ?>
              <tr class="clickable-row" data-href="<?= htmlspecialchars($href) ?>">
                <td><?= $rank++ ?></td>
                <td>
                  <a href="<?= htmlspecialchars($href) ?>" class="armory-link-strong">
                    <?= htmlspecialchars($r['name']) ?>
                  </a>
                </td>
                <td><?= (int)$r['level'] ?></td>
                <td>
                  <img
                    src="<?= htmlspecialchars($classIcon) ?>"
                    alt="<?= htmlspecialchars($className) ?>"
                    width="18"
                    height="18"
                    class="armory-inline-icon"
                  >
                  <?= htmlspecialchars($className) ?>
                </td>
                <td>
                  <?php if ($avgIlvl !== null): ?>
                    <?= number_format($avgIlvl, 1) ?>
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
</div>

<script>
(function() {
  const input   = document.getElementById('armory-search-input');
  const status  = document.getElementById('armory-search-status');
  const wrap    = document.getElementById('armory-search-results-wrap');
  const tbody   = document.getElementById('armory-search-results-body');

  // Helper: make table rows clickable, same pattern as on the account page
  function bindRowClicks(root) {
    const ctx = root || document;
    ctx.querySelectorAll('table.clickable-table tr.clickable-row').forEach(function (row) {
      // Avoid attaching multiple listeners if we rerender search results
      if (row.dataset.clickBound === '1') {
        return;
      }
      row.dataset.clickBound = '1';

      row.addEventListener('click', function (e) {
        // Let normal <a> clicks behave as usual
        if (e.target.closest('a')) {
          return;
        }
        const href = row.getAttribute('data-href');
        if (href) {
          window.location.href = href;
        }
      });
    });
  }

  // Initial binding for Top 10 table (rendered server-side)
  bindRowClicks(document);

  if (!input || !status || !wrap || !tbody) {
    return;
  }

  let currentQuery  = '';
  let debounceId    = null;
  let lastRequestId = 0;

  function clearResults() {
    tbody.innerHTML = '';
    wrap.hidden = true;
    status.textContent = '';
  }

  function renderResults(data) {
    tbody.innerHTML = '';

    if (!data || !Array.isArray(data.results) || data.results.length === 0) {
      wrap.hidden = true;
      status.textContent = data && data.error
        ? data.error
        : 'No characters found.';
      return;
    }

    data.results.forEach(function(r) {
      const tr = document.createElement('tr');
      tr.className = 'clickable-row';

      const href = '/character?guid=' + encodeURIComponent(r.guid);
      tr.setAttribute('data-href', href);

      const nameTd  = document.createElement('td');
      const levelTd = document.createElement('td');
      const classTd = document.createElement('td');
      const raceTd  = document.createElement('td');
      const ilvlTd  = document.createElement('td');

      const link = document.createElement('a');
      link.href = href;
      link.textContent = r.name || '(Unknown)';
      link.className = 'armory-link-strong';
      nameTd.appendChild(link);

      levelTd.textContent = r.level != null ? r.level : '—';
      classTd.textContent = r.class_name || '—';
      raceTd.textContent  = r.race_name  || '—';

      if (typeof r.avg_ilvl === 'number') {
        ilvlTd.textContent = r.avg_ilvl.toFixed(1);
      } else {
        ilvlTd.textContent = '—';
      }

      tr.appendChild(nameTd);
      tr.appendChild(levelTd);
      tr.appendChild(classTd);
      tr.appendChild(raceTd);
      tr.appendChild(ilvlTd);

      tbody.appendChild(tr);
    });

    wrap.hidden = false;
    status.textContent = '';

    // Now that we created new rows, bind click handlers to them
    bindRowClicks(wrap);
  }

  function performSearch(query) {
    const reqId = ++lastRequestId;

    status.textContent = 'Searching…';
    wrap.hidden = true;
    tbody.innerHTML = '';

    fetch('/armory/search?q=' + encodeURIComponent(query), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function(resp) {
        if (!resp.ok) {
          throw new Error('HTTP ' + resp.status);
        }
        return resp.json();
      })
      .then(function(data) {
        if (reqId !== lastRequestId) {
          // Outdated response, ignore.
          return;
        }
        renderResults(data);
      })
      .catch(function(err) {
        if (reqId !== lastRequestId) return;
        wrap.hidden = true;
        status.textContent = 'Error during search.';
        console.error('Armory search error', err);
      });
  }

  input.addEventListener('input', function() {
    const value = input.value.trim();

    if (value.length < 3) {
      currentQuery = '';
      if (debounceId !== null) {
        clearTimeout(debounceId);
        debounceId = null;
      }
      clearResults();
      return;
    }

    currentQuery = value;
    if (debounceId !== null) {
      clearTimeout(debounceId);
    }
    debounceId = setTimeout(function() {
      performSearch(currentQuery);
    }, 250);
  });
})();
</script>

<style>
  .armory-page {
    max-width: 1080px;
    margin: 0 auto;
    padding: 5.5rem 1.5rem 3rem; /* leave room for fixed header */
  }

  .armory-header {
    margin-bottom: 2rem;
  }

  .armory-title {
    margin: 0 0 0.25rem;
    font-size: 2.1rem;
  }

  .armory-subtitle {
    margin: 0;
    font-size: 0.95rem;
    color: #a8b6d4;
  }

  .armory-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
    gap: 1.5rem;
  }

  .armory-card {
    background: radial-gradient(circle at top left, #101728, #050914 60%);
    border-radius: 12px;
    border: 1px solid rgba(90, 140, 220, 0.4);
    box-shadow: 0 0 18px rgba(0, 0, 0, 0.7);
    padding: 1.25rem 1.3rem 1.1rem;
  }

  .armory-section-title {
    margin-top: 0;
    margin-bottom: 0.9rem;
    font-size: 1.3rem;
  }

  .armory-help-text {
    margin: 0 0 0.75rem;
    font-size: 0.85rem;
    color: #8d9bc0;
  }

  .armory-search-bar {
    margin-bottom: 0.75rem;
  }

  .armory-search-input {
    width: 100%;
    padding: 0.45rem 0.6rem;
    border-radius: 6px;
    border: 1px solid rgba(90, 140, 220, 0.7);
    background: rgba(3, 7, 18, 0.9);
    color: #e4f2ff;
    font-size: 0.95rem;
  }

  .armory-search-input:focus {
    outline: none;
    border-color: #c9a34f;
    box-shadow: 0 0 0 1px rgba(201, 163, 79, 0.6);
  }

  .armory-search-status {
    min-height: 1.1rem;
    font-size: 0.8rem;
    color: #9aa8c8;
  }

  .armory-empty {
    margin: 0.3rem 0 0.2rem;
    font-size: 0.9rem;
    color: #a8b6d4;
  }

  .armory-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }

  .armory-table th,
  .armory-table td {
    border-bottom: 1px solid #25324c;
    padding: 0.45rem 0.6rem;
    text-align: left;
  }

  .armory-table thead th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9eb3e6;
  }

  .armory-table tbody td {
    font-size: 0.88rem;
    white-space: nowrap;
  }

  .armory-table tr:last-child th,
  .armory-table tr:last-child td {
    border-bottom: none;
  }

  .armory-top-table-wrap,
  .armory-search-results-wrap {
    overflow-x: auto;
  }

  .armory-inline-icon {
    vertical-align: -3px;
    margin-right: 6px;
    border-radius: 4px;
  }

  .armory-link-strong {
    font-weight: 600;
  }

  /* Optional: visual cue that rows are clickable */
  table.clickable-table tr.clickable-row {
    cursor: pointer;
  }
  table.clickable-table tr.clickable-row:hover {
    background: rgba(42, 60, 110, 0.5);
  }

  @media (max-width: 860px) {
    .armory-layout {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 600px) {
    .armory-page {
      padding: 5.5rem 1rem 2.5rem;
    }

    .armory-title {
      font-size: 1.7rem;
    }

    .armory-section-title {
      font-size: 1.15rem;
    }
  }
</style>
