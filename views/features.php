<?php
/**
 * Features landing view: loops through configured feature entries and renders details.
 *
 * @var array $features Array of feature definitions keyed by slug.
 */
?>
<div class="features-page">

  <header class="features-page-header">
    <h1 class="features-page-title">Realm Features</h1>
    <p class="features-page-subtitle">
      A closer look at how Kardinal WoW is configured: pacing, progression, bots, and the systems
      that keep the world alive even when population is small.
    </p>
  </header>

  <main class="features-detail">

      <?php foreach ($features as $slug => $feature): ?>
        <article id="feature-<?= htmlspecialchars($slug) ?>" class="features-detail-card">
          <?php if (!empty($feature['pill'])): ?>
            <div class="features-detail-pill">
              <?= htmlspecialchars($feature['pill']) ?>
            </div>
          <?php endif; ?>

          <h2 class="features-detail-title">
            <?= htmlspecialchars($feature['name']) ?>
          </h2>

          <?php if (!empty($feature['summary'])): ?>
            <p class="features-detail-summary">
              <?= htmlspecialchars($feature['summary']) ?>
            </p>
          <?php endif; ?>

          <div class="features-detail-body">
            <?= $feature['body'] ?>
          </div>
        </article>
      <?php endforeach; ?>

      <div class="features-detail-footer">
        <a href="/" class="features-detail-link">← Back to Home</a>
        <span class="features-detail-spacer"></span>
        <a href="/armory" class="features-detail-link">Open Armory</a>
      </div>

    </main>

</div>
