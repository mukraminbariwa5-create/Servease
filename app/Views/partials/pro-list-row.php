<?php
/**
 * Laptop result row for a pro: face with trade badge, name, headline, track record,
 * their three lowest-priced services, starting rate and actions.
 * @var array $p         provider row
 * @var array $services  up to three services
 */
$link = url('/providers/' . $p['id']);
$saved = !empty($p['is_saved']);
$slug = $p['category_slug'] ?? null;
?>
<article class="pro-line">
  <span class="pro-line-face" aria-hidden="true">
    <?= avatar($p, 'lg') ?>
    <span class="pro-row-trade" style="--tint:<?= category_tint($slug) ?>"><?= category_art($slug, $p['category_icon'] ?? 'wrench', 'cat-art', 20) ?></span>
  </span>
  <div class="pro-line-body">
    <div class="pro-line-top">
      <h3 class="pro-line-name"><a href="<?= e($link) ?>"><?= e(full_name($p)) ?></a></h3>
      <?php if ($p['verification_status'] === 'verified'): ?><span class="badge badge-blue pro-line-badge"><?= icon('badge-check', 'icon-sm') ?> ID verified</span><?php endif; ?>
      <?php if (!$p['is_available']): ?><span class="badge">Fully booked</span><?php endif; ?>
    </div>
    <?php if (!empty($p['headline'])): ?><p class="pro-line-headline"><?= e($p['headline']) ?></p><?php endif; ?>
    <ul class="pro-line-meta">
      <li><?php if ((int) $p['rating_count'] > 0): ?><span class="rating"><?= icon('star') ?><?= number_format((float) $p['rating_avg'], 1) ?></span> <span class="subtle">(<?= (int) $p['rating_count'] ?> reviews)</span><?php else: ?><span class="tag">New pro</span><?php endif; ?></li>
      <li><?= icon('check-circle') ?><?= (int) $p['jobs_completed'] ?> jobs done</li>
      <li><?= icon('map-pin') ?><?= e(city_with_distance($p['city'] ?? '')) ?></li>
      <?php if ((int) ($p['years_experience'] ?? 0) > 0): ?><li><?= icon('briefcase') ?><?= (int) $p['years_experience'] ?> yrs experience</li><?php endif; ?>
    </ul>
    <?php if (!empty($services)): ?>
      <div class="pro-line-services">
        <?php foreach ($services as $sv): ?>
          <span class="svc-pill"><?= e($sv['title']) ?> <b><?= e(price_text($sv['price'], $sv['price_unit'])) ?></b></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="pro-line-side">
    <span class="pro-line-rate"><small>Rates from</small><strong><?= money($p['hourly_rate']) ?></strong><small>per hour</small></span>
    <div class="pro-line-actions">
      <?php if (!is_role('provider', 'admin')): ?>
        <form method="post" action="<?= e(url('/providers/' . $p['id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
          <?= csrf_field() ?>
          <button class="save-btn<?= $saved ? ' is-saved' : '' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>" aria-label="Save <?= e(full_name($p)) ?>" data-no-lock><?= icon('heart', 'icon-sm') ?></button>
        </form>
      <?php endif; ?>
      <a class="btn btn-primary" href="<?= e($link) ?>">View profile</a>
    </div>
  </div>
</article>
