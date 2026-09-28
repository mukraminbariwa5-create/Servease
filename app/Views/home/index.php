<?php
$top = $featured[0] ?? null;
$heroImage = is_file(ROOT . '/assets/img/hero.jpg') ? asset('img/hero.jpg') : null;
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="hero-badge"><?= icon('shield-check') ?> ID-verified pros in your neighborhood</span>
      <h1>Trusted help for every job at home, <span class="hl">right nearby.</span></h1>
      <p class="lead">Book a plumber, cleaner, electrician or tutor near you. Compare real reviews and upfront rates, then book a time that suits you.</p>

      <form class="search-bar" action="<?= e(url('/providers')) ?>" method="get" role="search">
        <label class="seg">
          <?= icon('search') ?>
          <span class="sr-only">What needs doing?</span>
          <input name="q" placeholder="What needs doing?" autocomplete="off">
        </label>
        <label class="seg">
          <?= icon('map-pin') ?>
          <span class="sr-only">Your city</span>
          <select name="city">
            <option value="">Your city</option>
            <?php foreach (cities() as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </label>
        <button class="btn btn-primary" type="submit">Find a pro</button>
      </form>
      <p class="hero-alt">Not sure who to hire? <a href="<?= e(url('/jobs/new')) ?>">Post a job and get free quotes →</a></p>

      <div class="trust-row">
        <span><?= icon('shield-check') ?> Verified IDs</span>
        <span><?= icon('star') ?> Reviews from real bookings</span>
        <span><?= icon('banknote') ?> Pay cash, GCash or Maya</span>
      </div>
    </div>

    <div class="hero-visual" aria-hidden="true">
      <div class="hero-photo">
        <?php if ($heroImage): ?>
          <img src="<?= e($heroImage) ?>" alt="">
        <?php else: ?>
          <div class="pattern">
            <?php foreach (['wrench', 'droplet', 'zap', 'hammer', 'paint-roller', 'sparkles', 'snowflake', 'truck', 'monitor', 'leaf', 'book-open', 'scissors', 'home', 'wrench', 'droplet', 'zap'] as $i): ?><?= icon($i) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($top): ?>
        <div class="float-card fc-pro">
          <div class="fc-row">
            <?= avatar($top, 'lg') ?>
            <div class="grow" style="min-width:0">
              <div class="pcard-name" style="font-size:1.1875rem"><span class="truncate"><?= e(full_name($top)) ?></span><span class="verified"><?= icon('shield-check') ?></span></div>
              <div class="small muted truncate"><?= e($top['category_name']) ?> · <?= e($top['city']) ?></div>
            </div>
          </div>
          <div class="fc-stats">
            <span class="rating"><?= icon('star') ?><?= number_format((float) $top['rating_avg'], 1) ?> (<?= (int) $top['rating_count'] ?>)</span>
            <span><?= (int) $top['jobs_completed'] ?> jobs done</span>
            <span><?= (int) $top['years_experience'] ?> yrs exp.</span>
          </div>
          <div class="fc-avail"><?= icon('calendar') ?> <?= $top['is_available'] ? 'Taking new bookings this week' : 'Fully booked — join the waitlist' ?></div>
          <div class="row mt-3" style="--gap:10px">
            <span class="btn btn-primary grow">Request booking</span>
            <span class="btn">Message</span>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($latestQuote): ?>
        <div class="float-card fc-quote">
          <div class="k">New quote received</div>
          <div class="semibold mt-1 truncate"><?= e($latestQuote['title']) ?></div>
          <div class="row row-between small mt-1"><span class="sub">from <?= e($latestQuote['first_name'] . ' ' . mb_substr($latestQuote['last_name'], 0, 1)) ?>.</span><span class="bold"><?= money($latestQuote['amount']) ?></span></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="container" style="padding-bottom:8px">
  <div class="steps-band">
    <div class="step-mini"><span class="step-no">1</span><div><h3>Tell us what needs doing</h3><p>Search for a pro, or post a job for free.</p></div></div>
    <div class="step-mini"><span class="step-no">2</span><div><h3>Compare quotes &amp; chat</h3><p>See reviews and rates, ask questions first.</p></div></div>
    <div class="step-mini"><span class="step-no">3</span><div><h3>Book, pay &amp; review</h3><p>Pay your pro directly, then leave a review.</p></div></div>
  </div>
</section>

<section class="section-sm" style="padding-top:48px">
  <div class="container">
    <div class="section-head">
      <h2>What needs doing?</h2>
      <a class="more" href="<?= e(url('/providers')) ?>">All services <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a class="cat-tile" href="<?= e(url('/providers', ['category' => $c['slug']])) ?>">
          <span class="cat-icon"><?= icon($c['icon']) ?></span>
          <span>
            <span class="cat-name" style="display:block"><?= e($c['name']) ?></span>
            <span class="cat-count"><?= (int) $c['providers'] ?> pro<?= (int) $c['providers'] === 1 ? '' : 's' ?> available</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Top-rated pros near you</h2>
        <p class="muted">Verified identities, upfront rates and reviews from completed bookings only.</p>
      </div>
      <a class="more" href="<?= e(url('/providers', ['sort' => 'top'])) ?>">See all pros <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <div class="grid grid-3">
      <?php foreach (array_slice($featured, 0, 6) as $p): ?><?= partial('provider-card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($jobs): ?>
<section class="section-sm">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Fresh jobs from clients</h2>
        <p class="muted">Are you a pro? Send a quote and win new customers.</p>
      </div>
      <a class="more" href="<?= e(url('/jobs')) ?>">Browse all jobs <?= icon('arrow-right', 'icon-sm') ?></a>
    </div>
    <div class="grid grid-2">
      <?php foreach ($jobs as $j): ?><?= View::partial('jobs/_card', ['j' => $j]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-sm">
  <div class="container">
    <div class="split-band">
      <div>
        <span class="eyebrow">For clients</span>
        <h2 class="mt-2">Hire with confidence</h2>
        <ul class="check-list">
          <li><?= icon('check-circle') ?><span><strong>Verified pros.</strong> <span class="muted">IDs and credentials are checked by our team.</span></span></li>
          <li><?= icon('check-circle') ?><span><strong>Honest reviews.</strong> <span class="muted">Only clients with a completed booking can leave one.</span></span></li>
          <li><?= icon('check-circle') ?><span><strong>Everything in one place.</strong> <span class="muted">Quotes, chat, schedule and payment records.</span></span></li>
        </ul>
        <a class="btn btn-primary mt-6" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job, it's free</a>
      </div>
      <div class="dark-side" id="pros">
        <span class="eyebrow">For pros</span>
        <h2 class="mt-2">Grow your business</h2>
        <p class="muted mt-2">Get discovered by clients near you, send quotes on open jobs and manage every booking from one dashboard.</p>
        <ul class="check-list">
          <li><?= icon('check-circle') ?><span>Free profile with services, rates &amp; portfolio</span></li>
          <li><?= icon('check-circle') ?><span>Verified badge that wins more clients</span></li>
          <li><?= icon('check-circle') ?><span>Track bookings, earnings and reviews</span></li>
        </ul>
        <a class="btn mt-6" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Become a pro <?= icon('arrow-right') ?></a>
      </div>
    </div>
  </div>
</section>

<?php if ($reviews): ?>
<section class="section-sm">
  <div class="container">
    <div class="section-head"><h2>What clients say</h2></div>
    <div class="grid grid-3">
      <?php foreach ($reviews as $r): ?>
        <figure class="card testimonial" style="margin:0">
          <?= stars(5) ?>
          <p><?= e($r['comment']) ?></p>
          <figcaption class="row" style="--gap:12px">
            <?= avatar(['id' => $r['client_uid'], 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'avatar' => $r['avatar']], 'md') ?>
            <div class="grow">
              <div class="semibold"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></div>
              <div class="small subtle">Hired <?= e($r['p_first']) ?> · <?= e($r['category_name']) ?></div>
            </div>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-sm" style="padding-bottom:72px">
  <div class="container">
    <div class="cta">
      <h2>Got something that needs fixing?</h2>
      <p>Post your job in two minutes and start receiving quotes from verified pros near you.</p>
      <div class="row" style="justify-content:center;flex-wrap:wrap">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/jobs/new')) ?>">Post a job</a>
        <a class="btn btn-lg btn-outline-light" href="<?= e(url('/providers')) ?>">Browse pros</a>
      </div>
    </div>
  </div>
</section>
