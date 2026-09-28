<div class="page-title">
  <div><h1>Jobs</h1><p class="muted"><?= number_format($total) ?> job<?= $total === 1 ? '' : 's' ?> posted by clients</p></div>
  <form method="get" action="<?= e(url('/admin/jobs')) ?>">
    <select class="select" name="status" aria-label="Filter by status" data-autosubmit style="width:auto">
      <option value="">All statuses</option>
      <?php foreach (['open' => 'Open', 'assigned' => 'Assigned', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<div class="card table-wrap">
  <table class="table">
    <thead><tr><th>Job</th><th>Client</th><th>Category</th><th>City</th><th>Budget</th><th>Quotes</th><th>Posted</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $j): ?>
        <tr>
          <td><a href="<?= e(url('/jobs/' . $j['id'])) ?>"><?= e(str_limit($j['title'], 46)) ?></a></td>
          <td class="nowrap"><?= e($j['first_name'] . ' ' . $j['last_name']) ?></td>
          <td><?= e($j['category_name'] ?? '—') ?></td>
          <td><?= e($j['city']) ?></td>
          <td class="nowrap"><?= $j['budget_max'] ? money($j['budget_min']) . '–' . money($j['budget_max']) : '—' ?></td>
          <td><?= (int) $j['quotes_count'] ?></td>
          <td class="nowrap"><?= e(time_ago($j['created_at'])) ?></td>
          <td><?= badge($j['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="text-center muted" style="padding:40px">No jobs found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pagination($total, $perPage, $page) ?>
