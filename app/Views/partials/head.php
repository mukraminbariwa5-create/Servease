<?php
/** @var string|null $title */
$pageTitle = isset($title) && $title ? $title . ' · ' . config('app.name') : config('app.name') . ' — Hire trusted local pros';
$desc = $description ?? 'Fixoria connects you with verified local service providers — plumbers, electricians, cleaners, tutors and more. Compare reviews, get quotes and book in minutes.';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="<?= ['blue' => '#1d5fd6', 'green' => '#0f7a55', 'navy' => '#16233f'][brand_theme()] ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php $headFont = ['blue' => 'Plus+Jakarta+Sans:wght@600;700;800', 'green' => 'Outfit:wght@500;600;700;800', 'navy' => 'Archivo:wght@600;700;800'][brand_theme()]; ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=<?= $headFont ?>&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script>
  (function () { try { var t = localStorage.getItem('fixoria-theme'); if (t) document.documentElement.dataset.theme = t; } catch (e) {} })();
</script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
