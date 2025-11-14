<?php
/** @var int|null    $accounts */
/** @var string|null $ac_rev */
/** @var string|null $last_restart */
/** @var string|null $uptime_human */
/** @var string|null $last_update */
/** @var int|null    $online_alliance */
/** @var int|null    $online_horde */

use App\Auth;

$user      = Auth::user();
$loggedIn  = $user !== null;
$username  = $user['username'] ?? null;

$allianceOnline = (int)($online_alliance ?? 0);
$hordeOnline    = (int)($online_horde ?? 0);

$coreRevisionRaw = $ac_rev ?? null;
$coreRevision    = null;

if ($coreRevisionRaw) {
    if (preg_match('/AzerothCore rev\.?\s+([0-9a-f]+)/i', $coreRevisionRaw, $m)) {
        $coreRevision = 'AzerothCore rev. ' . $m[1];
    } else {
        $coreRevision = $coreRevisionRaw;
    }
}

$lastRestartStr = $last_restart ?? null;
$uptimeStr      = $uptime_human ?? null;
$lastUpdateStr  = $last_update ?? null;

$lastRestartStr = $last_restart ?? null;
$uptimeStr      = $uptime_human ?? null;
$lastUpdateStr  = $last_update ?? null;
?>
<div class="home-page">

  <!-- HERO / LANDING -->
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-text">
        <p class="hero-kicker">Wrath of the Lich King — 3.3.5a</p>
        <div class="hero-logo">
    <img src="/assets/images/wrathlogo.png" alt="Wrath of the Lich King Logo">
</div>


        <h3>
          Step through the frost and into a world held forever in the age of the Lich King.
          Your characters, your story.
</h3>

        <div class="hero-actions">
          <?php if ($loggedIn): ?>
            <a href="/account" class="btn btn-primary">My Account</a>
          <?php else: ?>
            <a href="/login" class="btn btn-primary">Log In</a>
          <?php endif; ?>
          <a href="/armory" class="btn btn-secondary">Enter Armory</a>
        </div>

        <?php if ($loggedIn && $username): ?>
          <p class="hero-user">Signed in as <strong><?= htmlspecialchars($username) ?></strong></p>
        <?php endif; ?>
      </div>


    </div>

  <div class="hero-status-bar">
    <div class="status-item">
        <span class="status-label">Online:</span>
        <span class="status-value"><?= $allianceOnline ?> Alliance · <?= $hordeOnline ?> Horde</span>
    </div>

    <?php if ($coreRevision): ?>
    <div class="status-item">
        <span class="status-label">Core:</span>
        <span class="status-value"><?= htmlspecialchars($coreRevision) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($uptimeStr): ?>
    <div class="status-item">
        <span class="status-label">Uptime:</span>
        <span class="status-value"><?= htmlspecialchars($uptimeStr) ?></span>
    </div>
    <?php endif; ?>
</div>


  </section>

  <!-- INTRO / REALM FLAVOR -->
<section class="section intro">
  <div class="section-inner intro-inner">

    <div class="intro-body">
      <div class="intro-image">
        <img src="/assets/images/Lich_King.png" alt="The Lich King">
      </div>

      <div class="intro-content">
        <h2 class="section-title intro-title">Welcome to Kardinal WoW</h2>

        <div class="intro-text">
          <p class="section-lead">
            This realm is not a frozen snapshot of the Wrath era — it is a long passage through forgotten ages, beginning in the rough, unpolished world of Vanilla and unfolding into the shadowed epochs that followed. Each character walks their own thread through time, unlocking new eras only when the echoes of the past recognize their triumphs. The world reshapes itself around you, layering ancient memory upon present struggle, as if Azeroth itself stirs to watch your ascent.
          </p>
          <p>
            Beyond this portal, the chronicle of your journey takes form. Here you will follow your heroes from the low places of early Azeroth through the gathering storms of history, until the frost of Northrend settles upon their path. The armory holds the marks of every victory; every milestone becomes a whispered chapter in the greater tale. And soon, new tools will awaken to guide your progress across these shifting ages, leading each character toward the citadel where the Lich King waits — a place where time, legend, and doom converge beneath the cold.
          </p>
        </div>
      </div>
    </div>

  </div>
</section>



  <!-- SERVER FEATURES -->
  <section class="section features">
  <div class="section-inner features-inner">

    <h2 class="section-title features-title">Server Features</h2>

    <div class="features-grid">

      <div class="feature-block">
        <h3 class="feature-title">Blizzlike Rates</h3>
        <p class="feature-text">
          Experience and loot tuned to preserve the authentic spirit of Wrath while keeping the leveling journey meaningful, challenging, and true to its original pace.
        </p>
        <p class="feature-link">
          <a href="/features?section=blizzlike">Learn more →</a>
        </p>
      </div>

      <div class="feature-block">
        <h3 class="feature-title">Individual Progression</h3>
        <p class="feature-text">
          Advance through Azeroth’s history in ordered milestones, unlocking new eras, dungeons, and raids only as your character completes the trials of previous ages.
        </p>
        <p class="feature-link">
          <a href="/features?section=progression">Learn more →</a>
        </p>
      </div>

      <div class="feature-block">
        <h3 class="feature-title">Playerbots</h3>
        <p class="feature-text">
          Journey with AI companions who fill groups, assist in leveling, and stand with you in the dark or dangerous corners of the world when others are not around.
        </p>
        <p class="feature-link">
          <a href="/features?section=playerbots">Learn more →</a>
        </p>
      </div>

      <div class="feature-block">
        <h3 class="feature-title">Auction House Bot</h3>
        <p class="feature-text">
          A fully automated marketplace that supplies goods, maintains economic flow, and keeps the trading scene alive even during quieter hours on the realm.
        </p>
        <p class="feature-link">
          <a href="/features?section=ahbot">Learn more →</a>
        </p>
      </div>

    </div>
  </div>
</section>



  <!-- REALM STATUS DETAILS -->
 <section class="section realm-status">
  <div class="section-inner">

    <div class="realm-status-layout">

      <!-- LEFT COLUMN -->
      <div class="realm-status-left">
        <h2 class="section-title realm-status-title">Realm at a Glance</h2>

        <div class="realm-status-text">
          <p>
            Behind every login screen and loading bar, the realm lives and breathes on the worldserver. When it rises, zones awaken, NPCs stir from their loops, and the hidden machinery of Azeroth begins its silent work again. Each dawn at 04:00 CET, the realm briefly falls into a measured silence, a daily renewal that keeps its foundations steady and untouched by lingering instability. And when Sunday arrives, the world undergoes a deeper restoration at reset — the core itself reforged and brought in line with the latest release to ensure the ground beneath your path remains strong.
          </p>
          <p>
            What you see here are the quieter truths of that heart — the name and nature of the realm you walk upon, its tongue and temperament, and whether it presently stands awake or at rest. Alongside it, the numbers whisper how many bot-born constructs wander its paths to keep the world feeling alive, and how many true player characters have bound themselves to this shard of Azeroth.
          </p>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="realm-status-grid">

  <div class="realm-stat">
    <span class="realm-stat-label">Realm Name</span>
    <span class="realm-stat-value">
      <?php if (!empty($realm_name)): ?>
        <?= htmlspecialchars($realm_name) ?>
      <?php else: ?>
        <em>Unknown</em>
      <?php endif; ?>
      &nbsp;·&nbsp;
      <?php if ($realm_online): ?>
        <span class="realm-status-pill realm-status-pill--online">Online</span>
      <?php else: ?>
        <span class="realm-status-pill realm-status-pill--offline">Offline</span>
      <?php endif; ?>
    </span>
  </div>

  <div class="realm-stat">
    <span class="realm-stat-label">Realm Type</span>
    <span class="realm-stat-value">English RPPVP</span>
  </div>

  <div class="realm-stat">
    <span class="realm-stat-label">Bot Characters</span>
    <span class="realm-stat-value"><?= (int)$bot_characters ?></span>
  </div>

  <div class="realm-stat">
    <span class="realm-stat-label">Player Characters</span>
    <span class="realm-stat-value"><?= (int)$player_characters ?></span>
  </div>

  <div class="realm-stat">
    <span class="realm-stat-label">Online Players</span>
    <span class="realm-stat-value">
      <?= (int)$online_alliance ?> Alliance · <?= (int)$online_horde ?> Horde
    </span>
  </div>

</div>


    </div>
  </div>
</section>



  <!-- DISCORD / COMMUNITY -->
  <section class="section discord-section">
  <div class="section-inner discord-inner">

    <div class="discord-content">
      <h2 class="section-title discord-title">Join the Warcrafter Community</h2>

      <p class="discord-text">
        Beyond the boundaries of the realm lies a gathering place for heroes — a space where stories are shared,
        guides are forged, and adventurers seek allies for the battles ahead. Whether you are returning to Azeroth
        or taking your first steps into its frozen history, the community is ready to welcome you.
      </p>

      <a href="https://discord.gg/pVg8dhx6xf" class="discord-button" target="_blank" rel="noopener">
        Enter Discord
      </a>
    </div>

  </div>
</section>


  <!-- IN DEVELOPMENT / ROADMAP TEASER -->
  <section class="section roadmap">
  <div class="section-inner roadmap-inner">

    <div class="roadmap-layout">

      <div class="roadmap-left">
        <h2 class="section-title roadmap-title">Roadmap</h2>

        <p class="roadmap-text">
          Realms like this are never truly finished; they are reforged over time. Below is a glimpse of the
          work already underway and the features still waiting to be called forth. As the world stabilizes, the
          tools around it will sharpen, giving you more ways to shape, track, and share your journey.
        </p>
      </div>

      <div class="roadmap-right">
        <ul class="roadmap-list">
          <li>
            <span class="roadmap-pill roadmap-pill--soon">Soon</span>
            <div class="roadmap-item">
              <div class="roadmap-item-title">Challenge Modes</div>
              <div class="roadmap-item-text">
                Challenge yourself in various challenge modes. <span class="logo">Hardcore</span> for permadeath,
                <span class="logo">Crafter</span> where you can only wear what you craft, and many more options.
              </div>
            </div>
          </li>

          <li>
            <span class="roadmap-pill roadmap-pill--soon">Soon</span>
            <div class="roadmap-item">
              <div class="roadmap-item-title">Features Overview Page</div>
              <div class="roadmap-item-text">
                A dedicated Features section with deeper explanations of progression tiers, bot behaviour, and
                realm rules, all presented in a lore-friendly way.
              </div>
            </div>
          </li>

          <li>
            <span class="roadmap-pill roadmap-pill--soon">Soon</span>
            <div class="roadmap-item">
              <div class="roadmap-item-title">Account &amp; Character Tools</div>
              <div class="roadmap-item-text">
                Expanded web tools for managing characters, viewing detailed stats, and preparing for future
                story and progression tracking systems.
              </div>
            </div>
          </li>

          <li>
            <span class="roadmap-pill roadmap-pill--later">Later</span>
            <div class="roadmap-item">
              <div class="roadmap-item-title">Story-Driven Web Features</div>
              <div class="roadmap-item-text">
                Deeper integration between in-game milestones and out-of-game narrative, allowing characters to
                build visible chronicles of their journeys over time.
              </div>
            </div>
          </li>
        </ul>
      </div>

    </div>

  </div>
</section>


</div>
