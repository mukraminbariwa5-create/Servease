<section class="page-header">
  <div class="container container-md text-center" style="padding-top:20px;padding-bottom:20px">
    <span class="eyebrow">How it works</span>
    <h1 class="mt-2" style="font-size:clamp(2rem,4vw,2.75rem)">Get the job done in three steps</h1>
    <p class="lead mt-3" style="max-width:620px;margin-left:auto;margin-right:auto">Fixoria connects you with verified local pros. No middlemen and no surprise fees: you agree on the price and pay your pro directly.</p>
  </div>
</section>

<section class="section">
  <div class="container container-md">
    <span class="eyebrow">For clients</span>
    <h2 class="mt-2 mb-6">Hiring a pro</h2>
    <div class="stack" style="--gap:16px">
      <?php foreach ([
          ['search', 'Find a pro, or post a job', 'Browse pros by category, city, rating and rate. Or post a job for free and let pros send you quotes, usually within hours.'],
          ['message', 'Compare, chat and book', 'Read reviews from real bookings, message pros with questions, then request a booking with your preferred date and time.'],
          ['calendar', 'Track every step', 'Your pro confirms the booking, marks when work starts and when it\'s done. You\'ll get notified at each step.'],
          ['wallet', 'Pay your pro directly', 'Pay by cash, GCash, Maya or bank transfer and record it in Fixoria, so both of you have a receipt. Online payment through PayMongo can also be switched on.'],
          ['star', 'Leave a review', 'Only clients with a completed booking can review. That keeps ratings honest for everyone.'],
      ] as $i => [$ic, $t, $d]): ?>
        <div class="card card-body row row-top" style="--gap:18px">
          <span class="step-no" style="flex-shrink:0"><?= $i + 1 ?></span>
          <div><h3 class="row" style="--gap:8px"><?= icon($ic, 'icon-sm') ?> <?= e($t) ?></h3><p class="muted mt-1"><?= e($d) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="row mt-6"><a class="btn btn-primary btn-lg" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a><a class="btn btn-lg" href="<?= e(url('/providers')) ?>">Browse pros</a></div>
  </div>
</section>

<section class="section" id="pros" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container container-md">
    <span class="eyebrow">For pros</span>
    <h2 class="mt-2 mb-6">Growing your business on Fixoria</h2>
    <div class="grid grid-2">
      <?php foreach ([
          ['user', 'Create your free profile', 'Add your services and prices, a short bio and photos of past work.'],
          ['shield-check', 'Get verified', 'Upload a government ID or trade license. Once approved, you get the Verified badge and rank higher in search.'],
          ['file-text', 'Send quotes on jobs', 'Browse the job board and send a price and message to clients who need your skills.'],
          ['trending-up', 'Manage bookings & earnings', 'Confirm requests, update job status, confirm payments and watch your ratings grow.'],
      ] as [$ic, $t, $d]): ?>
        <div class="card card-body">
          <span class="stat-icon"><?= icon($ic) ?></span>
          <h3 class="mt-4"><?= e($t) ?></h3>
          <p class="muted mt-1"><?= e($d) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-dark btn-lg mt-6" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Become a pro <?= icon('arrow-right') ?></a>
  </div>
</section>

<section class="section">
  <div class="container container-md">
    <h2 class="mb-6">Common questions</h2>
    <div class="stack" style="--gap:12px">
      <?php foreach ([
          ['Is Fixoria free to use?', 'Yes. Posting jobs, sending quotes and booking are free. Fixoria doesn\'t add any fees on top of the price you agree with your pro.'],
          ['How do payments work?', 'You pay your pro directly (cash, GCash, Maya or bank transfer) and record the payment in the booking. The pro confirms they received it, so there\'s a clear record for both sides.'],
          ['What does "Verified" mean?', 'Our team has checked the pro\'s government-issued ID or trade license. It doesn\'t guarantee workmanship. That\'s what reviews are for.'],
          ['Can I cancel a booking?', 'Yes, you can cancel a booking before work starts. Please give your pro as much notice as possible.'],
      ] as [$q, $a]): ?>
        <details class="card card-body"><summary class="semibold" style="cursor:pointer"><?= e($q) ?></summary><p class="muted mt-3"><?= e($a) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
