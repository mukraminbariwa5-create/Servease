<!doctype html>
<html lang="en" data-brand="<?= e(brand_theme()) ?>">
<head>
<?= partial('head', ['title' => $title ?? null, 'description' => $description ?? null]) ?>
</head>
<body>
<a class="sr-only" href="#main">Skip to content</a>
<?= partial('navbar', ['navDark' => $navDark ?? false]) ?>
<?= partial('flashes') ?>
<main id="main"><?= $content ?></main>
<?= partial('footer') ?>
<?= partial('confirm') ?>
</body>
</html>
