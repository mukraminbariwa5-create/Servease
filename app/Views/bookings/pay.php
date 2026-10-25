<?php
/**
 * Payment screen: one decision per screen, like a checkout. The customer picks how they
 * paid (or pays online when PayMongo is set up); a pro records a payment they received.
 */
$err = errors();
$default = (string) old('method', $online ? 'online' : 'gcash');
?>
<?php app_bar('Payment', '/bookings/' . $b['id']); ?>
<div class="pay-page">
  <div class="page-bar pay-bar-top hide-md">
    <a class="icon-btn icon-btn-outline" href="<?= e(url('/bookings/' . $b['id'])) ?>" aria-label="Back to booking"><?= icon('arrow-left') ?></a>
    <div class="grow">
      <h1>Payment</h1>
      <p class="muted small"><?= $isPro ? 'Choose how your customer paid you.' : 'Select the payment method you want to use.' ?></p>
    </div>
  </div>

  <div class="card pay-summary">
    <div class="pay-summary-row">
      <span class="grow">
        <span class="small subtle">SV-<?= str_pad((string) $b['id'], 5, '0', STR_PAD_LEFT) ?></span>
        <strong><?= e($b['title']) ?></strong>
        <small class="muted"><?= $isPro ? 'From ' . e($b['c_first'] . ' ' . mb_substr($b['c_last'], 0, 1) . '.') : 'To ' . e($b['p_first'] . ' ' . mb_substr($b['p_last'], 0, 1) . '.') ?></small>
      </span>
      <span class="pay-amount"><small>Amount</small><strong><?= money($b['amount']) ?></strong></span>
    </div>
    <p class="tiny subtle"><?= icon('shield-check', 'icon-sm') ?> No Servease fees. <?= $isPro ? 'You keep the full amount.' : e($b['p_first']) . ' gets the full amount.' ?></p>
  </div>

  <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/pay')) ?>" data-pay-form>
    <?= csrf_field() ?>

    <?php if ($online): ?>
      <h2 class="pay-group">Pay online</h2>
      <div class="pay-list" role="radiogroup" aria-label="Pay online">
        <label class="pay-row">
          <input type="radio" name="method" value="online"<?= checked($default === 'online') ?>>
          <?= partial('pay-logo', ['method' => 'paymongo']) ?>
          <span class="grow"><strong>GCash, Maya or card</strong><small>Secure checkout by PayMongo</small></span>
          <span class="pay-radio" aria-hidden="true"></span>
        </label>
      </div>
    <?php endif; ?>

    <h2 class="pay-group"><?= $isPro ? 'How were you paid?' : ($online ? 'Already paid your pro directly?' : 'How did you pay?') ?></h2>
    <div class="pay-list" role="radiogroup" aria-label="Payment method">
      <?php foreach (PaymentController::CHOICES as $key => [$label, $note]): ?>
        <label class="pay-row">
          <input type="radio" name="method" value="<?= $key ?>"<?= checked($default === $key) ?>>
          <?= partial('pay-logo', ['method' => $key]) ?>
          <span class="grow"><strong><?= e($label) ?></strong><small><?= e($note) ?></small></span>
          <span class="pay-radio" aria-hidden="true"></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($err['method'])): ?><div class="field-error mt-2" role="alert"><?= e($err['method']) ?></div><?php endif; ?>

    <div class="field mt-6<?= !empty($err['reference']) ? ' is-invalid' : '' ?>" data-pay-ref<?= in_array($default, ['online', 'cash'], true) ? ' hidden' : '' ?>>
      <label class="label" for="reference">Reference no.</label>
      <div class="input-icon"><?= icon('receipt') ?><input class="input" id="reference" name="reference" maxlength="120" value="<?= e(old('reference')) ?>" placeholder="e.g. 1234 567 890" autocomplete="off" aria-describedby="reference-help"></div>
      <?php if (!empty($err['reference'])): ?><div class="field-error" role="alert"><?= e($err['reference']) ?></div><?php endif; ?>
      <span class="help" id="reference-help">The reference number from your GCash, Maya or bank receipt, so you can both match the payment.</span>
    </div>

    <div class="pay-actions">
      <p class="small muted" data-pay-note><?= $default === 'online' ? 'You\'ll be taken to PayMongo to pay securely.' : ($isPro ? 'The booking will be marked as paid right away.' : e($b['p_first']) . ' will be asked to confirm they received it.') ?></p>
      <button class="btn btn-primary btn-lg btn-block" type="submit" data-pay-submit><?= $default === 'online' ? 'Continue to pay ' . money($b['amount']) : 'Continue' ?></button>
    </div>
  </form>
</div>
<script>
  (function () {
    var form = document.querySelector('[data-pay-form]'); if (!form) return;
    var ref = form.querySelector('[data-pay-ref]'), note = form.querySelector('[data-pay-note]'), btn = form.querySelector('[data-pay-submit]');
    var amount = <?= json_encode(money($b['amount'])) ?>, isPro = <?= $isPro ? 'true' : 'false' ?>, pro = <?= json_encode($b['p_first']) ?>;
    form.addEventListener('change', function (e) {
      if (e.target.name !== 'method') return;
      var m = e.target.value;
      ref.hidden = m === 'online' || m === 'cash';
      ref.querySelector('input').required = !ref.hidden;
      btn.textContent = m === 'online' ? 'Continue to pay ' + amount : 'Continue';
      note.textContent = m === 'online' ? 'You\'ll be taken to PayMongo to pay securely.'
        : isPro ? 'The booking will be marked as paid right away.' : pro + ' will be asked to confirm they received it.';
    });
  })();
</script>
