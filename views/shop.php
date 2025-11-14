<?php
/** @var array|null $viewer */
/** @var int|null $guid */
/** @var string|null $tab */
?>
<h1>Shop</h1>

<section class="card">
  <h2>Under Construction</h2>
  <p>
    This part of the AC Portal is currently under construction.
    Soon you’ll be able to buy account-bound goodies for your characters here.
  </p>

  <?php if ($guid): ?>
    <p>
      You arrived here from character
      <strong>#<?= (int)$guid ?></strong>.
      Once the shop is live, this page will show options tailored to that character.
    </p>
  <?php endif; ?>

  <?php if (!$viewer): ?>
    <p>
      You’re not logged in. When the shop is available, you’ll need to
      <a href="/login">log in</a> to use it.
    </p>
  <?php else: ?>
    <p>
      Logged in as <strong><?= htmlspecialchars($viewer['username'] ?? '') ?></strong>.
    </p>
  <?php endif; ?>

  <?php if (!empty($tab)): ?>
    <p>
      Requested tab: <code><?= htmlspecialchars($tab) ?></code>
      (this will matter once we add real shop tabs).
    </p>
  <?php endif; ?>
</section>

<p>
  <?php if ($guid): ?>
    <a href="/character?guid=<?= (int)$guid ?>">&larr; Back to Character</a>
    &nbsp;|&nbsp;
  <?php endif; ?>
  <a href="/account">Back to My Account</a>
</p>
