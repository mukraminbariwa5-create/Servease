<?php
$u = user();
$isPro = is_role('provider');
$isClientOrGuest = !$u || is_role('client');
?>
<div class="container home">

  <!-- 1. Banner carousel (first slide: greeting + search) beside the next booking -->
  <section class="home-hero">
    <!-- Banner: greeting, the main search and what makes Servease safe to use -->
    <section class="home-banner" aria-label="Search for a service">
      <div class="banner-copy">
        <h1><?= $u ? 'What needs fixing, ' . e($u['first_name']) . '?' : 'What needs fixing at home?' ?></h1>
        <form class="banner-search" action="<?= e(url('/services')) ?>" method="get" role="search">
          <label class="banner-field">
            <?= icon('search') ?>
            <span class="sr-only">What do you need done?</span>
            <input name="q" type="search" placeholder="Leaking sink, aircon cleaning, math tutor" autocomplete="off">
          </label>
          <button class="btn btn-accent banner-go" type="submit"><span class="hide-sm">Search</span><span class="show-sm" aria-hidden="true"><?= icon('search') ?></span><span class="sr-only show-sm">Search</span></button>
        </form>
        <?php if ($quick): ?>
          <div class="banner-quick" aria-label="Popular in <?= e($city) ?>">
            <?php foreach ($quick as $qk): ?>
              <a href="<?= e(url('/services', ['q' => $qk['title']])) ?>"><?= e($qk['title']) ?> <span>from <?= money($qk['from_price']) ?></span></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($cityPros): ?>
          <p class="banner-note"><?= icon('badge-check', 'icon-sm') ?><span><?= $cityPros ?> verified pro<?= $cityPros === 1 ? '' : 's' ?> near you<?= $freeTomorrow ? ' · ' . $freeTomorrow . ' available tomorrow' : '' ?> · Pay after the job</span></p>
        <?php endif; ?>
      </div>
      <?php if ($deal): ?>
        <a class="banner-tag" href="<?= e(url('/services', ['category' => $deal['slug']] + ($dealInCity ? ['city' => $city] : []))) ?>">
          <span class="banner-tag-what"><?= e(preg_replace('/ &.*$/', '', $deal['name'])) ?><?= $dealInCity ? ' in ' . e($city) : '' ?></span>
          <span class="banner-tag-price"><small>from</small><?= money($deal['from_price']) ?></span>
          <span class="banner-tag-sub"><?= (int) $deal['n'] ?> services to compare</span>
        </a>
      <?php endif; ?>
    </section>

    <?php if ($next || ($u && $isPro)): ?>
    <!-- 3 (phone) / side card (computer): what's happening for you -->
    <aside class="side-card">
      <?php if ($next): ?>
        <div class="side-head">
          <span class="side-title"><?= $isPro ? 'Your next job' : 'Your next booking' ?></span>
          <?= badge($next['status']) ?>
        </div>
        <div class="side-svc">
          <span class="svc-ic" style="--tint:<?= category_tint($next['category_slug'] ?? null) ?>"><?= category_art($next['category_slug'] ?? null, $next['category_icon'] ?: 'wrench') ?></span>
          <span class="grow">
            <strong class="truncate"><?= e($next['title']) ?></strong>
            <small class="side-who"><?= avatar(['id' => $next['other_id'], 'first_name' => $next['first_name'], 'last_name' => $next['last_name'], 'avatar' => $next['avatar']], 'xs') ?><?= $isPro ? 'for' : 'with' ?> <?= e($next['first_name'] . ' ' . mb_substr($next['last_name'], 0, 1) . '.') ?></small>
          </span>
        </div>
        <ul class="side-facts">
          <li><?= icon('calendar') ?><?= $next['scheduled_date'] ? e(fmt_date($next['scheduled_date'], 'D, M j')) . ($next['scheduled_time'] ? ' · ' . e(date('g:i A', strtotime($next['scheduled_time']))) : '') : 'Date to be agreed' ?></li>
          <li><?= icon('map-pin') ?><?= e($next['city'] ?: '—') ?></li>
          <li><?= icon('wallet') ?><?= money($next['amount']) ?></li>
        </ul>
        <?php $stepAt = ['requested' => 0, 'accepted' => 1, 'in_progress' => 2][$next['status']] ?? 0; ?>
        <ol class="side-track" aria-label="Booking progress">
          <?php foreach (['Requested', 'Confirmed', 'In progress', 'Completed'] as $si => $sl): ?>
            <li class="<?= $si < $stepAt ? 'is-done' : ($si === $stepAt ? 'is-now' : '') ?>"<?= $si === $stepAt ? ' aria-current="step"' : '' ?>><span class="side-track-dot"><?= $si < $stepAt ? icon('check') : '' ?></span><span class="side-track-lbl"><?= $sl ?></span></li>
          <?php endforeach; ?>
        </ol>
        <div class="side-actions">
          <a class="btn btn-dark grow" href="<?= e(url('/bookings/' . $next['id'])) ?>">View booking</a>
          <a class="icon-btn icon-btn-outline" href="<?= e(url('/messages')) ?>" aria-label="Open inbox"><?= icon('message') ?></a>
        </div>
      <?php else: ?>
        <div class="side-head"><span class="side-title">Your business</span></div>
        <div class="side-big"><strong><?= (int) $pendingRequests ?></strong><span>new request<?= $pendingRequests === 1 ? '' : 's' ?> waiting</span></div>
        <p class="small muted">Pros who reply within an hour win more bookings.</p>
        <div class="side-actions"><a class="btn btn-dark grow" href="<?= e(url('/bookings')) ?>">Open bookings</a></div>
      <?php endif; ?>
    </aside>
    <?php elseif (!$u): ?>
    <aside class="side-card hide-md">
      <div class="side-head"><span class="side-title">How Servease works</span></div>
      <ol class="side-steps">
        <li><span>1</span><div><strong>Pick a service</strong><small>See the price and the pro before you book</small></div></li>
        <li><span>2</span><div><strong>Book a time that's open</strong><small>Your pro confirms the price first</small></div></li>
        <li><span>3</span><div><strong>Pay after the job</strong><small>Directly to your pro: GCash, Maya or cash</small></div></li>
      </ol>
      <a class="btn-link small mt-auto" href="<?= e(url('/how-it-works')) ?>">Learn more <?= icon('arrow-right', 'icon-sm') ?></a>
    </aside>
    <?php endif; ?>
  </section>

  <?php if ($reminders): ?>
  <!-- Customer to-dos: pay for finished jobs, then review them -->
  <section class="reminders" aria-label="Needs your attention">
    <?php foreach ($reminders as [$kind, $href, $head, $text]): ?>
      <a class="reminder reminder-<?= $kind ?>" href="<?= e(url($href)) ?>">
        <span class="reminder-ic"><?= icon(['pay' => 'wallet', 'overdue' => 'calendar-check'][$kind] ?? 'star') ?></span>
        <span class="grow"><strong><?= e($head) ?></strong><small><?= e($text) ?></small></span>
        <?= icon('chevron-right', 'reminder-go') ?>
      </a>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <!-- Get the app (phones that can install it; hidden once installed or dismissed) -->
  <div class="install-card show-md" data-install-card hidden>
    <span class="install-app-icon"><img src="<?= e(url('/assets/img/icon-192.png')) ?>" alt="" width="44" height="44"></span>
    <span class="grow"><strong>Get the app</strong><small>Full screen, one tap from your home screen</small></span>
    <button class="btn btn-primary btn-sm" type="button" data-install>Install</button>
    <button class="icon-btn install-close" type="button" data-install-dismiss aria-label="Not now"><?= icon('x') ?></button>
  </div>

  <!-- 2. Categories -->
  <section class="home-section">
    <div class="section-head">
      <h2>Categories</h2>
      <a class="more" href="<?= e(url('/categories')) ?>">View all</a>
    </div>
    <div class="cat-row">
      <?php foreach (array_slice($categories, 0, 8) as $i => $c): ?>
        <a class="cat-item" href="<?= e(url('/services', ['category' => $c['slug']])) ?>" style="--i:<?= $i ?>">
          <span class="cat-icon" style="--tint:<?= category_tint($c['slug']) ?>"><?= category_art($c['slug'], $c['icon']) ?></span>
          <span class="cat-name"><?= e(preg_replace('/ &.*$/', '', $c['name'])) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($isPro && $jobs): ?>
  <section class="home-section">
    <div class="section-head">
      <h2>New jobs from customers</h2>
      <a class="more" href="<?= e(url('/jobs')) ?>">Job board</a>
    </div>
    <div class="grid grid-2">
      <?php foreach ($jobs as $j): ?><?= View::partial('jobs/_card', ['j' => $j]) ?><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($popular): ?>
  <!-- Near you: services in the chosen city -->
  <section class="home-section">
    <div class="section-head">
      <div>
        <h2><?= $popularInCity ? 'Near you' : 'Popular services' ?></h2>
        <p class="muted small"><?= $popularInCity ? 'Services in ' . e($city) : 'The most booked services, nearest first' ?></p>
      </div>
      <a class="more" href="<?= e(url('/services', $popularInCity ? ['city' => $city] : [])) ?>">View all</a>
    </div>
    <div class="near-row">
      <?php foreach (array_slice($popular, 0, 8) as $s): ?><?= partial('near-card', ['s' => $s]) ?><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Top-rated pros -->
  <section class="home-section">
    <div class="section-head">
      <div>
        <h2>Top-rated pros</h2>
        <p class="muted small">ID-checked, nearest to <?= e($city) ?> first</p>
      </div>
      <a class="more" href="<?= e(url('/providers', ['sort' => 'top'])) ?>">See all</a>
    </div>
    <div class="pro-tiles">
      <?php foreach (array_slice($featured, 0, 4) as $p): ?><?= partial('pro-row', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </section>


  <?php if ($reviews): ?>
  <section class="home-section hide-md">
    <div class="section-head"><h2>Recent reviews</h2></div>
    <div class="review-scroller">
      <?php foreach ($reviews as $r): ?>
        <figure class="quote-card">
          <?= stars(5) ?>
          <blockquote><?= e($r['comment']) ?></blockquote>
          <figcaption>
            <?= avatar(['id' => $r['client_uid'], 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'avatar' => $r['avatar']], 'sm') ?>
            <span><strong><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></strong><small>Hired <?= e($r['p_first']) ?> for <?= e(mb_strtolower(preg_replace('/ &.*$/', '', (string) $r['category_name']))) ?></small></span>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!$u): ?>
  <section class="home-section hide-md">
    <div class="promo-band">
      <div class="promo-copy">
        <span class="eyebrow">For service pros</span>
        <h2>Grow your business with Servease</h2>
        <p>Get found by customers in your city, send quotes on open jobs and run every booking from one dashboard.</p>
        <ul class="check-list">
          <li><?= icon('check-circle') ?><span>Free profile with your services, prices and work photos</span></li>
          <li><?= icon('check-circle') ?><span>Verified badge that wins more customers</span></li>
          <li><?= icon('check-circle') ?><span>Set your own working hours</span></li>
        </ul>
        <a class="btn btn-accent btn-lg" href="<?= e(url('/register', ['role' => 'provider'])) ?>">Join as a pro <?= icon('arrow-right') ?></a>
      </div>
      <div class="promo-art"><?= partial('art', ['kind' => 'team']) ?></div>
    </div>
  </section>
  <?php endif; ?>
</div>
