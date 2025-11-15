<?php
/**
 * Armory landing page: exposes search UI and the top 10 characters by iLvl.
 *
 * @var array $rows Top 10 rows: each has guid, name, level, class, race, avg_ilvl
 */

use App\WowHelper;
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
      const classId = Object.prototype.hasOwnProperty.call(r, 'class') ? r['class'] : null;
      if (typeof classId === 'number' || (typeof classId === 'string' && classId !== '')) {
        const classImg = document.createElement('img');
        classImg.src = '/assets/icons/class/' + encodeURIComponent(classId) + '.gif';
        classImg.alt = r.class_name || 'Class';
        classImg.width = 18;
        classImg.height = 18;
        classImg.className = 'armory-inline-icon';
        classTd.appendChild(classImg);
      } else {
        classTd.textContent = '—';
      }
      const raceId = Object.prototype.hasOwnProperty.call(r, 'race') ? r['race'] : null;
      const gender = Object.prototype.hasOwnProperty.call(r, 'gender') ? r['gender'] : 0;
      if (
        (typeof raceId === 'number' && !Number.isNaN(raceId)) ||
        (typeof raceId === 'string' && raceId !== '')
      ) {
        const resolvedGender = typeof gender === 'number' ? gender : parseInt(gender, 10) || 0;
        const raceImg = document.createElement('img');
        raceImg.src = '/assets/icons/race/' + encodeURIComponent(raceId) + '-' + encodeURIComponent(resolvedGender) + '.gif';
        raceImg.alt = r.race_name || 'Race';
        raceImg.width = 18;
        raceImg.height = 18;
        raceImg.className = 'armory-inline-icon';
        raceTd.appendChild(raceImg);
      } else {
        raceTd.textContent = '—';
      }

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
