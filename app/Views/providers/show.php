<?php
$me = user();
$isOwner = $me && (int) $me['id'] === (int) $p['id'];
$canHire = !$me || $me['role'] === 'client';
$totalReviews = array_sum($dist);
$hue = hue_for($p['category_slug'] ?? $p['id']);
$memberSince = fmt_date($p['created_at'], 'M Y');
?>
<div class="profile-cover" style="--h:<?= $hue ?>"></div>
<div class="container">
  <div class="profile-head">
    <?= avatar($p, 'xl') ?>
    <div class="grow" style="padding-bottom:6px">
      <div class="profile-name">
        <?= e(full_name($p)) ?>
        <?php if ($p['verification_status'] === 'verified'): ?><span class="verified" title="Verified pro"><?= icon('badge-check', 'icon-lg') ?></span><?php endif; ?>
      </div>
      <p class="muted"><?= e($p['headline']) ?></p>
      <div class="stat-row">
        <?php if ($p['rating_count'] > 0): ?>
          <span class="rating"><?= icon('star') ?><?= number_format((float) $p['rating_avg'], 1) ?> <span class="subtle">(<?= (int) $p['rating_count'] ?> reviews)</span></span>
        <?php endif; ?>
        <span><?= icon('tag') ?><?= e($p['category_name'] ?? 'General') ?></span>
        <span><?= icon('map-pin') ?><?= e($p['city']) ?></span>
        <span><?= icon('briefcase') ?><?= (int) $p['years_experience'] ?> yrs experience</span>
        <span><?= icon('check-circle') ?><?= (int) $p['jobs_completed'] ?> jobs done</span>
      </div>
    </div>
    <div class="profile-actions">
      <?php if ($isOwner): ?>
        <a class="btn btn-primary" href="<?= e(url('/provider/profile')) ?>"><?= icon('pencil') ?> Edit profile</a>
      <?php else: ?>
        <?php if ($canHire): ?>
          <form method="post" action="<?= e(url('/providers/' . $p['id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-icon save-btn <?= $isSaved ? 'is-saved' : '' ?>" style="background:var(--surface);border:1px solid var(--border-strong)" type="submit" aria-label="Save" aria-pressed="<?= $isSaved ? 'true' : 'false' ?>" data-no-lock><?= icon('heart') ?></button>
          </form>
        <?php endif; ?>
        <?php if ($me && !$isOwner): ?>
          <form method="post" action="<?= e(url('/messages/start')) ?>">
            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
            <button class="btn" type="submit"><?= icon('message') ?> Message</button>
          </form>
        <?php else: ?>
          <a class="btn" href="<?= e(url('/login')) ?>"><?= icon('message') ?> Message</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="layout-aside page">
    <div class="stack" style="--gap:24px">
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
          <dt>Member since</dt><dd><?= e($memberSince) ?></dd>
          <dt>Availability</dt><dd><?= $p['is_available'] ? '<span class="badge badge-green">Taking new jobs</span>' : '<span class="badge">Fully booked</span>' ?></dd>
        </dl>
      </section>

      <section class="card card-body">
        <div class="row row-between mb-2"><h3>Services &amp; rates</h3><span class="small subtle">Final price is agreed before booking</span></div>
        <?php if (!$services): ?><p class="muted mt-2">No fixed-price services listed. Message this pro for a quote.</p><?php endif; ?>
        <?php foreach ($services as $s): ?>
          <div class="service-row">
            <div class="grow">
              <div class="semibold"><?= e($s['title']) ?></div>
              <?php if ($s['description']): ?><div class="small muted"><?= e($s['description']) ?></div><?php endif; ?>
            </div>
            <div class="service-price"><?= $s['price_unit'] === 'starting' ? '<span class="small subtle">from </span>' : '' ?><?= money($s['price']) ?><span class="small subtle"><?= e(price_unit_label($s['price_unit']) === '+' ? '' : price_unit_label($s['price_unit'])) ?></span></div>
            <?php if ($canHire && !$isOwner): ?>
              <a class="btn btn-sm btn-soft" href="<?= e(url('/providers/' . $p['id'] . '/book', ['service' => $s['id']])) ?>">Book</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>

      <section class="card card-body">
        <h3 class="mb-4">Portfolio</h3>
        <?php if (!$portfolio): ?>
          <div class="empty" style="padding:28px 12px">
            <div class="empty-icon"><?= icon('image') ?></div>
            <p>No photos yet<?= $isOwner ? ' — add some from your profile editor to win more clients.' : '.' ?></p>
          </div>
        <?php else: ?>
          <div class="gallery">
            <?php foreach ($portfolio as $item): ?>
              <a href="<?= e(upload_url($item['image'])) ?>" target="_blank" rel="noopener" title="<?= e($item['caption']) ?>">
                <img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['caption'] ?: 'Portfolio photo') ?>" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="card card-body" id="reviews">
        <h3 class="mb-4">Reviews</h3>
        <?php if (!$totalReviews): ?>
          <p class="muted">No reviews yet. Reviews can only be left by clients after a completed booking.</p>
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
          <div class="mt-4">
            <?php foreach ($reviews as $r): ?>
              <article class="review">
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
        <?php endif; ?>
      </section>
    </div>

    <aside class="sticky stack" style="--gap:20px">
      <div class="card card-body book-card">
        <div class="small subtle">Starting rate</div>
        <div class="price-lg"><?= money($p['hourly_rate']) ?><span class="small subtle" style="font-weight:500">/hour</span></div>
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
          <li><?= icon('shield-check') ?> <?= $p['verification_status'] === 'verified' ? 'Identity verified by Fixoria' : 'Verification in progress' ?></li>
          <li><?= icon('star') ?> Reviews only from completed bookings</li>
          <li><?= icon('wallet') ?> Pay by cash, GCash, Maya or bank</li>
          <li><?= icon('message') ?> Chat before you book</li>
        </ul>
      </div>
      <?php if ($similar): ?>
        <div class="card">
          <div class="card-header"><h3>Similar pros</h3></div>
          <div class="list-divided">
            <?php foreach ($similar as $s): ?>
              <a class="list-item" href="<?= e(url('/providers/' . $s['id'])) ?>">
                <?= avatar($s, 'md') ?>
                <div class="grow">
                  <div class="list-title truncate"><?= e(full_name($s)) ?></div>
                  <div class="list-sub"><?= $s['rating_count'] ? '★ ' . number_format((float) $s['rating_avg'], 1) . ' · ' : '' ?><?= e($s['city']) ?></div>
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
