<?php
/** @var array $p  provider row joined with profile, category and cover photo */
$saved = !empty($p['is_saved']);
$link = url('/providers/' . $p['id']);
$name = full_name($p);
?>
<article class="pro-card">
  <a class="media" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true"><?= partial('card-media', ['row' => $p, 'mode' => 'person']) ?></a>
  <?php if (!is_role('provider', 'admin')): ?>
    <form class="pro-save" method="post" action="<?= e(url('/providers/' . $p['id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
      <?= csrf_field() ?>
      <button class="save-btn <?= $saved ? 'is-saved' : '' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>" aria-label="Save <?= e($name) ?>" data-no-lock><?= icon('heart', 'icon-sm') ?></button>
    </form>
  <?php endif; ?>
  <div class="pro-body">
    <a class="pro-name" href="<?= e($link) ?>">
      <span class="truncate"><?= e($name) ?></span>
      <?php if ($p['verification_status'] === 'verified'): ?><span class="verified" title="ID verified"><?= icon('badge-check', 'icon-sm') ?><span class="sr-only">ID verified</span></span><?php endif; ?>
    </a>
    <span class="pro-sub truncate"><?= e($p['category_name'] ?? 'Service pro') ?>, <?= e(city_with_distance($p['city'] ?? '')) ?></span>
    <span class="pro-meta">
      <?php if ((int) $p['rating_count'] > 0): ?>
        <span class="rating"><?= icon('star') ?><?= number_format((float) $p['rating_avg'], 1) ?> <span class="subtle">(<?= (int) $p['rating_count'] ?>)</span></span>
      <?php else: ?><span class="tag">New pro</span><?php endif; ?>
      <?php if ((int) $p['jobs_completed'] > 0): ?><span class="subtle"><?= (int) $p['jobs_completed'] ?> jobs</span><?php endif; ?>
    </span>
    <div class="pro-foot">
      <span class="pro-price"><small>Rates from</small><strong><?= money($p['hourly_rate']) ?><span> / hour</span></strong></span>
      <a class="arrow-btn" href="<?= e($link) ?>" aria-label="View <?= e($name) ?>'s profile"><?= icon('arrow-up-right') ?></a>
    </div>
  </div>
</article>
