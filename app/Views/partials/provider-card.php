<?php
/** @var array $p  provider row joined with profile + category */
$saved = !empty($p['is_saved']);
$link = url('/providers/' . $p['id']);
?>
<article class="card card-hover pcard">
  <div class="pcard-head">
    <?= avatar($p, 'lg') ?>
    <div class="grow" style="min-width:0">
      <a class="pcard-name" href="<?= e($link) ?>"><span class="truncate"><?= e(full_name($p)) ?></span></a>
      <div class="pcard-sub truncate"><?= e($p['category_name'] ?? 'Service pro') ?></div>
      <div class="pcard-sub pcard-city truncate"><?= icon('map-pin') ?><?= e($p['city'] ?? '—') ?></div>
    </div>
  </div>
  <div class="pcard-tags">
    <?php if ($p['verification_status'] === 'verified'): ?><span class="tag tag-verified"><?= icon('shield-check') ?>Verified</span><?php endif; ?>
    <?php if ((int) $p['rating_count'] > 0): ?>
      <span class="tag tag-star"><?= icon('star') ?><?= number_format((float) $p['rating_avg'], 1) ?> · <?= (int) $p['rating_count'] ?> review<?= (int) $p['rating_count'] === 1 ? '' : 's' ?></span>
    <?php else: ?>
      <span class="tag">New pro</span>
    <?php endif; ?>
    <?php if ((int) $p['jobs_completed'] > 0): ?><span class="tag"><?= (int) $p['jobs_completed'] ?> jobs done</span><?php endif; ?>
  </div>
  <p class="pcard-headline"><?= e($p['headline'] ?: 'Available for jobs in ' . ($p['city'] ?? 'your area') . '.') ?></p>
  <div class="pcard-body-gap"></div>
  <div class="pcard-foot">
    <span class="pcard-price">from <strong><?= money($p['hourly_rate']) ?>/hr</strong></span>
    <div class="row" style="--gap:6px">
      <?php if (!is_role('provider', 'admin')): ?>
        <form class="pcard-save" method="post" action="<?= e(url('/providers/' . $p['id'] . '/save')) ?>" data-save-toggle data-login="<?= e(url('/login')) ?>">
          <?= csrf_field() ?>
          <button class="save-btn <?= $saved ? 'is-saved' : '' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>" aria-label="Save <?= e(full_name($p)) ?>" data-no-lock><?= icon('heart', 'icon-sm') ?></button>
        </form>
        <?php endif; ?>
      <a class="btn btn-dark btn-sm" href="<?= e($link) ?>">View profile</a>
    </div>
  </div>
</article>
