<?php
/**
 * Signed-in pages. Navigation lives in one place per screen size: the top bar and
 * account menu on computers, the tab bar and Profile tab on phones.
 * Reading and form pages use a comfortable reading width; dashboards and inboxes use the full width.
 */
$active = $active ?? '';
$wide = !empty($wide) || in_array($active, ['dashboard', 'messages', 'saved', 'quotes'], true) || ($active === 'bookings' && ($title ?? '') === 'Bookings');
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body class="in-shell<?= app_bar() ? ' has-appbar' : '' ?><?= !empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= partial('navbar') ?>
<?= partial('flashes') ?>
<?php if (page_hero()): ?><?= partial('listing-hero', page_hero()) ?><?php endif; ?>
<div class="container shell<?= $wide ? ' shell-wide' : '' ?><?= page_hero() ? ' has-hero' : '' ?>">
  <main id="main" class="shell-main"><?= $content ?></main>
</div>
<?= partial('tabbar') ?>
<?= partial('confirm') ?>
<?= partial('install') ?>
</body>
</html>
