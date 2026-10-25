<?php
$qCount = fn($st) => count(array_filter($quotes, fn($q) => ($q['status'] ?? '') === $st));
page_hero([
    'crumbs' => [['Dashboard', '/dashboard'], ['My quotes', null]],
    'title' => 'My quotes', 'lead' => 'Quotes you\'ve sent on the job board and where each one stands.',
    'stats' => [['file-text', '<strong>' . count($quotes) . '</strong> sent'], ['clock', '<strong>' . $qCount('pending') . '</strong> waiting'], ['check-circle', '<strong>' . $qCount('accepted') . '</strong> accepted']],
    'actions' => '<a class="btn btn-primary" href="' . e(url('/jobs')) . '">' . icon('search') . ' Find work</a>',
]);
?>
<?php app_bar('My quotes', '/account'); ?>
<div class="page-title">
  <div class="app-dup"><h1>My quotes</h1><p class="muted">Quotes you've sent on the job board.</p></div>
  <a class="btn btn-primary" href="<?= e(url('/jobs')) ?>"><?= icon('search') ?> Find work</a>
</div>
<?php if (!$quotes): ?>
  <div class="card empty">
    <?= empty_art('file-text') ?>
    <h3>No quotes yet</h3>
    <p>Browse open jobs in your category and send your first quote.</p>
    <a class="btn btn-primary" href="<?= e(url('/jobs')) ?>">Browse jobs</a>
  </div>
<?php else: ?>
  <div class="card data-table-wrap hide-md">
    <table class="data-table">
      <thead><tr><th>Job</th><th>Where</th><th>Sent</th><th class="num">Your price</th><th>Status</th><th><span class="sr-only">Open</span></th></tr></thead>
      <tbody>
        <?php foreach ($quotes as $q): ?>
          <tr>
            <td class="dt-main"><span class="dt-person"><span class="dt-ic" style="--tint:<?= category_tint($q['category_slug'] ?? null) ?>"><?= category_art($q['category_slug'] ?? null, $q['category_icon'] ?? 'file-text', 'cat-art', 22) ?></span>
              <span class="dt-stack"><a class="dt-link" href="<?= e(url($q['booking_id'] ? '/bookings/' . $q['booking_id'] : '/jobs/' . $q['job_id'])) ?>"><?= e($q['title']) ?></a><span class="dt-sub"><?= e($q['category_name']) ?><?= $q['booking_id'] ? ' · booked' : '' ?></span></span></span></td>
            <td><?= e($q['city']) ?></td>
            <td class="nowrap"><?= e(time_ago($q['created_at'])) ?></td>
            <td class="num nowrap"><span class="dt-strong"><?= money($q['amount']) ?></span></td>
            <td><?= badge($q['status']) ?></td>
            <td class="dt-go" aria-hidden="true"><?= icon('chevron-right') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card list-divided show-md">
    <?php foreach ($quotes as $q): ?>
      <a class="list-item" href="<?= e(url($q['booking_id'] ? '/bookings/' . $q['booking_id'] : '/jobs/' . $q['job_id'])) ?>">
        <span class="cat-icon cat-icon-md" style="--tint:<?= category_tint($q['category_slug'] ?? null) ?>"><?= category_art($q['category_slug'] ?? null, $q['category_icon'] ?? 'file-text') ?></span>
        <div class="grow">
          <div class="list-title truncate"><?= e($q['title']) ?></div>
          <div class="list-sub"><?= e($q['category_name']) ?> · <?= e($q['city']) ?> · sent <?= e(time_ago($q['created_at'])) ?><?= $q['booking_id'] ? ' · booked' : '' ?></div>
        </div>
        <div class="text-right">
          <div class="semibold nowrap"><?= money($q['amount']) ?></div>
          <div class="mt-1"><?= badge($q['status']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
