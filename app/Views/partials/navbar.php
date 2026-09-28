<?php
$u = user();
$dark = !empty($navDark);
if ($u) {
    $uid = (int) $u['id'];
    $unreadNotifs = (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$uid]);
    $latestNotifs = DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6', [$uid]);
    $unreadMsgs = (int) DB::value(
        'SELECT COUNT(*) FROM messages m JOIN conversations c ON c.id = m.conversation_id
         WHERE (c.user_one_id = ? OR c.user_two_id = ?) AND m.sender_id <> ? AND m.read_at IS NULL',
        [$uid, $uid, $uid]
    );
}
$notifIcon = ['booking' => 'calendar', 'message' => 'message', 'quote' => 'file-text', 'review' => 'star',
              'payment' => 'wallet', 'verification' => 'shield-check', 'welcome' => 'sparkles', 'job' => 'briefcase'];
?>
<header class="navbar <?= $dark ? 'navbar-dark' : '' ?>">
  <div class="container navbar-inner">
    <?= partial('brand') ?>
    <nav class="nav-links" aria-label="Main">
      <a class="nav-link <?= current_path_is('/providers') ? 'is-active' : '' ?>" href="<?= e(url('/providers')) ?>">Find pros</a>
      <a class="nav-link <?= current_path_is('/jobs') ? 'is-active' : '' ?>" href="<?= e(url('/jobs')) ?>"><?= is_role('provider') ? 'Find work' : 'Job board' ?></a>
      <?php if ($u): ?>
        <a class="nav-link <?= current_path_is('/dashboard') || current_path_is('/bookings') ? 'is-active' : '' ?>" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
      <?php else: ?>
        <a class="nav-link <?= current_path_is('/how-it-works') ? 'is-active' : '' ?>" href="<?= e(url('/how-it-works')) ?>">How it works</a>
        <a class="nav-link" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Become a pro</a>
      <?php endif; ?>
    </nav>

    <div class="nav-actions">
      <?php if (!$u): ?>
        <button class="nav-icon theme-toggle hide-sm" type="button" data-theme-toggle aria-label="Toggle dark mode">
          <?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?>
        </button>
        <a class="btn btn-ghost hide-sm" href="<?= e(url('/login')) ?>">Sign in</a>
        <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Get started</a>
      <?php else: ?>
        <?php if (is_role('client')): ?>
          <a class="btn btn-primary btn-sm hide-sm" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
        <?php endif; ?>
        <a class="nav-icon" href="<?= e(url('/messages')) ?>" aria-label="Messages<?= $unreadMsgs ? " ($unreadMsgs unread)" : '' ?>">
          <?= icon('message') ?>
          <?php if ($unreadMsgs): ?><span class="count-dot"><?= $unreadMsgs > 9 ? '9+' : $unreadMsgs ?></span><?php endif; ?>
        </a>
        <details class="dropdown">
          <summary class="nav-icon" aria-label="Notifications">
            <?= icon('bell') ?>
            <?php if ($unreadNotifs): ?><span class="count-dot"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span><?php endif; ?>
          </summary>
          <div class="dropdown-menu notif-menu">
            <div class="dropdown-head">
              <strong>Notifications</strong>
              <?php if ($unreadNotifs): ?>
                <form method="post" action="<?= e(url('/notifications/read-all')) ?>"><?= csrf_field() ?><button class="btn-link small" type="submit" data-no-lock>Mark all read</button></form>
              <?php endif; ?>
            </div>
            <?php if (!$latestNotifs): ?>
              <div class="empty" style="padding:28px 16px"><p class="small">You're all caught up.</p></div>
            <?php endif; ?>
            <?php foreach ($latestNotifs as $n): ?>
              <a class="notif-item <?= $n['read_at'] ? '' : 'is-unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>">
                <span class="notif-icon"><?= icon($notifIcon[$n['type']] ?? 'bell') ?></span>
                <span class="grow">
                  <span class="notif-title"><?= e($n['title']) ?></span>
                  <?php if ($n['body']): ?><span class="notif-body" style="display:block"><?= e(str_limit($n['body'], 80)) ?></span><?php endif; ?>
                  <span class="tiny subtle"><?= e(time_ago($n['created_at'])) ?></span>
                </span>
              </a>
            <?php endforeach; ?>
            <a class="notif-foot" href="<?= e(url('/notifications')) ?>">View all notifications</a>
          </div>
        </details>
        <details class="dropdown">
          <summary class="user-chip" aria-label="Account menu">
            <?= avatar($u, 'sm') ?><?= icon('chevron-down') ?>
          </summary>
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
            <a class="dropdown-item" href="<?= e(url('/dashboard')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a class="dropdown-item" href="<?= e(url('/bookings')) ?>"><?= icon('calendar') ?> Bookings</a>
            <?php if (is_role('provider')): ?>
              <a class="dropdown-item" href="<?= e(url('/providers/' . $u['id'])) ?>"><?= icon('user') ?> View public profile</a>
              <a class="dropdown-item" href="<?= e(url('/provider/profile')) ?>"><?= icon('pencil') ?> Edit pro profile</a>
            <?php elseif (is_role('client')): ?>
              <a class="dropdown-item" href="<?= e(url('/my/jobs')) ?>"><?= icon('briefcase') ?> My jobs</a>
              <a class="dropdown-item" href="<?= e(url('/saved')) ?>"><?= icon('heart') ?> Saved pros</a>
            <?php endif; ?>
            <a class="dropdown-item" href="<?= e(url('/settings')) ?>"><?= icon('settings') ?> Settings</a>
            <button class="dropdown-item theme-toggle" type="button" data-theme-toggle>
              <?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?>
              <span class="when-light">Dark mode</span><span class="when-dark">Light mode</span>
            </button>
            <div class="dropdown-sep"></div>
            <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?>
              <button class="dropdown-item" type="submit"><?= icon('log-out') ?> Sign out</button>
            </form>
          </div>
        </details>
      <?php endif; ?>
      <button class="nav-icon nav-toggle" type="button" data-nav-toggle aria-label="Menu" aria-expanded="false"><?= icon('menu') ?></button>
    </div>
  </div>
</header>
