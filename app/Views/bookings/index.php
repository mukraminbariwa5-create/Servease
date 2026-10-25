<?php
page_hero([
    'crumbs' => [['Home', $isPro ? '/dashboard' : '/'], ['Bookings', null]],
    'title' => 'Bookings',
    'lead' => $isPro ? 'Requests and jobs from your clients, from first message to payment.' : 'Everything you\'ve booked, from request to payment.',
    'stats' => [['calendar', '<strong>' . (int) $counts['active'] . '</strong> active'], ['check-circle', '<strong>' . (int) $counts['completed'] . '</strong> completed'], ['x-circle', '<strong>' . (int) $counts['closed'] . '</strong> cancelled']],
    'actions' => is_role('admin') ? '' : ($isPro ? '<a class="btn btn-primary" href="' . e(url('/jobs')) . '">' . icon('search') . ' Find work</a>' : '<a class="btn btn-primary" href="' . e(url('/services')) . '">' . icon('search') . ' Find a service</a>'),
]);
?>
<div class="page-title">
  <div>
    <h1>Bookings</h1>
    <p class="muted"><?= $isPro ? 'Requests and jobs from your clients.' : 'Everything you\'ve booked, in one place.' ?></p>
  </div>
  <?php if (!$isPro && !is_role('admin')): ?><a class="btn btn-primary" href="<?= e(url('/services')) ?>"><?= icon('search') ?> Find a service</a><?php endif; ?>
</div>
<nav class="tabs" aria-label="Booking status">
  <?php foreach ($tabs as $k => [$label]): ?>
    <a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/bookings', ['tab' => $k])) ?>"><?= $label ?> <span class="n"><?= $counts[$k] ?></span></a>
  <?php endforeach; ?>
</nav>
<?php if (!$rows): ?>
  <div class="card empty">
    <?= empty_art('calendar') ?>
    <h3>No <?= $tab === 'active' ? 'active' : strtolower($tabs[$tab][0]) ?> bookings</h3>
    <p><?= $isPro ? 'Keep your profile complete and send quotes on the job board to get booked.' : 'Find a pro and request a booking, or post a job to get quotes.' ?></p>
    <a class="btn btn-primary" href="<?= e(url($isPro ? '/jobs' : '/providers')) ?>"><?= $isPro ? 'Find work' : 'Find a pro' ?></a>
  </div>
<?php else: ?>
  <!-- Laptop: a table, one booking per row -->
  <div class="card data-table-wrap hide-md">
    <table class="data-table">
      <thead><tr><th>Booking</th><th><?= $isPro ? 'Client' : 'Pro' ?></th><th>When</th><th class="num">Amount</th><th>Status</th><th><span class="sr-only">Open</span></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $b): $other = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']]; ?>
          <tr>
            <td class="dt-main">
              <a class="dt-link" href="<?= e(url('/bookings/' . $b['id'])) ?>"><?= e($b['title']) ?></a>
              <span class="dt-sub"><?= e(booking_ref((int) $b['id'])) ?>, requested <?= e(fmt_date($b['created_at'], 'M j')) ?></span>
            </td>
            <td><span class="dt-person"><?= avatar($other, 'sm') ?><span class="truncate"><?= e(full_name($other)) ?></span></span></td>
            <td class="nowrap"><span class="dt-strong"><?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'D, M j') : 'To be agreed') ?></span><span class="dt-sub"><?= $b['scheduled_time'] ? e(Availability::range($b['scheduled_time'], (int) ($b['duration_hours'] ?? 1))) : '&nbsp;' ?></span></td>
            <td class="num nowrap"><span class="dt-strong"><?= money($b['amount']) ?></span><span class="dt-sub dt-pay dt-pay-<?= e($b['payment_status']) ?>"><?= $b['payment_status'] === 'paid' ? 'Paid' : ($b['payment_status'] === 'pending' ? 'Payment pending' : 'Unpaid') ?></span></td>
            <td><?= badge($b['status']) ?></td>
            <td class="dt-go" aria-hidden="true"><?= icon('chevron-right') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Phone: each booking as a ticket stub, the day on the left -->
  <div class="stub-list show-md">
    <?php foreach ($rows as $b): $other = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']];
        $ts = $b['scheduled_date'] ? strtotime($b['scheduled_date']) : null; ?>
      <a class="stub" href="<?= e(url('/bookings/' . $b['id'])) ?>">
        <span class="stub-day<?= $ts ? '' : ' is-tbd' ?>">
          <?php if ($ts): ?><small><?= date('M', $ts) ?></small><strong><?= date('j', $ts) ?></strong><small><?= date('D', $ts) ?></small><?php else: ?><small>Date</small><strong>?</strong><small>to agree</small><?php endif; ?>
        </span>
        <span class="stub-body">
          <span class="stub-title"><?= e($b['title']) ?></span>
          <span class="stub-meta"><?= $b['scheduled_time'] ? e(BookingController::TIMES[$b['scheduled_time']] ?? $b['scheduled_time']) . ', ' : '' ?><?= $isPro ? 'for' : 'with' ?> <?= e($other['first_name'] . ' ' . mb_substr((string) $other['last_name'], 0, 1) . '.') ?></span>
          <span class="stub-foot">
            <span class="stub-ref"><?= e(booking_ref((int) $b['id'])) ?></span>
            <?= badge($b['status']) ?>
          </span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
