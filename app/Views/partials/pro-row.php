<?php
/**
 * A suggested pro (home): initials or face, name with the ID badge, trade, rating and jobs,
 * then the starting rate and a View profile button. The whole card is one link.
 * @var array $p  provider row joined with profile and category
 */
$link = url('/providers/' . $p['id']);
?>
<article class="pro-tile">
  <div class="pro-tile-head">
    <?= avatar($p, 'lg') ?>
    <span class="grow">
      <h3 class="pro-tile-name"><a href="<?= e($link) ?>"><?= e(full_name($p)) ?></a><?php if ($p['verification_status'] === 'verified'): ?> <span class="verified" title="ID verified"><?= icon('badge-check', 'icon-sm') ?><span class="sr-only">ID verified</span></span><?php endif; ?></h3>
      <span class="pro-tile-sub truncate"><?= e(preg_replace('/ &.*$/', '', $p['category_name'] ?? 'Service pro')) ?>, <?= e(city_with_distance($p['city'] ?? '')) ?></span>
    </span>
  </div>
  <div class="pro-tile-meta">
    <?php if ((int) $p['rating_count'] > 0): ?>
      <span class="rating"><?= icon('star') ?><b><?= number_format((float) $p['rating_avg'], 1) ?></b> <span class="subtle">(<?= (int) $p['rating_count'] ?>)</span></span>
    <?php else: ?><span class="tag">New pro</span><?php endif; ?>
    <?php if ((int) $p['jobs_completed'] > 0): ?><span><?= (int) $p['jobs_completed'] ?> jobs</span><?php endif; ?>
    <span class="pro-tile-rate"><?= money($p['hourly_rate']) ?><small>/hr</small></span>
  </div>
  <span class="btn btn-block pro-tile-btn" aria-hidden="true">View profile</span>
</article>
