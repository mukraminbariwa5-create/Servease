<div class="page-title">
  <div><h1>Saved pros</h1><p class="muted">Pros you've saved to hire later.</p></div>
  <a class="btn" href="<?= e(url('/providers')) ?>"><?= icon('search') ?> Browse pros</a>
</div>
<?php if (!$rows): ?>
  <div class="card empty">
    <div class="empty-icon"><?= icon('heart') ?></div>
    <h3>No saved pros yet</h3>
    <p>Tap the heart on any pro's card to keep them here for later.</p>
    <a class="btn btn-primary" href="<?= e(url('/providers')) ?>">Find a pro</a>
  </div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($rows as $p): ?><?= partial('provider-card', ['p' => $p]) ?><?php endforeach; ?>
  </div>
<?php endif; ?>
