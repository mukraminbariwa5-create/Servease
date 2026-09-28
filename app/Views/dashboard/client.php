<?php $u = user(); $hour = (int) date('G'); $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'); ?>
<div class="page-title">
  <div>
    <h1><?= $greet ?>, <?= e($u['first_name']) ?></h1>
    <p class="muted">Here's what's happening with your jobs and bookings.</p>
  </div>
  <div class="row">
    <a class="btn" href="<?= e(url('/providers')) ?>"><?= icon('search') ?> Find a pro</a>
    <a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
  </div>
</div>

<?php if (empty($u['email_verified_at'])): ?>
  <div class="alert alert-warning mb-6"><?= icon('mail') ?><div class="grow">Please confirm your email address to keep your account secure.</div>
    <form method="post" action="<?= e(url('/verify-email/resend')) ?>"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Resend link</button></form>
  </div>
<?php endif; ?>

<div class="stats">
  <div class="card stat"><div class="stat-top"><span class="stat-label">Active bookings</span><span class="stat-icon"><?= icon('calendar') ?></span></div><div class="stat-value"><?= $stats['active'] ?></div><div class="stat-sub">Requested, confirmed or in progress</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Open jobs</span><span class="stat-icon amber"><?= icon('briefcase') ?></span></div><div class="stat-value"><?= $stats['open_jobs'] ?></div><div class="stat-sub">Collecting quotes</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Completed</span><span class="stat-icon green"><?= icon('check-circle') ?></span></div><div class="stat-value"><?= $stats['completed'] ?></div><div class="stat-sub">Jobs done by pros</div></div>
  <div class="card stat"><div class="stat-top"><span class="stat-label">Paid to pros</span><span class="stat-icon violet"><?= icon('wallet') ?></span></div><div class="stat-value"><?= money($stats['spent']) ?></div><div class="stat-sub">Recorded payments</div></div>
</div>

<?php if ($toPay || $toReview): ?>
  <div class="grid grid-2 mt-6">
    <?php foreach ($toPay as $b): ?>
      <a class="alert alert-info" href="<?= e(url('/bookings/' . $b['id'] . '#payment')) ?>" style="color:var(--text)"><?= icon('wallet') ?><div class="grow"><strong>Payment due:</strong> <?= e($b['title']) ?>. <?= money($b['amount']) ?> to <?= e($b['p_first']) ?></div><?= icon('chevron-right') ?></a>
    <?php endforeach; ?>
    <?php foreach ($toReview as $b): ?>
      <a class="alert alert-warning" href="<?= e(url('/bookings/' . $b['id'] . '#review')) ?>" style="color:var(--text)"><?= icon('star') ?><div class="grow"><strong>How did <?= e($b['p_first']) ?> do?</strong> Review “<?= e($b['title']) ?>”</div><?= icon('chevron-right') ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid split mt-6" style="--cols:minmax(0,1.4fr) minmax(0,1fr);gap:20px">
  <section class="card">
    <div class="card-header"><h3>Upcoming bookings</h3><a class="small semibold" href="<?= e(url('/bookings')) ?>">View all</a></div>
    <?php if (!$upcoming): ?>
      <div class="empty"><div class="empty-icon"><?= icon('calendar') ?></div><h3>Nothing booked yet</h3><p>Find a pro and request a booking. It only takes a minute.</p><a class="btn btn-primary" href="<?= e(url('/providers')) ?>">Browse pros</a></div>
    <?php else: ?>
      <div class="list-divided">
        <?php foreach ($upcoming as $b): $o = ['id' => $b['o_id'], 'first_name' => $b['o_first'], 'last_name' => $b['o_last'], 'avatar' => $b['o_avatar']]; ?>
          <a class="list-item" href="<?= e(url('/bookings/' . $b['id'])) ?>">
            <?= avatar($o, 'md') ?>
            <div class="grow" style="min-width:0">
              <div class="list-title truncate"><?= e($b['title']) ?></div>
              <div class="list-sub"><?= e(full_name($o)) ?> · <?= e($b['scheduled_date'] ? fmt_date($b['scheduled_date'], 'D, M j') : 'Date TBD') ?></div>
            </div>
            <?= badge($b['status']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-header"><h3>Your open jobs</h3><a class="small semibold" href="<?= e(url('/my/jobs')) ?>">Manage</a></div>
    <?php if (!$jobs): ?>
      <div class="empty"><div class="empty-icon"><?= icon('briefcase') ?></div><h3>No open jobs</h3><p>Describe what you need and let pros send you quotes.</p><a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>">Post a job</a></div>
    <?php else: ?>
      <div class="list-divided">
        <?php foreach ($jobs as $j): ?>
          <a class="list-item" href="<?= e(url('/jobs/' . $j['id'])) ?>">
            <span class="cat-icon" style="width:40px;height:40px;flex-shrink:0"><?= icon($j['category_icon'] ?? 'briefcase') ?></span>
            <div class="grow" style="min-width:0">
              <div class="list-title truncate"><?= e($j['title']) ?></div>
              <div class="list-sub"><?= (int) $j['quotes_count'] ?> quote<?= (int) $j['quotes_count'] === 1 ? '' : 's' ?> · posted <?= e(time_ago($j['created_at'])) ?></div>
            </div>
            <?php if ((int) $j['quotes_count'] > 0): ?><span class="badge badge-green badge-plain">New quotes</span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($recommended): ?>
  <section class="mt-8">
    <div class="row row-between mb-4"><h2 style="font-size:1.25rem">Recommended pros<?= $u['city'] ? ' near ' . e($u['city']) : '' ?></h2><a class="small semibold" href="<?= e(url('/providers')) ?>">See more</a></div>
    <div class="grid grid-3">
      <?php foreach ($recommended as $p): ?><?= partial('provider-card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
