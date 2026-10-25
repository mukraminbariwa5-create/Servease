<?php
/** @var string|null $title */
$pageTitle = isset($title) && $title ? $title . ' · ' . config('app.name') : config('app.name') . ' · Book ID-checked home service pros';
$desc = $description ?? 'Servease connects you with verified local pros for aircon, cleaning, plumbing, electrical work and more. See each pro\'s price and reviews, then book a time that is open.';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php /* The site has its own dark theme: this stops Chrome's Auto Dark Mode, Edge's force-dark and
         Dark Reader from inverting it (which turned the yellow into blue). */ ?>
<meta name="color-scheme" content="light dark">
<meta name="darkreader-lock">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0B1120" media="(prefers-color-scheme: dark)">
<meta name="application-name" content="Servease">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Servease">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection" content="telephone=no">
<meta name="app-base" content="<?= e(base_path()) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(url('/assets/img/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
<link rel="preload" href="<?= e(url('/assets/fonts/archivo-variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script>
  /* Theme: the person's saved choice, otherwise follow the phone or computer setting */
  (function () { var t = null; try { t = localStorage.getItem('servease-theme'); } catch (e) {}
    if (t === 'dark' || (t !== 'light' && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.dataset.theme = 'dark'; })();
  /* Page transitions, app style: going deeper slides in from the right, going back slides in from
     the left, switching tabs cross-fades. Must run before the first paint, so it lives here. */
  (function () {
    var depth = function () { return document.body && document.body.classList.contains('has-appbar') ? 2 : 1; };
    window.addEventListener('pageswap', function () { try { sessionStorage.setItem('se-depth', String(depth())); } catch (e) {} });
    window.addEventListener('pagereveal', function (e) {
      if (!e.viewTransition) return;
      var type = 'fade';
      try {
        var from = +sessionStorage.getItem('se-depth') || 1, to = depth();
        var back = sessionStorage.getItem('se-back') === '1'; sessionStorage.removeItem('se-back');
        var act = window.navigation && navigation.activation;
        if (act && act.navigationType === 'traverse' && act.from && act.entry && act.from.index > act.entry.index) back = true;
        type = back ? 'back' : to > from ? 'forward' : to < from ? 'back' : 'fade';
      } catch (err) {}
      try { e.viewTransition.types.add(type); } catch (err) {}
    });
  })();
</script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/feel.js')) ?>" defer></script>
