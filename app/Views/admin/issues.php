<div class="page-title"><div><h1>Reported problems</h1><p class="muted">No-shows, poor work, payment disputes and safety reports from bookings. Oldest open reports first.</p></div></div>
<nav class="tabs">
  <?php foreach (['open' => 'Open', 'resolved' => 'Resolved'] as $k => $l): ?>
    <a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/admin/issues', ['tab' => $k])) ?>"><?= e($l) ?> <span class="n"><?= $counts[$k] ?></span></a>
  <?php endforeach; ?>
</nav>

<div class="stack mt-4" style="--gap:14px">
  <?php foreach ($rows as $i): ?>
    <article class="card card-body issue-card">
      <div class="row row-between row-top row-wrap" style="--gap:10px">
        <div class="grow" style="min-width:0">
          <div class="row row-wrap" style="--gap:8px">
            <span class="badge <?= $i['reason'] === 'safety' ? 'badge-red' : 'badge-amber' ?>"><?= e(IssueController::REASONS[$i['reason']] ?? $i['reason']) ?></span>
            <span class="tiny subtle"><?= e(time_ago($i['created_at'])) ?></span>
          </div>
          <h3 class="mt-2" style="font-size:1rem"><a href="<?= e(url('/bookings/' . $i['booking_id'])) ?>"><?= e($i['title']) ?></a> <span class="small subtle"><?= e(booking_ref((int) $i['booking_id'])) ?> · <?= e(ucfirst(str_replace('_', ' ', $i['booking_status']))) ?> · <?= money($i['amount']) ?></span></h3>
          <p class="small muted mt-1">
            Reported by <strong><?= e($i['r_first'] . ' ' . $i['r_last']) ?></strong> (<?= $i['r_role'] === 'provider' ? 'pro' : 'customer' ?>).
            Customer: <?= e($i['c_first'] . ' ' . $i['c_last']) ?> · Pro: <a href="<?= e(url('/providers/' . $i['provider_id'])) ?>"><?= e($i['p_first'] . ' ' . $i['p_last']) ?></a>
          </p>
        </div>
      </div>
      <blockquote class="issue-quote mt-3"><?= nl2br(e($i['details'])) ?></blockquote>
      <?php if ($i['status'] === 'resolved'): ?>
        <div class="alert alert-success mt-3"><?= icon('check-circle') ?><div><strong>Resolved <?= e(fmt_date($i['resolved_at'], 'M j, g:i A')) ?>:</strong> <?= e($i['admin_note']) ?></div></div>
      <?php else: ?>
        <form class="reply-inline mt-4" method="post" action="<?= e(url('/admin/issues/' . $i['id'] . '/resolve')) ?>">
          <?= csrf_field() ?>
          <input class="input" name="admin_note" maxlength="255" required placeholder="What you did, e.g. Contacted both sides; pro will return Saturday to finish." aria-label="Resolution note">
          <button class="btn btn-primary" type="submit"><?= icon('check') ?> Resolve</button>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <div class="card empty"><?= empty_art('check-circle') ?><h3><?= $tab === 'open' ? 'Nothing to handle' : 'No resolved reports yet' ?></h3><p><?= $tab === 'open' ? 'New reports from customers and pros will show up here.' : 'Reports you resolve are kept here for reference.' ?></p></div>
  <?php endif; ?>
</div>
<?= pagination($total, $perPage, $page) ?>
