<?php
$u = user();
$selectedId = (string) old('service_id', $serviceId);
$selected = null;
foreach ($services as $s) if ((string) $s['id'] === $selectedId) $selected = $s;
if (!$selected && $selectedId !== '' && $services) $selectedId = '';
$cur = config('app.currency');
$unit = $selected['price_unit'] ?? '';
$hours = (int) old('hours', '2') ?: 2;
$duration = Availability::duration($selected, $hours, $p);
$date = (string) old('scheduled_date', '');
if (!in_array($date, $dates, true)) $date = $dates[0] ?? '';
$time = (string) old('scheduled_time', '');
$allTimes = Availability::times($p, 1);
$fits = fn(string $v, string $d) => Availability::fits($p, $v, $duration, $busy[$d] ?? []);
if ($time !== '' && !$fits($time, $date)) $time = '';
$morning = array_filter($allTimes, fn($k) => (int) $k < 12, ARRAY_FILTER_USE_KEY);
$afternoon = array_diff_key($allTimes, $morning);
$pick = function (array $list) use ($time, $date, $fits) {
    $out = '';
    foreach ($list as $v => $l) {
        $out .= '<label class="pick"><input type="radio" name="scheduled_time" value="' . $v . '" data-label="' . $l . '"'
              . checked($time === $v) . ($fits($v, $date) ? '' : ' disabled') . '><span>' . $l . '</span></label>';
    }
    return $out;
};
$dayLen = Availability::dayLength($p);
$amountLabel = ['starting' => 'Your estimate', '' => 'Your budget'][$unit] ?? 'Price';
?>
<?php app_bar('Book ' . $p['first_name'], '/providers/' . $p['id']); ?>
<section class="page-header page-header-soft hide-md">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/providers')) ?>">Pros</a><?= icon('chevron-right') ?>
      <a href="<?= e(url('/providers/' . $p['id'])) ?>"><?= e(full_name($p)) ?></a><?= icon('chevron-right') ?><span>Request a booking</span>
    </nav>
    <h1>Request a booking</h1>
    <p class="lead">Tell <?= e($p['first_name']) ?> what you need and when. Nothing is charged. <?= e($p['first_name']) ?> confirms the schedule and price first.</p>
  </div>
</section>

<div class="container page">
  <div class="layout-aside" style="--aside:340px">
    <form class="form-card" method="post" action="<?= e(url('/providers/' . $p['id'] . '/book')) ?>" novalidate data-live-preview>
      <?= csrf_field() ?>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">1</span><div><h2>What do you need?</h2><p>Pick one of <?= e($p['first_name']) ?>'s services or describe a custom job.</p></div></div>
        <?php if ($services): ?>
          <div class="choices choices-compact service-choices mb-4">
            <?php foreach ($services as $s): ?>
              <label class="choice">
                <input type="radio" name="service_id" value="<?= (int) $s['id'] ?>" data-title="<?= e($s['title']) ?>" data-price="<?= (int) $s['price'] ?>" data-unit="<?= e($s['price_unit']) ?>" data-duration="<?= (int) $s['duration_hours'] ?>" data-label="<?= e($s['title']) ?>"<?= checked($selectedId === (string) $s['id']) ?> data-service>
                <span class="choice-body">
                  <span class="choice-icon choice-icon-art" style="--tint:<?= category_tint($p['category_slug'] ?? null) ?>"><?= category_art($p['category_slug'] ?? null, $p['category_icon'] ?? 'wrench') ?></span>
                  <span class="grow"><span class="choice-title" style="display:block"><?= e($s['title']) ?></span><?php if ($s['description']): ?><span class="choice-desc"><?= e($s['description']) ?></span><?php endif; ?></span>
                  <span class="service-price"><?= $s['price_unit'] === 'starting' ? '<small>from</small> ' : '' ?><?= money($s['price']) ?><small><?= e(price_unit_label($s['price_unit']) === '+' ? '' : price_unit_label($s['price_unit'])) ?></small></span>
                </span>
              </label>
            <?php endforeach; ?>
            <label class="choice">
              <input type="radio" name="service_id" value="" data-unit="" data-label="Custom job"<?= checked($selectedId === '') ?> data-service>
              <span class="choice-body">
                <span class="choice-icon"><?= icon('pencil') ?></span>
                <span class="grow"><span class="choice-title" style="display:block">Something else</span><span class="choice-desc">Describe a custom job below</span></span>
              </span>
            </label>
          </div>
        <?php endif; ?>
        <div class="field<?= invalid('title') ?>">
          <div class="label-row"><label class="label" for="title">Job title</label><span class="counter"></span></div>
          <input class="input" id="title" name="title" maxlength="140" data-count data-min="5" value="<?= e(old('title', $selected['title'] ?? '')) ?>" placeholder="e.g. Fix leaking kitchen sink" required>
          <?= field_error('title') ?>
        </div>
        <div class="field<?= invalid('details') ?>">
          <div class="label-row"><label class="label" for="details">Details</label><span class="counter"></span></div>
          <textarea class="textarea" id="details" name="details" rows="4" maxlength="3000" data-count data-min="10" placeholder="Describe the problem, sizes, brands and access, so <?= e($p['first_name']) ?> brings the right tools." required><?= e(old('details')) ?></textarea>
          <?= field_error('details') ?>
        </div>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">2</span><div><h2>When works for you?</h2><p><?= e($p['first_name']) ?> works <?= e(preg_replace('/^Every day/', 'every day', Availability::summary($p))) ?>. Times that are taken or too late for this job are greyed out.</p></div></div>
        <div class="length-row mb-5" data-length>
          <span class="length-ic"><?= icon('clock') ?></span>
          <div class="grow">
            <strong data-length-title><?= $unit === 'day' ? 'Full day' : ($unit === 'fixed' || $unit === 'starting' ? 'Takes about ' . Availability::hoursText($duration) : 'How long will it take?') ?></strong>
            <small data-length-note><?= $unit === 'day' ? e(Availability::label(Availability::start($p)) . ' – ' . Availability::label(Availability::end($p))) : ($unit === 'fixed' || $unit === 'starting' ? 'Set by ' . e($p['first_name']) . ' for this service' : 'Your best guess. ' . e($p['first_name']) . ' can adjust it.') ?></small>
          </div>
          <div class="length-pick"<?= in_array($unit, ['fixed', 'starting', 'day'], true) ? ' hidden' : '' ?> data-length-pick>
            <label class="sr-only" for="hours">Hours</label>
            <select class="select select-sm" id="hours" name="hours">
              <?php for ($h = 1; $h <= Availability::MAX_HOURS; $h++): ?><option value="<?= $h ?>"<?= selected($hours, $h) ?>><?= Availability::hoursText($h) ?></option><?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="field<?= invalid('scheduled_date') ?>">
          <span class="label">Date</span>
          <input type="hidden" id="scheduled_date" name="scheduled_date" value="<?= e($date) ?>">
          <div class="date-scroll" data-date-scroll>
          <button class="date-nav date-nav-prev hide-md" type="button" data-date-nav="-1" aria-label="Earlier dates" disabled><?= icon('chevron-left') ?></button>
          <div class="date-strip" role="group" aria-label="Pick a date">
            <?php foreach ($dates as $i => $val): $ts = strtotime($val); $full = !array_filter(array_keys($allTimes), fn($t) => Availability::fits($p, $t, 1, $busy[$val] ?? [])); ?>
              <button type="button" class="date-cell<?= $val === $date ? ' is-active' : '' ?><?= $full ? ' is-full' : '' ?>" data-fill="#scheduled_date" data-value="<?= $val ?>"<?= $full ? ' disabled aria-label="' . e(date('D M j', $ts)) . ', fully booked"' : '' ?>>
                <small><?= $val === date('Y-m-d', strtotime('+1 day')) ? 'Tmrw' : date('D', $ts) ?></small><strong><?= date('j', $ts) ?></strong><small><?= $full ? 'Full' : date('M', $ts) ?></small>
              </button>
            <?php endforeach; ?>
          </div>
          <button class="date-nav date-nav-next hide-md" type="button" data-date-nav="1" aria-label="Later dates"><?= icon('chevron-right') ?></button>
          </div>
          <?= field_error('scheduled_date') ?>
        </div>
        <fieldset class="fieldset field<?= invalid('scheduled_time') ?> mt-6" data-busy='<?= e(json_encode((object) $busy, JSON_HEX_APOS)) ?>' data-day-start="<?= Availability::hour(Availability::start($p)) ?>" data-day-end="<?= Availability::hour(Availability::end($p)) ?>" data-day-len="<?= $dayLen ?>">
          <legend class="label">Time</legend>
          <div class="time-groups">
            <?php if ($morning): ?>
            <div><span class="time-group-label"><?= icon('sun', 'icon-sm') ?> Morning</span>
              <div class="picks"><?= $pick($morning) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($afternoon): ?>
            <div><span class="time-group-label"><?= icon('clock', 'icon-sm') ?> Afternoon</span>
              <div class="picks"><?= $pick($afternoon) ?></div>
            </div>
            <?php endif; ?>
          </div>
          <?= field_error('scheduled_time') ?>
        </fieldset>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">3</span><div><h2>Where and how much?</h2><p>Your address is only shared with <?= e($p['first_name']) ?> for this booking.</p></div></div>
        <div class="field<?= invalid('address') ?>">
          <label class="label" for="address">Address</label>
          <div class="input-icon"><?= icon('home') ?><input class="input" id="address" name="address" value="<?= e(old('address')) ?>" placeholder="House/unit no., street, barangay" autocomplete="street-address" required></div>
          <?= field_error('address') ?>
        </div>
        <div class="form-row mt-5">
          <div class="field<?= invalid('city') ?>">
            <label class="label" for="city">City</label>
            <div class="input-icon"><?= icon('map-pin') ?>
              <select class="select" id="city" name="city" required>
                <?php foreach (cities() as $c): ?><option<?= selected(old('city', $u['city'] ?? ''), $c) ?>><?= e($c) ?></option><?php endforeach; ?>
              </select>
            </div>
            <?= field_error('city') ?>
          </div>
          <div class="field<?= invalid('amount') ?>">
            <span class="label" id="amount-label" data-amount-label><?= e($amountLabel) ?></span>
            <div class="price-total"<?= in_array($unit, ['fixed', 'hour', 'day'], true) ? '' : ' hidden' ?> data-price-total>
              <strong data-price-total-value><?= money($unit === 'hour' ? $selected['price'] * $duration : ($selected['price'] ?? 0)) ?></strong>
              <small data-price-total-note><?= $unit === 'hour' ? e(money($selected['price']) . ' × ' . Availability::hoursText($duration)) : ($unit === 'day' ? 'For the full day' : 'Fixed price') ?></small>
            </div>
            <div class="input-affix"<?= in_array($unit, ['fixed', 'hour', 'day'], true) ? ' hidden' : '' ?> data-price-input><span class="affix"><?= e($cur) ?></span><input class="input" id="amount" name="amount" type="number" min="50" step="50" inputmode="numeric" aria-labelledby="amount-label" value="<?= e(old('amount', (string) (int) ($selected['price'] ?? $p['hourly_rate'] * 2))) ?>"></div>
            <?= field_error('amount') ?>
          </div>
        </div>
        <p class="help mt-2" data-price-help><?= icon('info', 'icon-sm') ?> <span><?= match ($unit) {
            'fixed' => e($p['first_name']) . ' confirms the booking at this price.',
            'starting' => 'This service starts at ' . money($selected['price']) . '. ' . e($p['first_name']) . ' confirms the final price after seeing the job.',
            'hour', 'day' => 'Estimated from ' . e($p['first_name']) . '\'s rate. The final price is agreed before any work starts.',
            default => e($p['first_name']) . '\'s rate starts at ' . money($p['hourly_rate']) . '/hr. The final price is agreed before any work starts.',
        } ?></span></p>
      </section>

      <div class="form-actions">
        <span class="note"><?= icon('shield-check') ?> Nothing to pay now. Cancel any time before it starts.</span>
        <button class="btn btn-primary btn-lg" type="submit"><?= icon('send') ?> Send request</button>
      </div>
    </form>

    <aside class="sticky stack" style="--gap:16px">
      <div class="card summary-card">
        <div class="summary-pro">
          <?= avatar($p, 'md') ?>
          <div class="grow" style="min-width:0">
            <div class="row" style="--gap:6px"><strong class="truncate"><?= e(full_name($p)) ?></strong><?php if ($p['verification_status'] === 'verified'): ?><span class="verified"><?= icon('badge-check', 'icon-sm') ?></span><?php endif; ?></div>
            <div class="small muted"><?= e($p['category_name']) ?> · <?= $p['rating_count'] ? '★ ' . number_format((float) $p['rating_avg'], 1) . ' (' . (int) $p['rating_count'] . ')' : 'New pro' ?></div>
          </div>
        </div>
        <div class="preview-label" style="padding:16px 20px 0;margin:0"><span class="live-dot"></span> Your request</div>
        <dl class="summary-list">
          <div><dt><?= icon('wrench') ?> Job</dt><dd data-preview="title" data-empty="Not set yet"></dd></div>
          <div><dt><?= icon('calendar') ?> Date</dt><dd data-preview="scheduled_date" data-format="date" data-empty="Pick a date"></dd></div>
          <div><dt><?= icon('clock') ?> Time</dt><dd data-preview="scheduled_time_label" data-empty="Pick a time"></dd></div>
          <div><dt><?= icon('layers') ?> Length</dt><dd data-summary-length><?= $unit === 'day' ? 'Full day' : Availability::hoursText($duration) ?></dd></div>
          <div><dt><?= icon('map-pin') ?> City</dt><dd data-preview="city" data-empty="—"></dd></div>
        </dl>
        <div class="summary-total"><span data-summary-amount-label><?= e($amountLabel) ?></span><strong data-preview="amount" data-format="money" data-empty="—"></strong></div>
      </div>
      <div class="card card-body">
        <h3 class="mb-4" style="font-size:1rem">What happens next</h3>
        <ol class="next-steps">
          <li><span>1</span><div><strong><?= e($p['first_name']) ?> confirms</strong><small>Accepts, or adjusts the time or price.</small></div></li>
          <li><span>2</span><div><strong>Chat about details</strong><small>Share photos and access notes.</small></div></li>
          <li><span>3</span><div><strong>Pay after the job</strong><small>Directly to your pro: GCash, Maya, bank or cash.</small></div></li>
        </ol>
      </div>
    </aside>
  </div>
</div>
<script>
  /* Booking: the chosen service decides how long the job takes and how it's priced;
     start times that would overlap another booking or run past closing are closed. */
  (function () {
    var form = document.querySelector('form[data-live-preview]');
    var box = document.querySelector('[data-busy]'), date = document.getElementById('scheduled_date');
    var hours = document.getElementById('hours'), amount = document.getElementById('amount'), title = document.getElementById('title');
    if (!form || !box || !date) return;
    var busy = JSON.parse(box.dataset.busy || '{}');
    var dayStart = +box.dataset.dayStart, dayEnd = +box.dataset.dayEnd, dayLen = +box.dataset.dayLen;
    var pro = <?= json_encode($p['first_name']) ?>;
    var peso = function (n) { return '₱' + Math.round(n).toLocaleString('en-PH'); };
    var hrs = function (n) { return n + ' hour' + (n === 1 ? '' : 's'); };
    var $ = function (sel) { return document.querySelector(sel); };
    var text = function (sel, t) { var el = $(sel); if (el) el.textContent = t; };

    var current = function () {
      var r = form.querySelector('input[name="service_id"]:checked');
      return r ? { unit: r.dataset.unit || '', price: +(r.dataset.price || 0), duration: +(r.dataset.duration || 1) } : { unit: '', price: 0, duration: 1 };
    };
    var length = function (svc) {
      if (svc.unit === 'fixed' || svc.unit === 'starting') return Math.max(1, svc.duration);
      if (svc.unit === 'day') return dayLen;
      return +(hours && hours.value || 2);
    };
    var fits = function (start, len, ranges) {
      if (start < dayStart || start + len > dayEnd) return false;
      return !ranges.some(function (r) { return start < r[1] && r[0] < start + len; });
    };

    var slots = function () {
      var len = length(current()), ranges = busy[date.value] || [];
      box.querySelectorAll('input[name="scheduled_time"]').forEach(function (r) {
        r.disabled = !fits(parseInt(r.value, 10), len, ranges);
        if (r.disabled && r.checked) { r.checked = false; r.dispatchEvent(new Event('change', { bubbles: true })); }
      });
    };

    var price = function () {
      var svc = current(), len = length(svc), fixedTotal = svc.unit === 'fixed' || svc.unit === 'hour' || svc.unit === 'day';
      $('[data-length-pick]').hidden = !(svc.unit === '' || svc.unit === 'hour');
      text('[data-length-title]', svc.unit === 'day' ? 'Full day' : (svc.unit === 'fixed' || svc.unit === 'starting') ? 'Takes about ' + hrs(len) : 'How long will it take?');
      text('[data-length-note]', svc.unit === 'day' ? 'The whole working day' : (svc.unit === 'fixed' || svc.unit === 'starting') ? 'Set by ' + pro + ' for this service' : 'Your best guess. ' + pro + ' can adjust it.');
      text('[data-summary-length]', svc.unit === 'day' ? 'Full day' : hrs(len));

      var label = svc.unit === 'starting' ? 'Your estimate' : svc.unit === '' ? 'Your budget' : 'Price';
      text('[data-amount-label]', label); text('[data-summary-amount-label]', label);
      $('[data-price-total]').hidden = !fixedTotal;
      $('[data-price-input]').hidden = fixedTotal;
      if (fixedTotal) {
        var total = svc.unit === 'hour' ? svc.price * len : svc.price;
        amount.value = total;
        text('[data-price-total-value]', peso(total));
        text('[data-price-total-note]', svc.unit === 'hour' ? peso(svc.price) + ' × ' + hrs(len) : svc.unit === 'day' ? 'For the full day' : 'Fixed price');
      } else if (svc.unit === 'starting') {
        amount.min = svc.price;
        if (+amount.value < svc.price) amount.value = svc.price;
      } else {
        amount.min = 50;
      }
      text('[data-price-help] span', svc.unit === 'fixed' ? pro + ' confirms the booking at this price.'
        : svc.unit === 'starting' ? 'This service starts at ' + peso(svc.price) + '. ' + pro + ' confirms the final price after seeing the job.'
        : svc.unit ? 'Estimated from ' + pro + '\'s rate. The final price is agreed before any work starts.'
        : 'Describe the job and set a budget. The final price is agreed before any work starts.');
      amount.dispatchEvent(new Event('input', { bubbles: true }));
      slots();
    };

    form.querySelectorAll('[data-service]').forEach(function (r) {
      r.addEventListener('change', function () {
        if (r.dataset.title) { title.value = r.dataset.title; title.dispatchEvent(new Event('input', { bubbles: true })); }
        if (r.dataset.price && r.dataset.unit === 'starting') amount.value = r.dataset.price;
        price();
      });
    });
    if (hours) hours.addEventListener('change', price);
    date.addEventListener('change', slots);
    price();

    /* Date strip arrows (laptop): scroll a page of dates and show where the edges are */
    var wrap = $('[data-date-scroll]'), strip = wrap && wrap.querySelector('.date-strip');
    if (strip) {
      var navs = wrap.querySelectorAll('[data-date-nav]');
      var edges = function () {
        var max = strip.scrollWidth - strip.clientWidth - 2;
        navs[0].disabled = strip.scrollLeft <= 2;
        navs[1].disabled = strip.scrollLeft >= max;
        wrap.classList.toggle('at-start', strip.scrollLeft <= 2);
        wrap.classList.toggle('at-end', strip.scrollLeft >= max);
      };
      navs.forEach(function (b) { b.addEventListener('click', function () { strip.scrollBy({ left: +b.dataset.dateNav * strip.clientWidth * 0.8, behavior: 'smooth' }); }); });
      strip.addEventListener('scroll', edges, { passive: true });
      window.addEventListener('resize', edges);
      var on = strip.querySelector('.is-active');
      if (on && on.offsetLeft > strip.clientWidth) strip.scrollLeft = on.offsetLeft - 8;
      edges();
    }
  })();
</script>
