<?php
/** @var array $j job row with category_name, category_icon */
$budget = $j['budget_min'] && $j['budget_max'] ? money($j['budget_min']) . '–' . money($j['budget_max'])
    : ($j['budget_max'] ? 'Up to ' . money($j['budget_max']) : ($j['budget_min'] ? money($j['budget_min']) . '+' : 'Open budget'));
?>
<a class="card card-hover job-card" href="<?= e(url('/jobs/' . $j['id'])) ?>">
  <div class="row row-top" style="--gap:14px">
    <span class="cat-icon" style="--h:<?= hue_for($j['category_slug'] ?? $j['category_name'] ?? '') ?>;width:42px;height:42px"><?= icon($j['category_icon'] ?? 'briefcase') ?></span>
    <div class="grow">
      <div class="row row-between row-top">
        <div class="job-title"><?= e($j['title']) ?></div>
        <div class="job-budget"><?= $budget ?></div>
      </div>
      <p class="job-desc"><?= e($j['description']) ?></p>
      <div class="job-meta">
        <span><?= icon('tag') ?><?= e($j['category_name'] ?? 'General') ?></span>
        <span><?= icon('map-pin') ?><?= e($j['city']) ?></span>
        <span><?= icon('clock') ?><?= e(time_ago($j['created_at'])) ?></span>
        <span><?= icon('file-text') ?><?= (int) $j['quotes_count'] ?> quote<?= (int) $j['quotes_count'] === 1 ? '' : 's' ?></span>
        <?php if ($j['urgency'] === 'urgent'): ?><span class="urgent"><?= icon('zap') ?>Urgent</span><?php elseif ($j['urgency'] === 'this_week'): ?><span><?= icon('calendar') ?>This week</span><?php endif; ?>
        <?php if (($j['status'] ?? 'open') !== 'open'): ?><?= badge($j['status']) ?><?php endif; ?>
      </div>
    </div>
  </div>
</a>
