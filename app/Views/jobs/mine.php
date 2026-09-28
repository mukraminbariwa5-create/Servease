<div class="page-title">
  <div><h1>My jobs</h1><p class="muted">Jobs you've posted and the quotes you've received.</p></div>
  <a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
</div>
<nav class="tabs" aria-label="Job status">
  <?php foreach (['open' => 'Open', 'assigned' => 'Hired', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label): ?>
    <a class="tab <?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/my/jobs', ['tab' => $k])) ?>"><?= $label ?> <span class="n"><?= $counts[$k] ?? 0 ?></span></a>
  <?php endforeach; ?>
</nav>
<?php if (!$jobs): ?>
  <div class="card empty">
    <div class="empty-icon"><?= icon('briefcase') ?></div>
    <h3>No <?= $tab === 'assigned' ? 'hired' : e($tab) ?> jobs</h3>
    <p><?= $tab === 'open' ? 'Post a job and pros near you will send quotes.' : 'Jobs will show up here as they move along.' ?></p>
    <?php if ($tab === 'open'): ?><a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>">Post a job</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="stack" style="--gap:14px">
    <?php foreach ($jobs as $j): ?><?= View::partial('jobs/_card', ['j' => $j]) ?><?php endforeach; ?>
  </div>
<?php endif; ?>
