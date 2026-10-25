<?php
/**
 * First visit: a short splash, three intro slides (Next / Skip), then the start screen
 * with Log in and Create account. Swipe works on phones; Next/Skip scroll the track.
 */
$slides = [
    ['services', 'Aircon, plumbing,<br>cleaning, tutoring', 'Book local pros for the jobs around the house, from a leaking sink to a deep clean.'],
    ['booking', 'See the price<br>before you book', 'Pick an open time. Your pro confirms the price, and you pay after the job by GCash, Maya or cash.'],
    ['verified', 'Every pro<br>is ID-checked', 'Reviews come only from finished bookings, so you know who you\'re letting in.'],
];
?>
<div class="splash" aria-hidden="true">
  <span class="ring ring-a"></span><span class="ring ring-b"></span>
  <?= partial('logo', ['class' => 'splash-logo']) ?>
  <span class="splash-name">Servease</span>
  <span class="splash-tag">Home services</span>
</div>

<div class="onboard" data-onboard>
  <div class="onboard-track" data-ob-track>
    <?php foreach ($slides as $i => [$art, $h, $p]): ?>
      <section class="onboard-slide" id="ob-<?= $i + 1 ?>" aria-label="Intro <?= $i + 1 ?> of 3">
        <span class="ring ring-a" aria-hidden="true"></span>
        <div class="onboard-art"><?= partial('art', ['kind' => $art]) ?></div>
        <div class="onboard-copy">
          <h1><?= $h ?></h1>
          <p><?= e($p) ?></p>
          <span class="onboard-dots" aria-hidden="true">
            <?php for ($d = 0; $d < 3; $d++): ?><i class="<?= $d === $i ? 'is-on' : '' ?>"></i><?php endfor; ?>
          </span>
        </div>
        <div class="onboard-actions">
          <?php if ($i < 2): ?>
            <a class="btn btn-primary btn-lg btn-block onboard-next" href="#ob-<?= $i + 2 ?>" data-ob-next>Next <?= icon('arrow-right') ?></a>
            <a class="btn btn-lg btn-block onboard-skip" href="#ob-start" data-ob-skip>Skip</a>
          <?php else: ?>
            <a class="btn btn-dark btn-lg btn-block onboard-next" href="#ob-start" data-ob-next>Get started</a>
            <p class="onboard-login">Already have an account? <a href="<?= e(url('/login')) ?>">Log in</a></p>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>

    <section class="onboard-slide onboard-start" id="ob-start" aria-label="Log in or create an account">
      <span class="ring ring-a" aria-hidden="true"></span>
      <div class="start-brand"><?= partial('logo') ?><span>Servease</span></div>
      <div class="onboard-art"><?= partial('art', ['kind' => 'welcome']) ?></div>
      <div class="onboard-copy">
        <h1>Home repairs,<br>booked properly</h1>
        <p>Already have an account? Log in. New here? It takes a minute.</p>
      </div>
      <div class="onboard-actions">
        <a class="btn btn-primary btn-lg btn-block" href="<?= e(url('/login')) ?>"><?= icon('log-in') ?> Log in</a>
        <a class="btn btn-dark btn-lg btn-block" href="<?= e(url('/register')) ?>"><?= icon('user-plus') ?> Create account</a>
        <p class="start-note"><?= icon('shield-check', 'icon-sm') ?> Free to join, and no booking fees.</p>
        <?= partial('lang-switch', ['class' => 'lang-switch-center']) ?>
      </div>
    </section>
  </div>
</div>
