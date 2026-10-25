<?php
/** Logo tile for a payment method. GCash uses its own mark; the rest are drawn here. */
$method = $method ?? 'cash';
?>
<span class="pay-badge pay-<?= e($method) ?>" aria-hidden="true">
  <?php if ($method === 'gcash'): ?><img src="<?= e(asset('img/pay/gcash.png')) ?>" alt="" width="40" height="40" loading="lazy">
  <?php elseif ($method === 'maya'): ?><span class="pay-word">maya</span>
  <?php elseif ($method === 'bank_transfer'): ?><?= icon('landmark') ?>
  <?php elseif ($method === 'paymongo'): ?><?= icon('credit-card') ?>
  <?php else: ?><?= icon('banknote') ?><?php endif; ?>
</span>
