<!doctype html>
<html lang="en">
<head>
<?= partial('head', ['title' => $title ?? null, 'description' => $description ?? null]) ?>
</head>
<body class="<?= e(trim(($bodyClass ?? '') . (app_bar() ? ' has-appbar' : ''))) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= partial('navbar') ?>
<?= partial('flashes') ?>
<?php if (page_hero()): ?><?= partial('listing-hero', page_hero()) ?><?php endif; ?>
<main id="main" class="app-main"><?= $content ?></main>
<?= partial('footer') ?>
<?= partial('tabbar') ?>
<?= partial('confirm') ?>
<?= partial('install') ?>
</body>
</html>
