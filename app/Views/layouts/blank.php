<!doctype html>
<html lang="en">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body class="<?= e($bodyClass ?? 'blank-body') ?>">
<?= partial('flashes') ?>
<main id="main"><?= $content ?></main>
</body>
</html>
