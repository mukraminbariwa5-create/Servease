<div class="page-title">
  <div>
    <h1>Bookings</h1>
    <p class="muted"><?= $isPro ? 'Requests and jobs from your clients.' : 'Everything you\'ve booked, in one place.' ?></p>
  </div>
  <?php if (!$isPro && !is_role('admin')): ?><a class="btn btn-primary" href="<?= e(url('/providers')) ?>"><?= icon('search') ?> Find a pro</a><?php endif; ?>
</div>
<nav class="tabs" aria-label="Booking status">
  <?php foreach ($tabs as $k => [$label]): ?>
    <a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/bookings', ['tab' => $k])) ?>"><?= $label ?> <span class="n"><?= $counts[$k] ?></span></a>
  <?php endforeach; ?>
</nav>
<?php if (!$rows): ?>
  <div class="card empty">
    <div class="empty-icon"><?= icon('calendar') ?></div>
    <h3>No <?= $tab === 'active' ? 'active' : strtolower($tabs[$tab][0]) ?> bookings</h3>
    <p><?= $isPro ? 'Keep your profile complete and send quotes on the job board to get booked.' : 'Find a pro and request a booking, or post a job to get quotes.' ?></p>
    <a class="btn btn-primary" href="<?= e(url($isPro ? '/jobs' : '/providers')) ?>"><?= $isPro ? 'Find work' : 'Find a pro' ?></a>
  </div>
<?php else: ?>
  <div class="card list-divided">
    <?php foreach ($rows as $b): $other = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']]; ?>
      <a class="list-item" href="<?= e(url('/bookings/' . $b['id'])) ?>">
        <?= avatar($other, 'md') ?>
        <div class="grow" style="min-width:0">
          <div class="list-title truncate"><?= e($b['title']) ?></div>
          <div class="list-sub"><?= $isPro ? 'Client' : 'Pro' ?>: <?= e(full_name($other)) ?> · <?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'D, M j') : 'Date TBD') ?><?= $b['scheduled_time'] ? ' · ' . e(BookingController::TIMES[$b['scheduled_time']] ?? $b['scheduled_time']) : '' ?></div>
        </div>
        <div class="text-right hide-sm">
          <div class="semibold"><?= money($b['amount']) ?></div>
          <div class="tiny subtle"><?= $b['payment_status'] === 'paid' ? 'Paid' : ($b['payment_status'] === 'pending' ? 'Payment pending' : 'Unpaid') ?></div>
        </div>
        <?= badge($b['status']) ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
