<div class="page-title">
  <div><h1>Bookings</h1><p class="muted"><?= number_format($total) ?> booking<?= $total === 1 ? '' : 's' ?></p></div>
  <form method="get" action="<?= e(url('/admin/bookings')) ?>">
    <select class="select" name="status" aria-label="Filter by status" data-autosubmit style="width:auto">
      <option value="">All statuses</option>
      <?php foreach (['requested' => 'Requested', 'accepted' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'declined' => 'Declined'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<div class="card table-wrap">
  <table class="table">
    <thead><tr><th>Ref</th><th>Job</th><th>Client</th><th>Pro</th><th>Date</th><th>Amount</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $b): ?>
        <tr>
          <td class="nowrap"><a href="<?= e(url('/bookings/' . $b['id'])) ?>"><?= e(booking_ref((int) $b['id'])) ?></a></td>
          <td><?= e(str_limit($b['title'], 42)) ?></td>
          <td class="nowrap"><?= e($b['c_first'] . ' ' . $b['c_last']) ?></td>
          <td class="nowrap"><?= e($b['p_first'] . ' ' . $b['p_last']) ?></td>
          <td class="nowrap"><?= e(fmt_date($b['scheduled_date'] ?: $b['created_at'])) ?></td>
          <td class="nowrap semibold"><?= money($b['amount']) ?></td>
          <td><?= badge($b['payment_status']) ?></td>
          <td><?= badge($b['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="text-center muted" style="padding:40px">No bookings found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pagination($total, $perPage, $page) ?>
