<?php
$other = $isPro ? $b['c_first'] : $b['p_first'];
$current = $b['scheduled_date'] ? RescheduleController::slotText($b['scheduled_date'], $b['scheduled_time'], (int) $b['duration_hours']) : 'Not set yet';
?>
<?php app_bar('New time', '/bookings/' . $b['id']); ?>
<div class="page-title">
  <div>
    <a class="small semibold hide-md" href="<?= e(url('/bookings/' . $b['id'])) ?>"><?= icon('arrow-left', 'icon-sm') ?> Back to booking</a>
    <h1 class="mt-2">Propose a new time</h1>
    <p class="muted"><?= e($b['title']) ?> · <?= e(booking_ref((int) $b['id'])) ?></p>
  </div>
</div>

<form class="form-card" method="post" action="<?= e(url('/bookings/' . $b['id'] . '/reschedule')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="resched-now">
    <span class="resched-ic"><?= icon('calendar') ?></span>
    <div class="grow"><small>Currently booked for</small><strong><?= e($current) ?></strong></div>
  </div>

  <section class="form-section">
    <div class="form-step"><span class="form-step-num"><?= icon('refresh', 'icon-sm') ?></span><div><h2>Pick a new day and time</h2><p>Only times <?= e($b['p_first']) ?> is free are open. The job still takes <?= e(Availability::hoursText(max(1, (int) $b['duration_hours']))) ?>. Nothing changes until <?= e($other) ?> accepts.</p></div></div>
    <?= partial('slot-picker', ['p' => $p, 'dates' => $dates, 'busy' => $busy, 'date' => (string) old('scheduled_date', ''), 'time' => (string) old('scheduled_time', ''), 'duration' => max(1, (int) $b['duration_hours'])]) ?>
  </section>

  <section class="form-section">
    <div class="field<?= invalid('note') ?>">
      <label class="label" for="note">Message for <?= e($other) ?> <span class="opt">(optional)</span></label>
      <textarea class="textarea" id="note" name="note" rows="2" maxlength="255" placeholder="e.g. Something came up that morning. Is the afternoon OK?"><?= e(old('note')) ?></textarea>
      <?= field_error('note') ?>
    </div>
  </section>

  <div class="form-actions">
    <a class="btn btn-ghost" href="<?= e(url('/bookings/' . $b['id'])) ?>">Keep current time</a>
    <button class="btn btn-primary btn-lg" type="submit"<?= $dates ? '' : ' disabled' ?>><?= icon('send') ?> Send new time</button>
  </div>
</form>
