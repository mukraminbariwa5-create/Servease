<?php if (user()) app_bar('How it works', '/account'); ?>
<div class="container page how">
  <section class="how-hero">
    <div class="how-hero-copy">
      <h1>Get the job done in three simple steps</h1>
      <p class="lead">Servease connects you with verified local pros. No middlemen and no surprise fees: you agree on the price first and pay your pro directly.</p>
      <div class="row row-wrap mt-2">
        <?php if (is_role('provider')): ?>
          <a class="btn btn-primary btn-lg" href="<?= e(url('/jobs')) ?>"><?= icon('search') ?> Find work</a>
          <a class="btn btn-lg" href="<?= e(url('/provider/profile')) ?>">Edit pro profile</a>
        <?php elseif (is_role('admin')): ?>
          <a class="btn btn-primary btn-lg" href="<?= e(url('/admin')) ?>"><?= icon('shield') ?> Admin panel</a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
          <a class="btn btn-lg" href="<?= e(url('/services')) ?>">Browse services</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="how-hero-art"><?= partial('art', ['kind' => 'services']) ?></div>
  </section>

  <section class="how-section">
    <div class="section-head"><div><h2>Hiring a pro</h2></div></div>
    <ol class="how-steps">
      <?php foreach ([
          ['search', 'Find a pro, or post a job', 'Browse pros by category, city, rating and price, or post a job for free and get quotes, usually within hours.'],
          ['message', 'Compare, chat and book', 'Read reviews from real bookings, message pros with questions, then book a date and time that suits you.'],
          ['calendar-check', 'Track every step', 'Your pro confirms the booking and updates you when work starts and finishes. You get notified at each step.'],
      ] as $i => [$img, $t, $d]): ?>
        <li class="how-step">
          <div class="how-step-img"><?= icon($img) ?><span class="how-step-no"><?= $i + 1 ?></span></div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
    <div class="how-pay card">
      <div class="grow">
        <h3>Pay your pro directly, the way you like</h3>
        <p class="muted">Pay after the job and record it in Servease, so both of you have a receipt. Then leave an honest review.</p>
      </div>
      <div class="how-pay-logos">
        <?php foreach (['gcash', 'maya', 'bank_transfer', 'cash'] as $m): ?>
          <span class="how-pay-item"><?= partial('pay-logo', ['method' => $m]) ?><small><?= e(payment_method_label($m)) ?></small></span>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="how-section" id="pros">
    <div class="promo-band">
      <div class="promo-copy">
        <h2>Grow your business on Servease</h2>
        <ul class="how-pro-list">
          <?php foreach ([
              ['user', 'Create your free profile', 'Add your services and prices, a short bio and photos of past work.'],
              ['shield-check', 'Get verified', 'Upload a government ID or trade license to earn the Verified badge and rank higher.'],
              ['file-text', 'Send quotes on jobs', 'Browse the job board and send your price to customers who need your skills.'],
              ['trending-up', 'Manage bookings and earnings', 'Confirm requests, update job status, confirm payments and grow your rating.'],
          ] as [$ic, $t, $d]): ?>
            <li><span><?= icon($ic) ?></span><div><strong><?= e($t) ?></strong><small><?= e($d) ?></small></div></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn-accent btn-lg" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Start working <?= icon('arrow-right') ?></a>
      </div>
      <div class="promo-art hide-md"><?= partial('art', ['kind' => 'team']) ?></div>
    </div>
  </section>

  <section class="how-section how-faq">
    <div class="section-head"><h2>Common questions</h2></div>
    <div class="faq-list">
      <?php foreach ([
          ['Is Servease free to use?', 'Yes. Posting jobs, sending quotes and booking are free. Servease doesn\'t add any fees on top of the price you agree with your pro.'],
          ['How do payments work?', 'You pay your pro directly (GCash, Maya, bank transfer or cash) and record the payment in the booking. The pro confirms they received it, so there\'s a clear record for both sides.'],
          ['What does "Verified" mean?', 'Our team has checked the pro\'s government-issued ID or trade license. It doesn\'t guarantee workmanship. That\'s what reviews are for.'],
          ['Can I cancel a booking?', 'Yes, you can cancel a booking before work starts. Please give your pro as much notice as possible.'],
      ] as $i => [$q, $a]): ?>
        <details class="faq"<?= $i === 0 ? ' open' : '' ?>>
          <summary><?= e($q) ?><?= icon('chevron-down', 'faq-chev') ?></summary>
          <p><?= e($a) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>
</div>
