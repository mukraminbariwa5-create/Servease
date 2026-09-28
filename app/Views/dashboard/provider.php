<?php
$u = user();
$done = count(array_filter($checklist, fn($c) => $c[1]));
$pct = (int) round($done / count($checklist) * 100);
$max = max(1, max(array_column($months, 'value')));
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

<?php foreach ($pendingPayments as $pp): ?>
  <a class="alert alert-warning mt-4" href="<?= e(url('/bookings/' . $pp['booking_id'] . '#payment')) ?>" style="color:var(--text)"><?= icon('wallet') ?><div class="grow"><strong>Confirm payment:</strong> client says they paid <?= money($pp['amount']) ?> via <?= e(payment_method_label($pp['method'])) ?> for “<?= e($pp['title']) ?>”.</div><?= icon('chevron-right') ?></a>
<?php endforeach; ?>

<div class="grid split mt-6" style="--cols:minmax(0,1.45fr) minmax(0,1fr);gap:20px;align-items:start">
  <div class="stack" style="--gap:20px">
    <section class="card">
      <div class="card-header"><h3>Requests &amp; upcoming jobs</h3><a class="small semibold" href="<?= e(url('/bookings')) ?>">All bookings</a></div>
      <?php if (!$requests): ?>
        <div class="empty"><div class="empty-icon"><?= icon('inbox') ?></div><h3>No active bookings</h3><p>Send quotes on open jobs to win new clients.</p><a class="btn btn-primary" href="<?= e(url('/jobs')) ?>">Browse jobs</a></div>
      <?php else: ?>
        <div class="list-divided">
          <?php foreach ($requests as $b): $o = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']]; ?>
            <a class="list-item" href="<?= e(url('/bookings/' . $b['id'])) ?>">
              <?= avatar($o, 'md') ?>
              <div class="grow" style="min-width:0">
                <div class="list-title truncate"><?= e($b['title']) ?></div>
                <div class="list-sub"><?= e(full_name($o)) ?> · <?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'D, M j') : 'Date TBD') ?> · <?= money($b['amount']) ?></div>
              </div>
              <?= $b['status'] === 'requested' ? '<span class="btn btn-sm btn-primary">Respond</span>' : badge($b['status']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-header"><h3>New jobs in <?= e($profile['category_name'] ?? 'your category') ?></h3><a class="small semibold" href="<?= e(url('/jobs', ['category' => $profile['category_slug'] ?? null])) ?>">Job board</a></div>
      <?php if (!$jobs): ?>
        <div class="card-body"><p class="muted small">No new jobs in your category right now. We'll notify you when clients post one.</p></div>
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
        <div class="bar mt-3"><span style="width:<?= $pct ?>%;background:var(--primary)"></span></div>
        <ul class="check-list" style="margin-top:16px">
          <?php foreach ($checklist as [$label, $ok, $href]): ?>
            <li><?= $ok ? icon('check-circle') : '<span style="width:18px;height:18px;border:2px solid var(--border-strong);border-radius:50%;flex-shrink:0;margin-top:2px"></span>' ?>
              <?= $ok ? '<span class="subtle" style="text-decoration:line-through">' . e($label) . '</span>' : '<a href="' . e(url($href)) . '">' . e($label) . '</a>' ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <section class="card card-body">
      <div class="row row-between"><h3>Earnings</h3><span class="small subtle">Last 6 months</span></div>
      <div class="chart" style="height:150px">
        <?php foreach ($months as $m): ?>
          <div class="chart-col">
            <div class="chart-bar" style="height:<?= max(2, round($m['value'] / $max * 100)) ?>%;max-width:34px" data-tip="<?= e($m['label'] . ': ' . money($m['value'])) ?>"></div>
            <span class="chart-lbl"><?= e($m['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card">
      <div class="card-header"><h3>Latest reviews</h3><a class="small semibold" href="<?= e(url('/providers/' . $u['id'] . '#reviews')) ?>">View all</a></div>
      <?php if (!$reviews): ?>
        <div class="card-body"><p class="muted small">Reviews from completed bookings will show up here.</p></div>
      <?php else: ?>
        <div class="card-body" style="padding-top:4px;padding-bottom:4px">
          <?php foreach ($reviews as $r): ?>
            <div class="review">
              <div class="row row-between"><span class="semibold small"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></span><?= stars((float) $r['rating']) ?></div>
              <?php if ($r['comment']): ?><p class="small muted mt-1"><?= e(str_limit($r['comment'], 140)) ?></p><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
