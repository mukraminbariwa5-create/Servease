<?php $grand = array_sum(array_map(fn($m) => (float) $m['total'], $byMethod)) ?: 1; ?>
<div class="page-title"><div><h1>Payments</h1><p class="muted">Payments recorded between clients and pros. Fixoria doesn't hold funds.</p></div></div>
<?php if ($byMethod): ?>
  <div class="grid grid-4 mb-6">
    <?php foreach ($byMethod as $m): ?>
      <div class="card stat">
        <div class="stat-top"><span class="stat-label"><?= e(payment_method_label($m['method'])) ?></span><span class="small subtle"><?= round($m['total'] / $grand * 100) ?>%</span></div>
        <div class="stat-value" style="font-size:1.35rem"><?= money($m['total']) ?></div>
        <div class="stat-sub"><?= (int) $m['n'] ?> payment<?= (int) $m['n'] === 1 ? '' : 's' ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="card table-wrap">
  <table class="table">
    <thead><tr><th>Booking</th><th>From</th><th>To</th><th>Method</th><th>Reference</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><a href="<?= e(url('/bookings/' . $p['booking_id'])) ?>"><?= e(booking_ref((int) $p['booking_id'])) ?></a><div class="tiny subtle"><?= e(str_limit($p['title'], 30)) ?></div></td>
          <td class="nowrap"><?= e($p['c_first'] . ' ' . $p['c_last']) ?></td>
          <td class="nowrap"><?= e($p['p_first'] . ' ' . $p['p_last']) ?></td>
          <td><?= e(payment_method_label($p['method'])) ?><div class="tiny subtle"><?= $p['channel'] === 'online' ? 'Online' : 'Direct' ?></div></td>
          <td class="tiny"><?= e($p['reference'] ?: '—') ?></td>
          <td class="semibold nowrap"><?= money($p['amount'], true) ?></td>
          <td class="nowrap"><?= e(fmt_date($p['paid_at'] ?: $p['created_at'])) ?></td>
          <td><?= badge($p['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="text-center muted" style="padding:40px">No payments recorded yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pagination($total, $perPage, $page) ?>
