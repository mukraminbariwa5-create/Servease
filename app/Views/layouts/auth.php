<!doctype html>
<html lang="en">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body class="auth-body">
<?= partial('flashes') ?>
<div class="auth">
  <aside class="auth-side" aria-label="About Servease">
    <div class="auth-side-inner">
      <?= partial('brand') ?>
      <?= partial('art', ['kind' => $art ?? 'verified']) ?>
      <div class="auth-pitch">
        <h2>Aircon, plumbing, cleaning and repairs from ID-checked pros in Metro Manila.</h2>
        <ul class="auth-points">
          <li><?= icon('shield-check') ?> Every pro's ID is checked by our team</li>
          <li><?= icon('star') ?> Reviews only from completed bookings</li>
          <li><?= icon('wallet') ?> Pay your pro after the job: GCash, Maya or cash</li>
        </ul>
      </div>
    </div>
  </aside>

  <main class="auth-main" id="main">
    <div class="auth-top">
      <a class="icon-btn" href="<?= e(url($back ?? '/welcome')) ?>" aria-label="Back"><?= icon('arrow-left') ?></a>
      <span class="auth-mobile-brand"><?= partial('brand') ?></span>
      <span class="auth-top-gap"></span>
    </div>
    <div class="auth-card"><?= $content ?></div>
    <?= partial('lang-switch', ['class' => 'lang-switch-center']) ?>
    <p class="auth-legal"><?= icon('lock', 'icon-sm') ?> © <?= date('Y') ?> Servease</p>
  </main>
</div>
</body>
</html>
