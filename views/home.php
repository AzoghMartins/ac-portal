<?php
/**
 * Landing page view: showcases the realm intro, live status, features, and roadmap.
 *
 * @var int|null    $accounts
 * @var string|null $ac_rev
 * @var string|null $last_restart
 * @var string|null $uptime_human
 * @var string|null $last_update
 * @var int|null    $online_alliance
 * @var int|null    $online_horde
 * @var bool|null   $realm_online
 * @var string|null $realm_name
 * @var int|null    $bot_characters
 * @var int|null    $player_characters
 */

use App\Auth;

$user     = Auth::user();
$loggedIn = $user !== null;
$username = $user['username'] ?? null;

$allianceOnline = (int)($online_alliance ?? 0);
$hordeOnline    = (int)($online_horde ?? 0);

$coreRevision = null;
if (!empty($ac_rev)) {
    if (preg_match('/AzerothCore rev\.?\s+([0-9a-f]+)/i', (string)$ac_rev, $match)) {
        $coreRevision = 'AzerothCore rev. ' . $match[1];
    } else {
        $coreRevision = $ac_rev;
    }
}

$uptimeStr     = $uptime_human ?? null;
$realmName     = $realm_name ?? null;
$realmOnline   = (bool)($realm_online ?? false);
$botCount      = (int)($bot_characters ?? 0);
$playerCount   = (int)($player_characters ?? 0);
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
              This realm is not a frozen snapshot of the Wrath era — it is a long passage through forgotten ages,
              beginning in the rough, unpolished world of Vanilla and unfolding into the shadowed epochs that followed.
            </p>
            <p>
              Beyond this portal, the chronicle of your journey takes form. Here you will follow your heroes from the
              low places of early Azeroth through the gathering storms of history, until the frost of Northrend settles
              upon their path.
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
            Experience and loot tuned to preserve the authentic spirit of Wrath while keeping the leveling journey
            meaningful, challenging, and true to its original pace.
          </p>
          <p class="feature-link">
            <a href="/features?section=blizzlike">Learn more →</a>
          </p>
        </div>

        <div class="feature-block">
          <h3 class="feature-title">Individual Progression</h3>
          <p class="feature-text">
            Advance through Azeroth’s history in ordered milestones, unlocking new eras, dungeons, and raids only as your
            character completes the trials of previous ages.
          </p>
          <p class="feature-link">
            <a href="/features?section=progression">Learn more →</a>
          </p>
        </div>

        <div class="feature-block">
          <h3 class="feature-title">Playerbots</h3>
          <p class="feature-text">
            Journey with AI companions who fill groups, assist in leveling, and stand with you in the dark or dangerous
            corners of the world when others are not around.
          </p>
          <p class="feature-link">
            <a href="/features?section=playerbots">Learn more →</a>
          </p>
        </div>

        <div class="feature-block">
          <h3 class="feature-title">Auction House Bot</h3>
          <p class="feature-text">
            A fully automated marketplace that supplies goods, maintains economic flow, and keeps the trading scene alive
            even during quieter hours on the realm.
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
        <div class="realm-status-left">
          <h2 class="section-title realm-status-title">Realm at a Glance</h2>
          <div class="realm-status-text">
            <p>
              Behind every login screen and loading bar, the realm lives and breathes on the worldserver. Each dawn at
              04:00 CET, the realm briefly rests, ensuring a stable foundation for every hero’s journey.
            </p>
            <p>
              What you see here are the quieter truths of that world — its name, language, temperament, and the balance
              between heroes and the automated caretakers who keep the realm feeling alive.
            </p>
          </div>
        </div>

        <div class="realm-status-grid">
          <div class="realm-stat">
            <span class="realm-stat-label">Realm Name</span>
            <span class="realm-stat-value">
              <?php if ($realmName): ?>
                <?= htmlspecialchars($realmName) ?>
              <?php else: ?>
                <em>Unknown</em>
              <?php endif; ?>
              &nbsp;·&nbsp;
              <?php if ($realmOnline): ?>
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
            <span class="realm-stat-value"><?= $botCount ?></span>
          </div>

          <div class="realm-stat">
            <span class="realm-stat-label">Player Characters</span>
            <span class="realm-stat-value"><?= $playerCount ?></span>
          </div>

          <div class="realm-stat">
            <span class="realm-stat-label">Online Players</span>
            <span class="realm-stat-value">
              <?= $allianceOnline ?> Alliance · <?= $hordeOnline ?> Horde
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
        <h2 class="section-title discord-title">Join the Community</h2>
        <p class="discord-text">
          Beyond the boundaries of the realm lies a gathering place for heroes — a space where stories are shared,
          guides are forged, and adventurers seek allies for the battles ahead.
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
            Realms like this are never truly finished; they are reforged over time. Below is a glimpse of the work
            already underway and the features still waiting to be called forth.
          </p>
        </div>

        <div class="roadmap-right">
          <ul class="roadmap-list">
            <li>
              <span class="roadmap-pill roadmap-pill--soon">Soon</span>
              <div class="roadmap-item">
                <div class="roadmap-item-title">Challenge Modes</div>
                <div class="roadmap-item-text">
                  Challenge yourself with Hardcore, Crafter, and other special gameplay modifiers.
                </div>
              </div>
            </li>
            <li>
              <span class="roadmap-pill roadmap-pill--soon">Soon</span>
              <div class="roadmap-item">
                <div class="roadmap-item-title">Features Overview Page</div>
                <div class="roadmap-item-text">
                  A richer features hub that explains progression tiers, bot behaviour, and realm rules.
                </div>
              </div>
            </li>
            <li>
              <span class="roadmap-pill roadmap-pill--soon">Soon</span>
              <div class="roadmap-item">
                <div class="roadmap-item-title">Account &amp; Character Tools</div>
                <div class="roadmap-item-text">
                  Expanded web tools for managing characters and preparing for future story systems.
                </div>
              </div>
            </li>
            <li>
              <span class="roadmap-pill roadmap-pill--later">Later</span>
              <div class="roadmap-item">
                <div class="roadmap-item-title">Story-Driven Web Features</div>
                <div class="roadmap-item-text">
                  Deeper integration between in-game milestones and web chronicles for each hero.
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</div>
