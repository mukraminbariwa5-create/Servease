<?php app_bar('Pro profile', '/services'); ?>
<?php
$me = user();
$isOwner = $me && (int) $me['id'] === (int) $p['id'];
$canHire = !$me || $me['role'] === 'client';
$totalReviews = array_sum($dist);
$memberSince = fmt_date($p['created_at'], 'M Y');
?>
<div class="container">
  <nav class="breadcrumbs profile-crumbs hide-md" aria-label="Breadcrumb">
    <a href="<?= e(url('/services')) ?>">Services</a><?= icon('chevron-right') ?>
    <?php if (!empty($p['category_slug'])): ?><a href="<?= e(url('/providers', ['category' => $p['category_slug']])) ?>"><?= e($p['category_name']) ?> pros</a><?= icon('chevron-right') ?><?php endif; ?>
    <span aria-current="page"><?= e(full_name($p)) ?></span>
  </nav>
  <?php
  $prices = array_map(fn ($s) => (float) $s['price'], $services);
  $fromPrice = $prices ? min($prices) : null;
  $slug = $p['category_slug'] ?? '';
  ?>
  <article class="biz" aria-label="<?= e(full_name($p)) ?>, <?= e($p['category_name'] ?? 'General') ?>">
    <div class="biz-band" style="--tint:<?= category_tint($slug) ?>">
      <span class="biz-trade"><?= category_art($slug, $p['category_icon'] ?? 'wrench', 'cat-art', 28) ?></span>
      <span class="biz-trade-name"><?= e($p['category_name'] ?? 'General') ?></span>
    </div>
    <div class="biz-body">
      <div class="biz-id">
        <?= avatar($p, 'xl') ?>
        <div class="grow">
          <h1 class="profile-name"><?= e(full_name($p)) ?><?php if ($p['verification_status'] === 'verified'): ?><span class="verified" title="Verified pro"><?= icon('badge-check', 'icon-lg') ?></span><?php endif; ?></h1>
          <p class="biz-headline"><?= e($p['headline']) ?></p>
        </div>
      </div>
      <dl class="biz-facts">
        <div><dt>Rating</dt><dd><?php if ($p['rating_count'] > 0): ?><span class="rating"><?= icon('star') ?><?= number_format((float) $p['rating_avg'], 1) ?></span> <small><?= (int) $p['rating_count'] ?> reviews</small><?php else: ?>New<?php endif; ?></dd></div>
        <div><dt>Jobs done</dt><dd><?= (int) $p['jobs_completed'] ?></dd></div>
        <div><dt>Experience</dt><dd><?= (int) $p['years_experience'] ?> yrs</dd></div>
        <div><dt>Based in</dt><dd><?= e(city_with_distance($p['city'])) ?></dd></div>
      </dl>
      <div class="biz-foot">
        <?php if ($fromPrice !== null): ?><span class="price-tag">from <?= money($fromPrice) ?></span><?php endif; ?>
        <span class="biz-since">Member since <?= e($memberSince) ?></span>
      </div>
    </div>
    <?php if ($p['verification_status'] === 'verified'): ?><span class="stamp stamp-green biz-stamp">ID checked</span><?php endif; ?>
  </article>

  <div class="profile-actions biz-actions">
    <?php if ($isOwner): ?>
      <a class="btn btn-primary" href="<?= e(url('/provider/profile')) ?>"><?= icon('pencil') ?> Edit profile</a>
    <?php else: ?>
      <?php if ($me && !$isOwner): ?>
        <form method="post" action="<?= e(url('/messages/start')) ?>">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
          <button class="btn" type="submit"><?= icon('message') ?> Message</button>
        </form>
      <?php else: ?>
        <a class="btn" href="<?= e(url('/login')) ?>"><?= icon('message') ?> Message</a>
      <?php endif; ?>
      <?php if ($canHire): ?>
        <form method="post" action="<?= e(url('/providers/' . $p['id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
          <?= csrf_field() ?>
          <button class="save-btn <?= $isSaved ? 'is-saved' : '' ?>" type="submit" aria-label="Save" aria-pressed="<?= $isSaved ? 'true' : 'false' ?>" data-no-lock><?= icon('heart') ?></button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="layout-aside page">
    <div class="stack profile-main" style="--gap:24px">
      <?php if ($p['verification_status'] !== 'verified'): ?>
        <div class="alert alert-warning"><?= icon('info') ?><div>This pro hasn't completed ID verification yet. <?= $p['verification_status'] === 'pending' ? 'Their documents are being reviewed.' : '' ?></div></div>
      <?php endif; ?>

      <section class="card card-body">
        <h3 class="mb-3">About</h3>
        <p class="muted" style="white-space:pre-line"><?= e($p['bio'] ?: 'This pro has not written a bio yet.') ?></p>
        <?php if ($p['skills']): ?>
          <div class="row row-wrap mt-4" style="--gap:8px">
            <?php foreach (array_filter(array_map('trim', explode(',', $p['skills']))) as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>
        <dl class="kv mt-6">
          <dt>Service area</dt><dd><?= e($p['service_area'] ?: $p['city']) ?></dd>
          <dt>Working hours</dt><dd><?= e(Availability::summary($p)) ?></dd>
          <dt>Member since</dt><dd><?= e($memberSince) ?></dd>
          <dt>Availability</dt><dd><?= $p['is_available'] ? '<span class="badge badge-green">Taking new jobs</span>' : '<span class="badge">Fully booked</span>' ?></dd>
        </dl>
      </section>

      <section class="card card-body" id="services">
        <div class="head-row mb-2"><h3>Services &amp; rates</h3><span class="small subtle">Final price is agreed before booking</span></div>
        <?php if (!$services): ?><p class="muted mt-2">No fixed-price services listed. Message this pro for a quote.</p><?php endif; ?>
        <?php $picked = (int) ($_GET['service'] ?? 0); foreach ($services as $s): $isPicked = $picked === (int) $s['id']; ?>
          <div class="service-row<?= $isPicked ? ' is-picked' : '' ?>"<?= $isPicked ? ' aria-current="true"' : '' ?>>
            <div class="grow">
              <div class="semibold"><?= e($s['title']) ?></div>
              <?php if ($s['description']): ?><div class="small muted"><?= e($s['description']) ?></div><?php endif; ?>
            </div>
            <div class="service-price"><?= $s['price_unit'] === 'starting' ? '<span class="small subtle">from </span>' : '' ?><?= money($s['price']) ?><span class="small subtle"><?= e(price_unit_label($s['price_unit']) === '+' ? '' : price_unit_label($s['price_unit'])) ?></span></div>
            <?php if ($canHire && !$isOwner): ?>
              <a class="btn btn-sm <?= $isPicked ? 'btn-primary' : 'btn-soft' ?>" href="<?= e(url('/providers/' . $p['id'] . '/book', ['service' => $s['id']])) ?>">Book<span class="sr-only"> <?= e($s['title']) ?></span></a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>

      <?php if ($portfolio || $isOwner): ?>
      <section class="card card-body" id="portfolio">
        <div class="head-row mb-4"><h3>Work photos</h3><?php if ($portfolio): ?><span class="small subtle"><?= count($portfolio) ?> photo<?= count($portfolio) === 1 ? '' : 's' ?></span><?php endif; ?></div>
        <?php if (!$portfolio): ?>
          <div class="inline-empty"><?= icon('image') ?><p>Customers can't see this section until you add photos of your work. <a href="<?= e(url('/provider/profile#portfolio')) ?>">Add photos</a></p></div>
        <?php else: ?>
          <div class="gallery">
            <?php foreach ($portfolio as $item): ?>
              <a href="<?= e(upload_url($item['image'])) ?>" target="_blank" rel="noopener" title="<?= e($item['caption']) ?>">
                <img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['caption'] ?: 'Work photo') ?>" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <section class="card card-body" id="reviews">
        <div class="head-row mb-4"><h3>Reviews</h3><?php if ($totalReviews): ?><span class="small subtle">From completed bookings only</span><?php endif; ?></div>
        <?php if (!$totalReviews): ?>
          <?= empty_inline('star', 'No reviews yet', 'Only customers with a completed booking can leave a review, so every rating here is real.') ?>
        <?php else: ?>
          <div class="rating-summary">
            <div>
              <div class="rating-big"><?= number_format((float) $p['rating_avg'], 1) ?></div>
              <div class="mt-2"><?= stars((float) $p['rating_avg']) ?></div>
              <div class="small subtle mt-1"><?= $totalReviews ?> review<?= $totalReviews === 1 ? '' : 's' ?></div>
            </div>
            <div>
              <?php for ($i = 5; $i >= 1; $i--): $pct = $totalReviews ? round($dist[$i] / $totalReviews * 100) : 0; ?>
                <div class="bar-row"><span><?= $i ?>★</span><div class="bar"><span style="width:<?= $pct ?>%"></span></div><span class="text-right"><?= $dist[$i] ?></span></div>
              <?php endfor; ?>
            </div>
          </div>
          <div class="mt-4" data-more-list>
            <?php foreach ($reviews as $ri => $r): ?>
              <article class="review"<?= $ri >= 3 ? ' hidden data-more-item' : '' ?>>
                <div class="row" style="--gap:12px">
                  <?= avatar(['id' => $r['client_uid'], 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'avatar' => $r['avatar']], 'sm') ?>
                  <div class="grow">
                    <div class="semibold"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></div>
                    <div class="tiny subtle"><?= e($r['booking_title']) ?> · <?= e(fmt_date($r['created_at'])) ?></div>
                  </div>
                  <?= stars((float) $r['rating']) ?>
                </div>
                <?php if ($r['comment']): ?><p class="mt-3"><?= e($r['comment']) ?></p><?php endif; ?>
                <?php if ($r['provider_reply']): ?>
                  <div class="review-reply"><strong>Reply from <?= e($p['first_name']) ?>:</strong> <?= e($r['provider_reply']) ?></div>
                <?php elseif ($isOwner): ?>
                  <form class="row mt-3" method="post" action="<?= e(url('/reviews/' . $r['id'] . '/reply')) ?>">
                    <?= csrf_field() ?>
                    <input class="input" name="reply" placeholder="Write a public reply…" maxlength="500" required>
                    <button class="btn btn-sm" type="submit">Reply</button>
                  </form>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
          <?php if (count($reviews) > 3): ?>
            <button class="btn btn-block mt-4" type="button" data-more-toggle aria-expanded="false">Show all <?= count($reviews) ?> reviews <?= icon('chevron-down') ?></button>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    </div>

    <aside class="sticky stack" style="--gap:20px">
      <div class="card card-body book-card<?= $canHire && !$isOwner ? ' book-card-hire' : '' ?>">
        <?php if ($fromPrice !== null): ?>
          <div class="small subtle">Services from</div>
          <div class="price-lg"><?= money($fromPrice) ?></div>
          <div class="small muted mt-1">Custom jobs: <?= money($p['hourly_rate']) ?>/hour</div>
        <?php else: ?>
          <div class="small subtle">Starting rate</div>
          <div class="price-lg"><?= money($p['hourly_rate']) ?><span class="small subtle" style="font-weight:500">/hour</span></div>
        <?php endif; ?>
        <?php if ($isOwner): ?>
          <p class="small muted mt-3">This is how clients see your profile.</p>
          <a class="btn btn-block mt-4" href="<?= e(url('/provider/profile')) ?>">Edit profile</a>
        <?php elseif ($canHire): ?>
          <a class="btn btn-primary btn-lg btn-block mt-4" href="<?= e(url('/providers/' . $p['id'] . '/book')) ?>"><?= icon('calendar') ?> Request a booking</a>
          <p class="tiny subtle text-center mt-2">You won't be charged anything now.</p>
        <?php else: ?>
          <p class="small muted mt-3">Sign in with a client account to book this pro.</p>
        <?php endif; ?>
        <hr>
        <ul class="trust-list">
          <li><?= icon('shield-check') ?> <?= $p['verification_status'] === 'verified' ? 'Identity verified by Servease' : 'Verification in progress' ?></li>
          <li><?= icon('star') ?> Reviews only from completed bookings</li>
          <li><?= icon('wallet') ?> Pay your pro directly: GCash, Maya, bank or cash</li>
          <li><?= icon('message') ?> Chat before you book</li>
        </ul>
      </div>
      <?php if ($ll = city_coords($p['city'])): [$lat, $lng] = $ll; $dist = distance_label($p['city']); ?>
        <div class="card map-card">
          <div class="card-header"><h3><?= icon('map-pin') ?> Service area</h3><?php if ($dist): ?><span class="small subtle"><?= e($dist) ?></span><?php endif; ?></div>
          <iframe class="map-frame" title="Map of <?= e($p['city']) ?>" loading="lazy" referrerpolicy="no-referrer"
                  src="https://www.openstreetmap.org/export/embed.html?bbox=<?= rawurlencode(sprintf('%.4f,%.4f,%.4f,%.4f', $lng - .07, $lat - .045, $lng + .07, $lat + .045)) ?>&amp;layer=mapnik&amp;marker=<?= rawurlencode(sprintf('%.4f,%.4f', $lat, $lng)) ?>"></iframe>
          <div class="card-body small muted"><?= e($p['first_name']) ?> works in <?= e($p['service_area'] ?: $p['city'] . ' and nearby') ?>. The exact address is only shared after you book.</div>
        </div>
      <?php endif; ?>
      <?php if ($similar): ?>
        <div class="card">
          <div class="card-header"><h3>Similar pros</h3></div>
          <div class="list-divided">
            <?php foreach ($similar as $s): ?>
              <a class="list-item" href="<?= e(url('/providers/' . $s['id'])) ?>">
                <?= avatar($s, 'md') ?>
                <div class="grow">
                  <div class="list-title truncate"><?= e(full_name($s)) ?></div>
                  <div class="list-sub"><?= $s['rating_count'] ? '★ ' . number_format((float) $s['rating_avg'], 1) . ' · ' : '' ?><?= e(city_with_distance($s['city'])) ?></div>
                </div>
                <span class="semibold small"><?= money($s['hourly_rate']) ?>/hr</span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>
<?php if ($canHire && !$isOwner): ?>
  <div class="book-bar show-md">
    <div class="grow"><?php if ($fromPrice !== null): ?><small>Services from</small><strong><?= money($fromPrice) ?></strong><?php else: ?><small>Starting rate</small><strong><?= money($p['hourly_rate']) ?><span>/hr</span></strong><?php endif; ?></div>
    <a class="btn btn-primary btn-lg" href="<?= e(url('/providers/' . $p['id'] . '/book')) ?>"><?= icon('calendar') ?> Request a booking</a>
  </div>
<?php endif; ?>
<script>
  document.querySelectorAll('[data-more-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var items = b.parentNode.querySelectorAll('[data-more-item]');
      items.forEach(function (i) { i.hidden = false; });
      if (items[0]) { items[0].setAttribute('tabindex', '-1'); items[0].focus({ preventScroll: true }); }
      b.remove();
    });
  });
</script>
