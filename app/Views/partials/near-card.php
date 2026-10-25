<?php
/**
 * "Near you" card: photo with the rating and a heart, then the service, its trade, the price
 * and the city. The whole card is one link.
 * @var array $s  service row from ServiceController::query
 */
$link = url('/providers/' . $s['provider_id'], ['service' => $s['id']]) . '#services';
$slug = $s['category_slug'] ?? null;
$photo = service_photo($slug, $s['cover'] ?? null, (int) $s['id']);
$p = price_parts($s['price'], $s['price_unit']);
?>
<article class="near-card">
  <span class="near-media<?= $photo ? '' : ' is-icon' ?>" aria-hidden="true">
    <?php if ($photo): ?><img<?= empty($s['cover']) ? ' class="is-stock"' : '' ?> src="<?= e($photo) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><?= category_art($slug, $s['category_icon'] ?? 'wrench') ?><?php endif; ?>
    <?php if ((int) $s['rating_count'] > 0): ?><span class="svc-rate"><?= icon('star') ?><b><?= number_format((float) $s['rating_avg'], 1) ?></b> (<?= (int) $s['rating_count'] ?>)</span><?php endif; ?>
  </span>
  <?php if (!is_role('provider', 'admin')): ?>
    <form class="card-save" method="post" action="<?= e(url('/providers/' . $s['provider_id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
      <?= csrf_field() ?>
      <button class="save-btn<?= !empty($s['is_saved']) ? ' is-saved' : '' ?>" type="submit" aria-pressed="<?= !empty($s['is_saved']) ? 'true' : 'false' ?>" aria-label="Save <?= e($s['first_name']) ?>" data-no-lock><?= icon('heart', 'icon-sm') ?></button>
    </form>
  <?php endif; ?>
  <span class="near-body">
    <h3 class="near-title"><a href="<?= e($link) ?>"><?= e($s['title']) ?></a></h3>
    <span class="near-type"><?= e(preg_replace('/ &.*$/', '', $s['category_name'] ?? 'Service')) ?>, by <?= e($s['first_name'] . ' ' . mb_substr($s['last_name'], 0, 1) . '.') ?></span>
    <span class="svc-price-line"><?php if ($p['from']): ?><small class="price-from">from</small><?php endif; ?><span class="price-tag svc-price"><?= $p['amount'] ?><?php if ($p['unit']): ?><small><?= e(str_replace('/ ', '/', $p['unit'])) ?></small><?php endif; ?></span></span>
    <span class="near-foot">
      <?php $km = city_km($s['city'] ?? null, current_city()); ?>
      <span class="near-city"><?= icon('map-pin') ?><span class="truncate"><?= e($s['city'] ?? '') ?></span><?php if ($km !== null && $km <= 80): ?><span class="near-km"><?= $km < 1 ? 'Your city' : '~' . max(1, (int) round($km)) . ' km' ?></span><?php endif; ?></span>
      <span class="svc-go" aria-hidden="true"><?= icon('chevron-right') ?></span>
    </span>
  </span>
</article>
