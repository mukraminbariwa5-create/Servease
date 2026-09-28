<!doctype html>
<html lang="en" data-brand="<?= e(brand_theme()) ?>">
<head>
<?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body>
<?= partial('flashes') ?>
<div class="auth">
  <aside class="auth-side" aria-label="About Fixoria">
   <div class="auth-side-inner">
    <?= partial('brand') ?>

    <div class="auth-pitch">
      <span class="auth-eyebrow"><?= icon('shield-check', 'icon-sm') ?> ID-verified pros near you</span>
      <h2>Trusted help for every job at home.</h2>
      <ul class="auth-points">
        <li><span><?= icon('search') ?></span><div><strong>Compare real pros</strong><small>Reviews only from completed bookings.</small></div></li>
        <li><span><?= icon('message') ?></span><div><strong>Chat, quote &amp; book</strong><small>Agree on the price before anyone starts.</small></div></li>
        <li><span><?= icon('wallet') ?></span><div><strong>Pay your way</strong><small>Cash, GCash, Maya or bank, all on record.</small></div></li>
      </ul>
    </div>

    <div class="auth-showcase" aria-hidden="true">
      <div class="auth-mini auth-mini-pro">
        <div class="row" style="--gap:12px">
          <span class="avatar avatar-md" style="--h:150">JD</span>
          <div class="grow">
            <div class="row" style="--gap:6px"><strong>Juan Dela Cruz</strong><span class="verified"><?= icon('badge-check', 'icon-sm') ?></span></div>
            <small>Licensed plumber · Quezon City</small>
          </div>
          <span class="auth-rate">★ 4.6</span>
        </div>
        <div class="auth-slot"><?= icon('calendar', 'icon-sm') ?> Next available: Sat, 9:00 AM</div>
      </div>
      <div class="auth-mini auth-mini-done">
        <span class="auth-check"><?= icon('check') ?></span>
        <div><strong>Booking confirmed</strong><small>Kitchen sink repair · ₱1,500</small></div>
      </div>
    </div>

    <div class="auth-foot">
      <div class="auth-faces">
        <span class="avatar avatar-sm" style="--h:320">PU</span>
        <span class="avatar avatar-sm" style="--h:150">JM</span>
        <span class="avatar avatar-sm" style="--h:40">KS</span>
      </div>
      <p>“Posted a job at 9 AM, had three quotes by lunch.” <span>— Patricia U., Makati</span></p>
    </div>
   </div>
  </aside>

  <main class="auth-main" id="main">
    <div class="auth-top">
      <span class="auth-mobile-brand"><?= partial('brand') ?></span>
      <a class="auth-home" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon-sm') ?> Back to home</a>
    </div>
    <div class="auth-card"><?= $content ?></div>
    <p class="auth-legal"><?= icon('lock', 'icon-sm') ?> Secure sign-in · © <?= date('Y') ?> Fixoria</p>
  </main>
</div>
</body>
</html>
