<!doctype html>
<html lang="en" data-brand="<?= e(brand_theme()) ?>">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body>
<?= partial('flashes') ?>
<main id="main"><?= $content ?></main>
</body>
</html>
