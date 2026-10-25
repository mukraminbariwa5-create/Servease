<?php
/**
 * Decorative illustrations made from the app's own objects: hang tags, a job ticket,
 * a pro's business card and rubber stamps. Drawn with HTML and CSS in a 300 × 330 box.
 * $kind: services | booking | verified | welcome | team
 */
$kind = $kind ?? 'welcome';
if ($kind === 'team') $kind = 'services';
?>
<div class="ca ca-<?= e($kind) ?>" aria-hidden="true">
  <span class="ca-pad"></span>
  <?php if ($kind === 'services'): ?>
    <span class="ca-rail"></span>
    <?php foreach ([['air-vent', 'Aircon', '₱800'], ['droplets', 'Plumbing', '₱450'], ['spray-can', 'Cleaning', '₱1,200']] as $n => [$ic, $name, $price]): ?>
      <span class="ca-hang ca-h<?= $n + 1 ?>">
        <span class="ca-hang-ic"><?= icon($ic) ?></span>
        <b><?= e($name) ?></b>
        <small>from <?= e($price) ?></small>
      </span>
    <?php endforeach; ?>
    <span class="ca-sticker">No booking fee</span>

  <?php elseif ($kind === 'booking'): ?>
    <span class="ca-ticket">
      <span class="ca-ticket-top"><small>Job ticket</small><b>SV-00128</b></span>
      <span class="ca-perf"></span>
      <span class="ca-line"><?= icon('calendar') ?>Sat, 10:00 AM</span>
      <span class="ca-line"><?= icon('air-vent') ?>Aircon cleaning</span>
      <span class="ca-line ca-line-price"><span class="ca-tag">₱1,200</span></span>
      <span class="ca-stamp">Booked</span>
    </span>

  <?php elseif ($kind === 'verified'): ?>
    <span class="ca-bcard ca-bcard-back"></span>
    <span class="ca-bcard">
      <span class="ca-bcard-band"><?= icon('spray-can') ?></span>
      <span class="ca-bcard-body">
        <span class="ca-face">AR</span>
        <b>Ana Reyes</b>
        <small>Deep cleaning · 6 yrs</small>
        <span class="ca-stars"><?= str_repeat(icon('star'), 5) ?></span>
      </span>
      <span class="ca-stamp ca-stamp-green">ID checked</span>
    </span>

  <?php else: ?>
    <span class="ca-stub">
      <small>Job ticket</small><b>SV-00001</b>
      <span class="ca-stub-perf"></span>
      <span class="ca-stamp ca-stamp-sm">Confirmed</span>
    </span>
    <span class="ca-bigtag">
      <small>Aircon · Plumbing · Cleaning</small>
      <b>from ₱350</b>
      <small>Pay after the job</small>
    </span>
  <?php endif; ?>
</div>
