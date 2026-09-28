<?php
/** Signed-in area: top navbar + side navigation. */
$u = user();
$active = $active ?? '';
$openBookings = (int) DB::value(
    is_role('provider')
        ? "SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'requested'"
        : "SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status IN ('accepted','in_progress')",
    [(int) $u['id']]
);
$link = function (string $key, string $href, string $icon, string $label, int $count = 0) use ($active) {
    return '<a href="' . e(url($href)) . '" class="' . ($active === $key ? 'is-active' : '') . '">'
        . icon($icon) . '<span>' . e($label) . '</span>'
        . ($count ? '<span class="pill-count">' . $count . '</span>' : '') . '</a>';
};
?>
<!doctype html>
<html lang="en" data-brand="<?= e(brand_theme()) ?>">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body>
<a class="sr-only" href="#main">Skip to content</a>
<?= partial('navbar') ?>
<?= partial('flashes') ?>
<div class="container shell">
  <nav class="sidenav" aria-label="Account">
    <span class="sidenav-label">Overview</span>
    <?= $link('dashboard', '/dashboard', 'dashboard', 'Dashboard') ?>
    <?= $link('bookings', '/bookings', 'calendar', 'Bookings', $openBookings) ?>
    <?= $link('messages', '/messages', 'message', 'Messages') ?>
    <?= $link('notifications', '/notifications', 'bell', 'Notifications') ?>
    <?php if (is_role('client')): ?>
      <span class="sidenav-label">Hiring</span>
      <?= $link('jobs', '/my/jobs', 'briefcase', 'My jobs') ?>
      <?= $link('saved', '/saved', 'heart', 'Saved pros') ?>
    <?php elseif (is_role('provider')): ?>
      <span class="sidenav-label">Your business</span>
      <?= $link('quotes', '/my/quotes', 'file-text', 'My quotes') ?>
      <?= $link('profile', '/provider/profile', 'user', 'Pro profile') ?>
      <?= $link('findwork', '/jobs', 'search', 'Find work') ?>
    <?php elseif (is_role('admin')): ?>
      <span class="sidenav-label">Admin</span>
      <?= $link('admin', '/admin', 'shield', 'Admin panel') ?>
    <?php endif; ?>
    <span class="sidenav-label">Account</span>
    <?= $link('settings', '/settings', 'settings', 'Settings') ?>
  </nav>
  <main id="main" class="grow"><?= $content ?></main>
</div>
<?= partial('confirm') ?>
</body>
</html>
