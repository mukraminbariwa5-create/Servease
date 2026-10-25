<?php
/**
 * One service as a photo card (laptop: a grid card; phone: photo on the left).
 * Photo with the rating, a heart to save the pro, then the service, its trade, the price,
 * and who does it. The whole card is one link to the pro's profile with this service picked.
 * @var array $s  provider service row joined with provider, profile, category and cover photo
 */
$link = url('/providers/' . $s['provider_id'], ['service' => $s['id']]) . '#services';
$pro = ['id' => $s['provider_id'], 'first_name' => $s['first_name'], 'last_name' => $s['last_name'], 'avatar' => $s['avatar']];
$slug = $s['category_slug'] ?? null;
$photo = service_photo($slug, $s['cover'] ?? null, (int) $s['id']);
$p = price_parts($s['price'], $s['price_unit']);
$saved = !empty($s['is_saved']);
?>
<article class="svc-card">
  <span class="svc-media<?= $photo ? '' : ' is-icon' ?>" aria-hidden="true">
    <?php if ($photo): ?><img<?= empty($s['cover']) ? ' class="is-stock"' : '' ?> src="<?= e($photo) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><?= category_art($slug, $s['category_icon'] ?? 'wrench') ?><?php endif; ?>
    <?php if ((int) $s['rating_count'] > 0): ?><span class="svc-rate"><?= icon('star') ?><b><?= number_format((float) $s['rating_avg'], 1) ?></b> (<?= (int) $s['rating_count'] ?>)</span><?php endif; ?>
  </span>
  <?php if (!is_role('provider', 'admin')): ?>
    <form class="card-save" method="post" action="<?= e(url('/providers/' . $s['provider_id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
      <?= csrf_field() ?>
      <button class="save-btn <?= $saved ? 'is-saved' : '' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>" aria-label="Save <?= e($s['first_name']) ?>" data-no-lock><?= icon('heart', 'icon-sm') ?></button>
    </form>
  <?php endif; ?>
  <div class="svc-body">
    <h3 class="svc-title"><a href="<?= e($link) ?>"><?= e($s['title']) ?></a></h3>
    <span class="svc-type"><?= e(preg_replace('/ &.*$/', '', $s['category_name'] ?? 'Service')) ?></span>
    <span class="svc-price-line"><?php if ($p['from']): ?><small class="price-from">from</small><?php endif; ?><span class="price-tag svc-price"><?= $p['amount'] ?><?php if ($p['unit']): ?><small><?= e(str_replace('/ ', '/', $p['unit'])) ?></small><?php endif; ?></span></span>
    <div class="svc-foot">
      <span class="svc-pro">
        <?= avatar($pro, 'sm') ?>
        <span class="svc-pro-text">
          <span class="truncate"><?= e($s['first_name'] . ' ' . mb_substr($s['last_name'], 0, 1) . '.') ?></span>
          <?php if ($s['verification_status'] === 'verified'): ?><small class="svc-verified"><?= icon('check-circle') ?>Verified provider</small><?php else: ?><small><?= e(city_with_distance($s['city'] ?? '')) ?></small><?php endif; ?>
        </span>
      </span>
      <span class="svc-go" aria-hidden="true"><?= icon('chevron-right') ?></span>
    </div>
  </div>
</article>
