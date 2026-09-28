<?php
$max = max(1, max(array_column($chart, 'value')));
$statusColors = ['completed' => 'var(--green)', 'accepted' => 'var(--blue)', 'in_progress' => 'var(--violet)', 'requested' => 'var(--amber)', 'cancelled' => 'var(--text-3)', 'declined' => 'var(--border-strong)'];
$totalB = max(1, array_sum($byStatus));
?>
<div class="page-title">
  <div><h1>Overview</h1><p class="muted">How Fixoria is doing: <?= e(date('F Y')) ?>.</p></div>
  <?php if ($pendingCount): ?><a class="btn btn-primary" href="<?= e(url('/admin/verifications')) ?>"><?= icon('shield-check') ?> <?= $pendingCount ?> pro<?= $pendingCount === 1 ? '' : 's' ?> to verify</a><?php endif; ?>
</div>

<div class="stats">
  <div class="card stat"><div class="stat-top"><span class="stat-label">Users</span><span class="stat-icon"><?= icon('users') ?></span></div><div class="stat-value"><?= number_format($kpi['clients'] + $kpi['providers']) ?></div><div class="stat-sub"><?= $kpi['clients'] ?> clients · <?= $kpi['providers'] ?> pros · +<?= $kpi['new_users'] ?> this month</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Bookings</span><span class="stat-icon violet"><?= icon('calendar') ?></span></div><div class="stat-value"><?= number_format($kpi['bookings']) ?></div><div class="stat-sub"><?= $kpi['bookings_month'] ?> this month · <?= $kpi['completed'] ?> completed</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Payments recorded</span><span class="stat-icon green"><?= icon('wallet') ?></span></div><div class="stat-value"><?= money($kpi['volume']) ?></div><div class="stat-sub"><?= money($kpi['volume_month']) ?> this month</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Avg. rating</span><span class="stat-icon amber"><?= icon('star') ?></span></div><div class="stat-value"><?= number_format($kpi['rating'], 2) ?></div><div class="stat-sub"><?= $kpi['verified'] ?> verified pros · <?= $kpi['open_jobs'] ?> open jobs</div></div>
</div>

<div class="grid split mt-6" style="--cols:minmax(0,1.7fr) minmax(0,1fr);gap:20px">
  <section class="card card-body">
    <div class="row row-between"><h3>Bookings, last 30 days</h3><span class="small subtle"><?= array_sum(array_column($chart, 'value')) ?> total</span></div>
    <div class="chart mt-4" role="img" aria-label="Bar chart of bookings per day for the last 30 days">
      <?php foreach ($chart as $i => $c): ?>
        <div class="chart-col">
          <div class="chart-bar" style="height:<?= $c['value'] ? max(4, round($c['value'] / $max * 100)) : 1 ?>%" data-tip="<?= e($c['label'] . ': ' . $c['value']) ?>"></div>
          <span class="chart-lbl"><?= $i % 5 === 0 || $i === 29 ? e($c['short']) : '&nbsp;' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card card-body">
    <h3>Booking status</h3>
    <div class="donut-legend mt-4">
      <?php foreach ($statusColors as $s => $col): $n = $byStatus[$s] ?? 0; ?>
        <div>
          <div class="row row-between small"><span class="row" style="--gap:8px"><span class="legend-dot" style="background:<?= $col ?>"></span><?= e(ucwords(str_replace('_', ' ', $s === 'accepted' ? 'confirmed' : $s))) ?></span><span class="semibold"><?= $n ?></span></div>
          <div class="bar mt-1" style="height:6px"><span style="width:<?= round($n / $totalB * 100) ?>%;background:<?= $col ?>"></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="grid grid-3 mt-6" style="gap:20px">
  <section class="card">
    <div class="card-header"><h3>Top categories</h3></div>
    <div class="list-divided">
      <?php foreach ($topCategories as $c): ?>
        <div class="list-item"><span class="cat-icon" style="width:36px;height:36px"><?= icon($c['icon']) ?></span><div class="grow list-title"><?= e($c['name']) ?></div><span class="semibold"><?= (int) $c['n'] ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <div class="card-header"><h3>New users</h3><a class="small semibold" href="<?= e(url('/admin/users')) ?>">All</a></div>
    <div class="list-divided">
      <?php foreach ($recentUsers as $ru): ?>
        <div class="list-item"><?= avatar($ru, 'sm') ?><div class="grow" style="min-width:0"><div class="list-title truncate"><?= e(full_name($ru)) ?></div><div class="list-sub"><?= e(ucfirst($ru['role'])) ?> · <?= e(time_ago($ru['created_at'])) ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <div class="card-header"><h3>Recent bookings</h3><a class="small semibold" href="<?= e(url('/admin/bookings')) ?>">All</a></div>
    <div class="list-divided">
      <?php foreach ($recentBookings as $b): ?>
        <a class="list-item" href="<?= e(url('/bookings/' . $b['id'])) ?>"><div class="grow" style="min-width:0"><div class="list-title truncate"><?= e($b['title']) ?></div><div class="list-sub truncate"><?= e($b['c_first'] . ' → ' . $b['p_first']) ?> · <?= money($b['amount']) ?></div></div><?= badge($b['status']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>
