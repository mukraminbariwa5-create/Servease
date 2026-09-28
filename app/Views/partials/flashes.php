<?php
$flashes = take_flashes();
$icons = ['success' => 'check-circle', 'error' => 'alert', 'info' => 'info', 'warning' => 'alert'];
if (!$flashes) return;
?>
<div class="toasts" aria-live="polite">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> toast" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
      <?= icon($icons[$f['type']] ?? 'info') ?>
      <div class="grow"><?= !empty($f['html']) ? $f['message'] : e($f['message']) ?></div>
      <button class="alert-close" type="button" aria-label="Dismiss"><?= icon('x', 'icon-sm') ?></button>
    </div>
  <?php endforeach; ?>
</div>
