<?php
$u = user();
$selectedId = (string) old('service_id', $serviceId);
$selected = null;
foreach ($services as $s) if ((string) $s['id'] === $selectedId) $selected = $s;
$cur = config('app.currency');
$date = old('scheduled_date', date('Y-m-d', strtotime('+2 days')));
$time = old('scheduled_time', '09:00');
$morning = array_filter(BookingController::TIMES, fn($k) => (int) $k < 12, ARRAY_FILTER_USE_KEY);
$afternoon = array_diff_key(BookingController::TIMES, $morning);
?>
<section class="page-header page-header-soft">
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
                <input type="radio" name="service_id" value="<?= (int) $s['id'] ?>" data-title="<?= e($s['title']) ?>" data-price="<?= (int) $s['price'] ?>" data-label="<?= e($s['title']) ?>"<?= checked($selectedId === (string) $s['id']) ?> data-service>
                <span class="choice-body">
                  <span class="choice-icon"><?= icon($p['category_icon'] ?? 'wrench') ?></span>
                  <span class="grow"><span class="choice-title" style="display:block"><?= e($s['title']) ?></span><?php if ($s['description']): ?><span class="choice-desc"><?= e($s['description']) ?></span><?php endif; ?></span>
                  <span class="service-price"><?= $s['price_unit'] === 'starting' ? '<small>from</small> ' : '' ?><?= money($s['price']) ?><small><?= e(price_unit_label($s['price_unit']) === '+' ? '' : price_unit_label($s['price_unit'])) ?></small></span>
                </span>
              </label>
            <?php endforeach; ?>
            <label class="choice">
              <input type="radio" name="service_id" value="" data-label="Custom job"<?= checked($selectedId === '') ?> data-service>
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
        <div class="form-step"><span class="form-step-num">2</span><div><h2>When works for you?</h2><p><?= e($p['first_name']) ?> will confirm or suggest another time.</p></div></div>
        <div class="field<?= invalid('scheduled_date') ?>">
          <span class="label">Date</span>
          <div class="date-strip" role="group" aria-label="Pick a date">
            <?php for ($i = 1; $i <= 10; $i++): $ts = strtotime("+$i days"); $val = date('Y-m-d', $ts); ?>
              <button type="button" class="date-cell<?= $val === $date ? ' is-active' : '' ?>" data-fill="#scheduled_date" data-value="<?= $val ?>">
                <small><?= $i === 1 ? 'Tmrw' : date('D', $ts) ?></small><strong><?= date('j', $ts) ?></strong><small><?= date('M', $ts) ?></small>
              </button>
            <?php endfor; ?>
          </div>
          <div class="row mt-2" style="--gap:10px">
            <span class="help">Or pick another date:</span>
            <div class="input-icon" style="max-width:220px"><?= icon('calendar') ?><input class="input input-sm" id="scheduled_date" name="scheduled_date" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($date) ?>" required></div>
          </div>
          <?= field_error('scheduled_date') ?>
        </div>
        <fieldset class="fieldset field<?= invalid('scheduled_time') ?> mt-6">
          <legend class="label">Time</legend>
          <div class="time-groups">
            <div><span class="time-group-label"><?= icon('sun', 'icon-sm') ?> Morning</span>
              <div class="picks"><?php foreach ($morning as $v => $l): ?><label class="pick"><input type="radio" name="scheduled_time" value="<?= $v ?>" data-label="<?= $l ?>"<?= checked($time === $v) ?>><span><?= $l ?></span></label><?php endforeach; ?></div>
            </div>
            <div><span class="time-group-label"><?= icon('clock', 'icon-sm') ?> Afternoon</span>
              <div class="picks"><?php foreach ($afternoon as $v => $l): ?><label class="pick"><input type="radio" name="scheduled_time" value="<?= $v ?>" data-label="<?= $l ?>"<?= checked($time === $v) ?>><span><?= $l ?></span></label><?php endforeach; ?></div>
            </div>
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
            <label class="label" for="amount">Your budget</label>
            <div class="input-affix"><span class="affix"><?= e($cur) ?></span><input class="input" id="amount" name="amount" type="number" min="50" step="50" inputmode="numeric" value="<?= e(old('amount', (string) (int) ($selected['price'] ?? $p['hourly_rate'] * 2))) ?>" required></div>
            <?= field_error('amount') ?>
          </div>
        </div>
        <p class="help mt-2"><?= icon('info', 'icon-sm') ?> <?= e($p['first_name']) ?>'s rate starts at <?= money($p['hourly_rate']) ?>/hr. The final price is agreed before any work starts.</p>
      </section>

      <div class="form-actions">
        <span class="note"><?= icon('shield-check') ?> No payment now · Cancel any time before it starts</span>
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
          <div><dt><?= icon('map-pin') ?> City</dt><dd data-preview="city" data-empty="—"></dd></div>
        </dl>
        <div class="summary-total"><span>Your budget</span><strong data-preview="amount" data-format="money" data-empty="—"></strong></div>
      </div>
      <div class="card card-body">
        <h3 class="mb-4" style="font-size:1rem">What happens next</h3>
        <ol class="next-steps">
          <li><span>1</span><div><strong><?= e($p['first_name']) ?> confirms</strong><small>Accepts, or adjusts the time or price.</small></div></li>
          <li><span>2</span><div><strong>Chat about details</strong><small>Share photos and access notes.</small></div></li>
          <li><span>3</span><div><strong>Pay after the job</strong><small>Cash, GCash, Maya or bank transfer.</small></div></li>
        </ol>
      </div>
    </aside>
  </div>
</div>
<script>
  document.querySelectorAll('[data-service]').forEach(function (r) {
    r.addEventListener('change', function () {
      var title = document.getElementById('title'), amount = document.getElementById('amount');
      if (r.dataset.title) { title.value = r.dataset.title; title.dispatchEvent(new Event('input', { bubbles: true })); }
      if (r.dataset.price) { amount.value = r.dataset.price; amount.dispatchEvent(new Event('input', { bubbles: true })); }
    });
  });
</script>
