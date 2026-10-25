<?php
/**
 * Date strip + time picks for a pro's open slots.
 * Expects: $p (pro row with work hours), $dates (open Y-m-d list), $busy (date => ranges),
 *          $date, $time (current picks), $duration (hours), $withHours (show a length picker), $note (optional line).
 * Times that would overlap another booking or run past closing are disabled, live, as the date or length changes.
 */
$withHours = $withHours ?? false;
$duration = max(1, (int) ($duration ?? 2));
$allTimes = Availability::times($p, 1);
if (!in_array($date, $dates, true)) $date = $dates[0] ?? '';
$fits = fn(string $v, string $d) => Availability::fits($p, $v, $duration, $busy[$d] ?? []);
if ($time !== '' && $date !== '' && !$fits($time, $date)) $time = '';
$morning = array_filter($allTimes, fn($k) => (int) $k < 12, ARRAY_FILTER_USE_KEY);
$afternoon = array_diff_key($allTimes, $morning);
$pick = function (array $list) use ($time, $date, $fits) {
    $out = '';
    foreach ($list as $v => $l) {
        $out .= '<label class="pick"><input type="radio" name="scheduled_time" value="' . $v . '" data-label="' . $l . '"'
              . checked($time === $v) . ($date !== '' && $fits($v, $date) ? '' : ' disabled') . '><span>' . $l . '</span></label>';
    }
    return $out;
};
$uid = 'sp' . substr(md5((string) mt_rand()), 0, 6);
?>
<div class="slot-picker" id="<?= $uid ?>" data-slot-picker data-busy='<?= e(json_encode((object) $busy, JSON_HEX_APOS)) ?>'
     data-day-start="<?= Availability::hour(Availability::start($p)) ?>" data-day-end="<?= Availability::hour(Availability::end($p)) ?>" data-duration="<?= $duration ?>">
  <?php if (!$dates): ?>
    <div class="alert alert-warning"><?= icon('info') ?><div><?= e($p['first_name']) ?> has no open days in the next two months. Send a message to agree on a date.</div></div>
  <?php else: ?>
    <?php if (!empty($note)): ?><p class="help mb-4"><?= e($note) ?></p><?php endif; ?>
    <?php if ($withHours): ?>
      <div class="length-row mb-5">
        <span class="length-ic"><?= icon('clock') ?></span>
        <div class="grow"><strong>How long will it take?</strong><small>Your best guess. <?= e($p['first_name']) ?> can adjust it.</small></div>
        <div class="length-pick">
          <label class="sr-only" for="<?= $uid ?>-hours">Hours</label>
          <select class="select select-sm" id="<?= $uid ?>-hours" name="hours" data-slot-hours>
            <?php for ($h = 1; $h <= Availability::MAX_HOURS; $h++): ?><option value="<?= $h ?>"<?= selected($duration, $h) ?>><?= Availability::hoursText($h) ?></option><?php endfor; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>
    <div class="field<?= invalid('scheduled_date') ?>">
      <span class="label">Date</span>
      <input type="hidden" id="<?= $uid ?>-date" name="scheduled_date" value="<?= e($date) ?>" data-slot-date>
      <div class="date-scroll">
        <div class="date-strip" role="group" aria-label="Pick a date">
          <?php foreach ($dates as $val): $ts = strtotime($val); $full = !array_filter(array_keys($allTimes), fn($t) => Availability::fits($p, $t, 1, $busy[$val] ?? [])); ?>
            <button type="button" class="date-cell<?= $val === $date ? ' is-active' : '' ?><?= $full ? ' is-full' : '' ?>" data-fill="#<?= $uid ?>-date" data-value="<?= $val ?>"<?= $full ? ' disabled aria-label="' . e(date('D M j', $ts)) . ', fully booked"' : '' ?>>
              <small><?= $val === date('Y-m-d', strtotime('+1 day')) ? 'Tmrw' : date('D', $ts) ?></small><strong><?= date('j', $ts) ?></strong><small><?= $full ? 'Full' : date('M', $ts) ?></small>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?= field_error('scheduled_date') ?>
    </div>
    <fieldset class="fieldset field<?= invalid('scheduled_time') ?> mt-6">
      <legend class="label">Time</legend>
      <div class="time-groups">
        <?php if ($morning): ?><div><span class="time-group-label"><?= icon('sun', 'icon-sm') ?> Morning</span><div class="picks"><?= $pick($morning) ?></div></div><?php endif; ?>
        <?php if ($afternoon): ?><div><span class="time-group-label"><?= icon('clock', 'icon-sm') ?> Afternoon</span><div class="picks"><?= $pick($afternoon) ?></div></div><?php endif; ?>
      </div>
      <?= field_error('scheduled_time') ?>
    </fieldset>
  <?php endif; ?>
</div>
<script>
  (function () {
    var box = document.getElementById(<?= json_encode($uid) ?>);
    if (!box) return;
    var date = box.querySelector('[data-slot-date]'), hours = box.querySelector('[data-slot-hours]');
    if (!date) return;
    var busy = JSON.parse(box.dataset.busy || '{}'), s = +box.dataset.dayStart, e = +box.dataset.dayEnd;
    var refresh = function () {
      var len = hours ? +hours.value : +box.dataset.duration, ranges = busy[date.value] || [];
      box.querySelectorAll('input[name="scheduled_time"]').forEach(function (r) {
        var a = parseInt(r.value, 10);
        r.disabled = a < s || a + len > e || ranges.some(function (x) { return a < x[1] && x[0] < a + len; });
        if (r.disabled && r.checked) r.checked = false;
      });
    };
    date.addEventListener('change', refresh);
    if (hours) hours.addEventListener('change', refresh);
    refresh();
  })();
</script>
