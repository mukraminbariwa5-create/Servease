<?php
$active = $active ?? '';
$nav = [
    'dashboard'     => ['/admin', 'dashboard', 'Overview'],
    'verifications' => ['/admin/verifications', 'shield-check', 'Verifications'],
    'users'         => ['/admin/users', 'users', 'Users'],
    'bookings'      => ['/admin/bookings', 'calendar', 'Bookings'],
    'issues'        => ['/admin/issues', 'flag', 'Problems'],
    'payments'      => ['/admin/payments', 'wallet', 'Payments'],
    'jobs'          => ['/admin/jobs', 'briefcase', 'Jobs'],
    'reviews'       => ['/admin/reviews', 'star', 'Reviews'],
    'categories'    => ['/admin/categories', 'grid', 'Categories'],
];
$me = user();
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head', ['title' => (empty($title) || $title === 'Admin' ? 'Overview' : $title) . ' · Admin']) ?>
</head>
<body>
<?= partial('flashes') ?>
<div class="admin">
  <aside class="admin-side" aria-label="Admin navigation">
    <?= partial('brand') ?>
    <span class="sidenav-label">Manage</span>
    <?php foreach ($nav as $key => [$href, $ic, $label]): ?>
      <a class="nav <?= $active === $key ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"><?= icon($ic) ?><span><?= e($label) ?></span>
        <?php if ($key === 'verifications' && !empty($pendingCount)): ?><span class="pill-count"><?= (int) $pendingCount ?></span><?php endif; ?>
        <?php if ($key === 'issues' && !empty($issueCount)): ?><span class="pill-count"><?= (int) $issueCount ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <div class="admin-side-foot" style="margin-top:auto;padding-top:20px">
      <a class="nav" href="<?= e(url('/')) ?>"><?= icon('external-link') ?><span>View site</span></a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <div class="semibold"><?= e($title ?? 'Admin') ?></div>
      <div class="row" style="margin-left:auto;--gap:6px">
        <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?></button>
        <details class="dropdown">
          <summary class="user-chip" aria-label="Account menu"><?= avatar($me, 'sm') ?><?= icon('chevron-down') ?></summary>
          <div class="dropdown-menu">
            <div class="dropdown-head"><div class="semibold"><?= e(full_name($me)) ?></div><div class="small subtle"><?= e($me['email']) ?></div></div>
            <a class="dropdown-item" href="<?= e(url('/')) ?>"><?= icon('home') ?> Back to site</a>
            <a class="dropdown-item" href="<?= e(url('/settings')) ?>"><?= icon('settings') ?> Settings</a>
            <div class="dropdown-sep"></div>
            <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="dropdown-item" type="submit"><?= icon('log-out') ?> Sign out</button></form>
          </div>
        </details>
      </div>
    </header>
    <main class="admin-content" id="main"><?= $content ?></main>
  </div>
</div>
<?= partial('confirm') ?>
</body>
</html>
