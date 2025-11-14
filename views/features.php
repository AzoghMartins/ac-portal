<?php
/**
 * @var array  $features  Array of feature definitions.
 * @var string $active    Slug of the currently active feature.
 */

$activeFeature = $features[$active] ?? reset($features);
?>
<div class="features-page">

  <header class="features-page-header">
    <h1 class="features-page-title">Realm Features</h1>
    <p class="features-page-subtitle">
      A closer look at how Kardinal WoW is configured: pacing, progression, bots, and the systems
      that keep the world alive even when population is small.
    </p>
  </header>

  <div class="features-layout">

    <!-- SIDEBAR NAV -->
    <aside class="features-nav">
      <h2 class="features-nav-title">Sections</h2>
      <ul class="features-nav-list">
        <?php foreach ($features as $slug => $feature): ?>
          <?php
            $isActive = ($slug === $active);
            $href = '/features?section=' . urlencode($slug);
          ?>
          <li class="features-nav-item<?= $isActive ? ' is-active' : '' ?>">
            <a href="<?= htmlspecialchars($href) ?>" class="features-nav-link">
              <span class="features-nav-name">
                <?= htmlspecialchars($feature['name']) ?>
              </span>
              <?php if (!empty($feature['pill'])): ?>
                <span class="features-nav-pill">
                  <?= htmlspecialchars($feature['pill']) ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="features-detail">

      <article class="features-detail-card">
        <?php if (!empty($activeFeature['pill'])): ?>
          <div class="features-detail-pill">
            <?= htmlspecialchars($activeFeature['pill']) ?>
          </div>
        <?php endif; ?>

        <h2 class="features-detail-title">
          <?= htmlspecialchars($activeFeature['name']) ?>
        </h2>

        <?php if (!empty($activeFeature['summary'])): ?>
          <p class="features-detail-summary">
            <?= htmlspecialchars($activeFeature['summary']) ?>
          </p>
        <?php endif; ?>

        <div class="features-detail-body">
          <?= $activeFeature['body'] ?>
        </div>

        <div class="features-detail-footer">
          <a href="/" class="features-detail-link">← Back to Home</a>
          <span class="features-detail-spacer"></span>
          <a href="/armory" class="features-detail-link">Open Armory</a>
        </div>
      </article>

    </main>

  </div>

</div>
