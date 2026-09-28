<div class="page-title">
  <div><h1>My quotes</h1><p class="muted">Quotes you've sent on the job board.</p></div>
  <a class="btn btn-primary" href="<?= e(url('/jobs')) ?>"><?= icon('search') ?> Find work</a>
</div>
<?php if (!$quotes): ?>
  <div class="card empty">
    <div class="empty-icon"><?= icon('file-text') ?></div>
    <h3>No quotes yet</h3>
    <p>Browse open jobs in your category and send your first quote.</p>
    <a class="btn btn-primary" href="<?= e(url('/jobs')) ?>">Browse jobs</a>
  </div>
<?php else: ?>
  <div class="card table-wrap">
    <table class="table">
      <thead><tr><th>Job</th><th>Your quote</th><th>Status</th><th>Sent</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($quotes as $q): ?>
          <tr>
            <td><a class="semibold" href="<?= e(url('/jobs/' . $q['job_id'])) ?>" style="color:var(--text)"><?= e($q['title']) ?></a><div class="small subtle"><?= e($q['category_name']) ?> · <?= e($q['city']) ?></div></td>
            <td class="semibold nowrap"><?= money($q['amount']) ?></td>
            <td><?= badge($q['status']) ?></td>
            <td class="small muted nowrap"><?= e(time_ago($q['created_at'])) ?></td>
            <td class="text-right">
              <?php if ($q['booking_id']): ?>
                <a class="btn btn-sm btn-soft" href="<?= e(url('/bookings/' . $q['booking_id'])) ?>">View booking</a>
              <?php else: ?>
                <a class="btn btn-sm" href="<?= e(url('/jobs/' . $q['job_id'])) ?>">Open</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
