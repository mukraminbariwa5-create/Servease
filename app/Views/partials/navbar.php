<?php
/**
 * Top bar. Computer: logo, location, search, main links and account.
 * Phone: logo and quick actions only. The tab bar at the bottom carries navigation.
 */
$u = user();
$counts = nav_counts();
$latestNotifs = $u ? DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6', [(int) $u['id']]) : [];
$notifIcon = ['booking' => 'calendar', 'message' => 'message', 'quote' => 'file-text', 'review' => 'star',
              'payment' => 'wallet', 'verification' => 'shield-check', 'welcome' => 'bell', 'job' => 'briefcase'];
$city = current_city();
$q = trim((string) ($_GET['q'] ?? ''));
$links = [];
if (is_role('provider')) {
    // A pro's workspace: everything else lives in the account menu
    $links[] = ['/dashboard', 'Dashboard', current_path_is('/dashboard')];
    $links[] = ['/jobs', 'Find work', current_path_is('/jobs')];
} else {
    $links[] = ['/', 'Home', request_path() === '/'];
    $links[] = ['/services', 'Services', current_path_is('/services') || current_path_is('/providers')];
    if (!$u) $links[] = ['/jobs', 'Job board', current_path_is('/jobs')];
}
if (is_role('admin')) {
    $links[] = ['/admin', 'Admin panel', current_path_is('/admin')];
} elseif ($u) {
    $links[] = ['/bookings', 'Bookings', current_path_is('/bookings')];
}
if ($u) {
    $links[] = ['/messages', 'Inbox', current_path_is('/messages')];
} else {
    $links[] = ['/how-it-works', 'How it works', current_path_is('/how-it-works')];
}
?>
<?php $bar = app_bar(); ?>
<header class="topbar<?= $bar ? ' has-appbar' : '' ?>" data-topbar>
  <?php if ($bar): ?>
  <!-- Phone: drill-down screens get an app-style bar (back, title, action) instead of the website header -->
  <div class="appbar">
    <a class="icon-btn appbar-back" href="<?= e(url($bar['back'])) ?>" data-back aria-label="Back"><?= icon('arrow-left') ?></a>
    <span class="appbar-title"><?= e($bar['title']) ?></span>
    <span class="appbar-action"><?= $bar['action'] ?></span>
  </div>
  <?php endif; ?>
  <div class="container topbar-inner">
    <?= partial('brand') ?>

    <details class="dropdown loc-pick">
      <summary class="loc-chip" aria-label="Choose your city, currently <?= e($city) ?>">
        <span class="loc-label">Location</span>
        <span class="loc-val"><?= icon('map-pin') ?><span class="truncate"><?= e($city) ?></span><?= icon('chevron-down', 'icon-sm') ?></span>
      </summary>
      <div class="dropdown-menu loc-menu">
        <div class="dropdown-head"><strong>Show services in</strong></div>
        <div class="loc-list">
          <?php foreach (cities() as $c): ?>
            <a class="dropdown-item<?= $c === $city ? ' is-current' : '' ?>" href="<?= e(url('/services', ['city' => $c])) ?>"><?= icon($c === $city ? 'check' : 'map-pin') ?><?= e($c) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </details>

    <?php /* Home has the big banner search, so the header one would be a duplicate there */ ?>
    <?php if (request_path() !== '/'): ?>
    <form class="top-search" action="<?= e(url('/services')) ?>" method="get" role="search">
      <?= icon('search') ?>
      <label class="sr-only" for="top-q">Search services or pros</label>
      <input id="top-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Search services, providers, or keywords..." autocomplete="off"<?= !empty($_GET['focus']) ? ' autofocus' : '' ?>>
    </form>
    <?php endif; ?>

    <nav class="top-links hide-md" aria-label="Main">
      <?php foreach ($links as [$href, $label, $on]): ?>
        <a class="top-link<?= $on ? ' is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= e($label) ?>
          <?php if ($href === '/messages' && $counts['msgs']): ?><span class="count-pill"><?= count_label($counts['msgs']) ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="top-actions">
      <?php if (!$u): ?>
        <a class="btn btn-ghost hide-md" href="<?= e(url('/login')) ?>">Log in</a>
        <a class="btn btn-primary hide-md" href="<?= e(url('/register')) ?>">Sign up</a>
        <a class="btn btn-primary btn-sm show-md" href="<?= e(url('/login')) ?>">Log in</a>
      <?php else: ?>
        <details class="dropdown">
          <summary class="icon-btn" aria-label="Notifications<?= $counts['notifs'] ? ', ' . $counts['notifs'] . ' unread' : '' ?>">
            <?= icon('bell') ?>
            <?php if ($counts['notifs']): ?><span class="count-dot"><?= count_label($counts['notifs']) ?></span><?php endif; ?>
          </summary>
          <div class="dropdown-menu notif-menu">
            <div class="dropdown-head">
              <strong>Notifications</strong>
              <?php if ($counts['notifs']): ?>
                <form method="post" action="<?= e(url('/notifications/read-all')) ?>"><?= csrf_field() ?><button class="btn-link small" type="submit" data-no-lock>Mark all read</button></form>
              <?php endif; ?>
            </div>
            <?php if (!$latestNotifs): ?>
              <div class="empty empty-sm"><div class="empty-icon"><?= icon('bell') ?></div><p class="small">You're all caught up.</p></div>
            <?php endif; ?>
            <?php foreach ($latestNotifs as $n): ?>
              <a class="notif-item <?= $n['read_at'] ? '' : 'is-unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>">
                <span class="notif-icon"><?= icon($notifIcon[$n['type']] ?? 'bell') ?></span>
                <span class="grow">
                  <span class="notif-title"><?= e($n['title']) ?></span>
                  <?php if ($n['body']): ?><span class="notif-body"><?= e(str_limit($n['body'], 80)) ?></span><?php endif; ?>
                  <span class="tiny subtle"><?= e(time_ago($n['created_at'])) ?></span>
                </span>
              </a>
            <?php endforeach; ?>
            <a class="notif-foot" href="<?= e(url('/notifications')) ?>">View all notifications</a>
          </div>
        </details>
        <?php /* Phones: the avatar opens the Profile tab (the tab bar already carries navigation, so no extra menu) */ ?>
        <a class="user-chip user-chip-phone show-md" href="<?= e(url('/account')) ?>" aria-label="Your profile"><?= avatar($u, 'sm') ?></a>
        <details class="dropdown user-menu hide-md">
          <summary class="user-chip" aria-label="Account menu"><?= avatar($u, 'sm') ?><?= icon('chevron-down', 'icon-sm') ?></summary>
          <div class="dropdown-menu">
            <div class="dropdown-head">
              <div class="row" style="--gap:10px">
                <?= avatar($u, 'md') ?>
                <div class="grow">
                  <div class="semibold truncate"><?= e(full_name($u)) ?></div>
                  <div class="small subtle truncate"><?= e($u['email']) ?></div>
                </div>
              </div>
            </div>
            <?php if (is_role('admin')): ?>
              <a class="dropdown-item" href="<?= e(url('/admin')) ?>"><?= icon('shield') ?> Admin panel</a>
            <?php endif; ?>
            <?php if (is_role('provider')): ?>
              <a class="dropdown-item" href="<?= e(url('/providers/' . $u['id'])) ?>"><?= icon('user') ?> View public profile</a>
              <a class="dropdown-item" href="<?= e(url('/provider/profile')) ?>"><?= icon('pencil') ?> Edit pro profile</a>
              <a class="dropdown-item" href="<?= e(url('/my/quotes')) ?>"><?= icon('file-text') ?> My quotes</a>
            <?php elseif (is_role('client')): ?>
              <a class="dropdown-item" href="<?= e(url('/my/jobs')) ?>"><?= icon('briefcase') ?> My jobs</a>
              <a class="dropdown-item" href="<?= e(url('/saved')) ?>"><?= icon('heart') ?> Saved pros</a>
            <?php endif; ?>
            <a class="dropdown-item" href="<?= e(url('/notifications')) ?>"><?= icon('bell') ?> Notifications</a>
            <a class="dropdown-item" href="<?= e(url('/settings')) ?>"><?= icon('settings') ?> Settings</a>
            <div class="dropdown-item dropdown-lang"><?= icon('message-square') ?> <span class="grow">Language</span><?= partial('lang-switch') ?></div>
            <button class="dropdown-item" type="button" data-sound-toggle aria-pressed="true"><?= icon('volume') ?> <span class="grow">Sounds</span><span class="switch-pill" aria-hidden="true"></span></button>
            <button class="dropdown-item" type="button" data-install hidden><?= icon('upload') ?> Install app</button>
            <button class="dropdown-item theme-toggle" type="button" data-theme-toggle>
              <?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?>
              <span class="when-light">Dark mode</span><span class="when-dark">Light mode</span>
            </button>
            <div class="dropdown-sep"></div>
            <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?>
              <button class="dropdown-item" type="submit"><?= icon('log-out') ?> Log out</button>
            </form>
          </div>
        </details>
      <?php endif; ?>
    </div>
  </div>
</header>
