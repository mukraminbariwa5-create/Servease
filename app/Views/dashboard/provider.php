<?php
$u = user();
$done = count(array_filter($checklist, fn($c) => $c[1]));
$pct = (int) round($done / count($checklist) * 100);
$max = max(1, max(array_column($months, 'value')));
page_hero([
    'crumbs' => [['Dashboard', null]],
    'title' => 'Welcome back, ' . $u['first_name'],
    'lead' => $stats['requests'] ? 'You have ' . $stats['requests'] . ' new request' . ($stats['requests'] === 1 ? '' : 's') . ' waiting for a reply.' : 'Here\'s how your business is doing.',
    'stats' => array_values(array_filter([
        [!empty($profile['verification_status']) && $profile['verification_status'] === 'verified' ? 'badge-check' : 'shield', $profile['verification_status'] === 'verified' ? 'ID verified' : 'Not verified yet'],
        !empty($profile['category_name']) ? ['tag', e($profile['category_name'])] : null,
        !empty($profile['city']) ? ['map-pin', e($profile['city'])] : null,
        ['clock', e(Availability::summary($profile))],
    ])),
    'actions' => '<form method="post" action="' . e(url('/provider/availability')) . '">' . csrf_field()
        . '<button class="btn" type="submit">' . (!empty($profile['is_available']) ? '<span class="live-dot"></span> Taking jobs' : '<span class="badge badge-plain">●</span> Fully booked') . '</button></form>'
        . '<a class="btn btn-primary" href="' . e(url('/jobs')) . '">' . icon('search') . ' Find work</a>',
]);
?>
<div class="page-title">
  <div>
    <h1>Welcome back, <?= e($u['first_name']) ?></h1>
    <p class="muted"><?= $stats['requests'] ? 'You have ' . $stats['requests'] . ' new request' . ($stats['requests'] === 1 ? '' : 's') . ' waiting for a reply.' : 'Here\'s how your business is doing.' ?></p>
  </div>
  <div class="row">
    <form method="post" action="<?= e(url('/provider/availability')) ?>"><?= csrf_field() ?>
      <button class="btn" type="submit"><?= !empty($profile['is_available']) ? '<span class="badge badge-green badge-plain">●</span> Taking jobs' : '<span class="badge badge-plain">●</span> Fully booked' ?></button>
    </form>
    <a class="btn btn-primary" href="<?= e(url('/jobs')) ?>"><?= icon('search') ?> Find work</a>
  </div>
</div>

<div class="stats">
  <div class="card stat"><div class="stat-top"><span class="stat-label">New requests</span><span class="stat-icon amber"><?= icon('inbox') ?></span></div><div class="stat-value"><?= $stats['requests'] ?></div><div class="stat-sub">Waiting for your reply</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Upcoming jobs</span><span class="stat-icon"><?= icon('calendar') ?></span></div><div class="stat-value"><?= $stats['upcoming'] ?></div><div class="stat-sub">Confirmed or in progress</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Earned this month</span><span class="stat-icon green"><?= icon('wallet') ?></span></div><div class="stat-value"><?= money($stats['earned']) ?></div><div class="stat-sub"><?= money($stats['earned_all']) ?> all time</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Rating</span><span class="stat-icon violet"><?= icon('star') ?></span></div><div class="stat-value"><?= $stats['reviews'] ? number_format($stats['rating'], 1) : '—' ?></div><div class="stat-sub"><?= $stats['reviews'] ?> review<?= $stats['reviews'] === 1 ? '' : 's' ?></div></div>
</div>

<?php if ($stats['overdue']): ?>
  <a class="alert alert-warning mt-4" href="<?= e(url('/bookings')) ?>" style="color:var(--sv-text)"><?= icon('calendar-check') ?><div class="grow"><strong><?= $stats['overdue'] ?> job<?= $stats['overdue'] === 1 ? '' : 's' ?> past <?= $stats['overdue'] === 1 ? 'its' : 'their' ?> booked day.</strong> Mark <?= $stats['overdue'] === 1 ? 'it' : 'them' ?> completed, or propose a new time.</div><?= icon('chevron-right') ?></a>
<?php endif; ?>
<?php foreach ($pendingPayments as $pp): ?>
  <a class="alert alert-warning mt-4" href="<?= e(url('/bookings/' . $pp['booking_id'] . '#payment')) ?>" style="color:var(--sv-text)"><?= icon('wallet') ?><div class="grow"><strong>Confirm payment:</strong> client says they paid <?= money($pp['amount']) ?> via <?= e(payment_method_label($pp['method'])) ?> for “<?= e($pp['title']) ?>”.</div><?= icon('chevron-right') ?></a>
<?php endforeach; ?>

<div class="grid split mt-6" style="--cols:minmax(0,1.45fr) minmax(0,1fr);gap:20px;align-items:start">
  <div class="stack" style="--gap:20px">
    <section class="card">
      <div class="card-header"><h3>Requests &amp; upcoming jobs</h3><a class="small semibold" href="<?= e(url('/bookings')) ?>">All bookings</a></div>
      <?php if (!$requests): ?>
        <div class="empty"><?= empty_art('briefcase') ?><h3>No active bookings</h3><p>Send quotes on open jobs to win new clients.</p><a class="btn btn-primary" href="<?= e(url('/jobs')) ?>">Browse jobs</a></div>
      <?php else: ?>
        <div class="list-divided">
          <?php foreach ($requests as $b): $o = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']]; ?>
            <a class="list-item" href="<?= e(url('/bookings/' . $b['id'])) ?>">
              <?= avatar($o, 'md') ?>
              <div class="grow" style="min-width:0">
                <div class="list-title truncate"><?= e($b['title']) ?></div>
                <div class="list-sub"><?= e(full_name($o)) ?> · <?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'D, M j') : 'Date TBD') ?> · <?= money($b['amount']) ?></div>
              </div>
              <?php $late = $b['scheduled_date'] && $b['scheduled_date'] < date('Y-m-d') && $b['status'] !== 'requested'; ?>
              <?= $b['status'] === 'requested' ? '<span class="btn btn-sm btn-primary">Respond</span>' : ($late ? '<span class="badge badge-amber">Overdue</span>' : badge($b['status'])) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-header"><h3>New jobs in <?= e($profile['category_name'] ?? 'your category') ?></h3><a class="small semibold" href="<?= e(url('/jobs', ['category' => $profile['category_slug'] ?? null])) ?>">Job board</a></div>
      <?php if (!$jobs): ?>
        <?= empty_inline('briefcase', 'No new jobs right now', "We'll notify you when a customer posts one in your category.", '<a class="btn btn-sm" href="' . e(url('/jobs')) . '">Browse all jobs</a>') ?>
      <?php else: ?>
        <div class="list-divided">
          <?php foreach ($jobs as $j): ?>
            <a class="list-item" href="<?= e(url('/jobs/' . $j['id'])) ?>">
              <div class="grow" style="min-width:0">
                <div class="list-title truncate"><?= e($j['title']) ?></div>
                <div class="list-sub"><?= e($j['city']) ?> · <?= e(time_ago($j['created_at'])) ?> · <?= (int) $j['quotes_count'] ?> quotes</div>
              </div>
              <span class="semibold small nowrap"><?= $j['budget_max'] ? 'up to ' . money($j['budget_max']) : 'Open budget' ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="stack" style="--gap:20px">
    <?php if ($pct < 100): ?>
      <section class="card card-body">
        <div class="row row-between"><h3>Complete your profile</h3><span class="semibold small"><?= $pct ?>%</span></div>
        <div class="bar mt-3"><span style="width:<?= $pct ?>%;background:var(--sv-primary)"></span></div>
        <?php usort($checklist, fn ($a, $b) => $a[1] <=> $b[1]); /* still to do first */ ?>
        <ul class="todo-list">
          <?php foreach ($checklist as [$label, $ok, $href]): ?>
            <?php if ($ok): ?>
              <li class="todo is-done"><?= icon('check-circle', 'todo-ic') ?><span class="grow"><?= e($label) ?></span><span class="todo-done">Done</span></li>
            <?php else: ?>
              <li><a class="todo" href="<?= e(url($href)) ?>"><span class="todo-ring" aria-hidden="true"></span><span class="grow"><?= e($label) ?></span><?= icon('chevron-right', 'todo-go') ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <section class="card card-body">
      <div class="row row-between"><h3>Earnings</h3><span class="small subtle">Last 6 months</span></div>
      <div class="chart chart-valued" style="height:170px">
        <?php foreach ($months as $mi => $m): $isNow = $mi === count($months) - 1; ?>
          <div class="chart-col<?= $isNow ? ' is-now' : '' ?>">
            <span class="chart-val"><?= $m['value'] > 0 ? e(short_money($m['value'])) : '—' ?></span>
            <div class="chart-bar" style="height:<?= max(2, round($m['value'] / $max * 82)) ?>%;max-width:34px" data-tip="<?= e($m['label'] . ': ' . money($m['value'])) ?>"></div>
            <span class="chart-lbl"><?= e($m['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card" id="reviews">
      <div class="card-header"><h3>Latest reviews</h3><a class="small semibold" href="<?= e(url('/providers/' . $u['id'] . '#reviews')) ?>">View all</a></div>
      <?php if (!$reviews): ?>
        <?= empty_inline('star', 'No reviews yet', 'Customers can review you after a completed booking. Great first jobs get you there fast.') ?>
      <?php else: ?>
        <div class="card-body" style="padding-top:4px;padding-bottom:4px">
          <?php foreach ($reviews as $r): ?>
            <div class="review">
              <div class="row row-between"><span class="semibold small"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></span><?= stars((float) $r['rating']) ?></div>
              <?php if ($r['comment']): ?><p class="small muted mt-1"><?= e(str_limit($r['comment'], 140)) ?></p><?php endif; ?>
              <?php if ($r['provider_reply']): ?>
                <div class="review-reply small"><strong>You replied:</strong> <?= e(str_limit($r['provider_reply'], 120)) ?></div>
              <?php else: ?>
                <form class="reply-inline mt-2" method="post" action="<?= e(url('/reviews/' . $r['id'] . '/reply')) ?>">
                  <?= csrf_field() ?><input type="hidden" name="return" value="dashboard">
                  <input class="input input-sm" name="reply" placeholder="Write a public reply…" maxlength="500" required aria-label="Reply to <?= e($r['first_name']) ?>">
                  <button class="btn btn-sm" type="submit">Reply</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
