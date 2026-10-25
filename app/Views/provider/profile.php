<?php
$err = errors();
$f = fn($k) => old($k, $p[$k] ?? '');
$vs = $p['verification_status'];
$units = ['fixed' => 'Fixed price', 'starting' => 'Starting at', 'hour' => 'Per hour', 'day' => 'Per day'];
$fe = function (string $k) use ($err) { return isset($err[$k]) ? '<span class="field-error">' . e($err[$k]) . '</span>' : ''; };
?>
<?php app_bar('Pro profile', '/account');
$vsLabel = ['verified' => 'ID verified', 'pending' => 'Verification in review', 'rejected' => 'Verification needs another look', 'unverified' => 'Not verified yet'][$vs] ?? '';
page_hero([
    'crumbs' => [['Dashboard', '/dashboard'], ['Pro profile', null]], 'narrow' => true,
    'title' => 'Pro profile', 'lead' => 'This is what clients see when they find you. A complete profile gets more bookings.',
    'stats' => [[$vs === 'verified' ? 'badge-check' : 'shield', e($vsLabel)], ['tag', '<strong>' . count($services) . '</strong> service' . (count($services) === 1 ? '' : 's')], ['image', '<strong>' . count($portfolio) . '</strong> work photo' . (count($portfolio) === 1 ? '' : 's')]],
    'actions' => '<a class="btn" href="' . e(url('/providers/' . $u['id'])) . '">' . icon('eye') . ' Preview public profile</a>',
]); ?>
<div class="page-title">
  <div class="app-dup"><h1>Pro profile</h1><p class="muted">This is what clients see when they find you. A complete profile gets more bookings.</p></div>
  <a class="btn" href="<?= e(url('/providers/' . $u['id'])) ?>"><?= icon('eye') ?> Preview public profile</a>
</div>

<div class="stack" style="--gap:20px;max-width:860px">
  <section class="card" id="photo">
    <div class="card-header"><h3><?= icon('user') ?> Your photo</h3><?= $u['avatar'] ? '<span class="badge badge-green">Added</span>' : '<span class="badge badge-amber">Needed</span>' ?></div>
    <div class="card-body photo-row">
      <?= avatar($u, 'xl') ?>
      <div class="grow">
        <p class="muted">Customers book pros they can see. Use a clear, friendly photo of your face with good light. No logos or group photos. You need one before you can get verified.</p>
        <form class="row row-wrap mt-4" method="post" action="<?= e(url('/settings/avatar')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?><input type="hidden" name="return" value="/provider/profile">
          <label class="btn btn-primary"><?= icon('upload') ?> <?= $u['avatar'] ? 'Change photo' : 'Upload photo' ?>
            <input class="sr-only" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required onchange="this.form.submit()">
          </label>
          <noscript><button class="btn" type="submit">Save</button></noscript>
          <span class="help">JPG, PNG or WebP, up to 3 MB</span>
        </form>
        <?= $fe('avatar') ?>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3>About your business</h3></div>
    <form method="post" action="<?= e(url('/provider/profile')) ?>" data-savebar>
      <?= csrf_field() ?>
      <div class="card-body">
      <div class="form-row">
        <div class="field<?= isset($err['category_id']) ? ' is-invalid' : '' ?>">
          <label class="label" for="category_id">Main service</label>
          <div class="input-icon"><?= icon('tag') ?>
            <select class="select" id="category_id" name="category_id" required>
              <option value="">Choose a category</option>
              <?php foreach (categories() as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) $f('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div><?= $fe('category_id') ?>
        </div>
        <div class="field<?= isset($err['years_experience']) ? ' is-invalid' : '' ?>">
          <label class="label" for="years_experience">Years of experience</label>
          <div class="input-affix"><span class="affix"><?= icon('briefcase', 'icon-sm') ?></span><input class="input" id="years_experience" name="years_experience" type="number" min="0" max="60" inputmode="numeric" value="<?= e($f('years_experience')) ?>" required><span class="suffix">years</span></div><?= $fe('years_experience') ?>
        </div>
      </div>
      <div class="field<?= isset($err['headline']) ? ' is-invalid' : '' ?> mt-5">
        <div class="label-row"><label class="label" for="headline">Headline</label><span class="counter"></span></div>
        <input class="input" id="headline" name="headline" maxlength="120" data-count value="<?= e($f('headline')) ?>" placeholder="e.g. Licensed plumber, leaks, clogs & installations" required>
        <?= $fe('headline') ?: '<span class="help">One line that tells clients what you do best. It appears on your card in search.</span>' ?>
      </div>
      <div class="field<?= isset($err['bio']) ? ' is-invalid' : '' ?>">
        <div class="label-row"><label class="label" for="bio">About you</label><span class="counter"></span></div>
        <textarea class="textarea" id="bio" name="bio" maxlength="3000" data-count data-min="40" rows="6" required placeholder="Your experience, the kind of jobs you take, tools you bring, warranty…"><?= e($f('bio')) ?></textarea>
        <?= $fe('bio') ?>
      </div>
      <div class="form-row mt-5">
        <div class="field<?= isset($err['city']) ? ' is-invalid' : '' ?>">
          <label class="label" for="city">Based in</label>
          <div class="input-icon"><?= icon('map-pin') ?><select class="select" id="city" name="city" required><option value="">Select a city</option><?php foreach (cities() as $c): ?><option <?= $f('city') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div><?= $fe('city') ?>
        </div>
        <div class="field">
          <label class="label" for="service_area">Service area <span class="opt">optional</span></label>
          <div class="input-icon"><?= icon('layers') ?><input class="input" id="service_area" name="service_area" maxlength="160" value="<?= e($f('service_area')) ?>" placeholder="e.g. Quezon City, Marikina, San Juan"></div>
        </div>
      </div>
      <div class="form-row mt-5">
        <div class="field<?= isset($err['hourly_rate']) ? ' is-invalid' : '' ?>">
          <label class="label" for="hourly_rate">Starting rate</label>
          <div class="input-affix"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input" id="hourly_rate" name="hourly_rate" type="number" min="50" step="50" inputmode="numeric" value="<?= e($f('hourly_rate') !== '' ? (int) $f('hourly_rate') : '') ?>" required><span class="suffix">/ hour</span></div><?= $fe('hourly_rate') ?>
        </div>
        <div class="field">
          <label class="label" for="skills">Skills <span class="opt">comma-separated</span></label>
          <div class="input-icon"><?= icon('tag') ?><input class="input" id="skills" name="skills" maxlength="255" value="<?= e($f('skills')) ?>" placeholder="Leak repair, Pipe installation, Water heaters" data-skill-input></div>
        </div>
      </div>
      <div class="picks mt-3" data-skill-preview aria-hidden="true"></div>
      </div>
      <div class="card-footer row row-between">
        <span class="small muted"><?= icon('eye', 'icon-sm') ?> Changes appear on your public profile right away.</span>
        <button class="btn btn-primary" type="submit">Save profile</button>
      </div>
    </form>
    <script>
      (function () {
        var i = document.querySelector('[data-skill-input]'), out = document.querySelector('[data-skill-preview]');
        if (!i) return;
        var draw = function () {
          out.innerHTML = '';
          i.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean).slice(0, 12).forEach(function (s) {
            var c = document.createElement('span'); c.className = 'chip'; c.textContent = s; out.appendChild(c);
          });
        };
        i.addEventListener('input', draw); draw();
      })();
    </script>
  </section>

  <?php
    $wDays = array_map('intval', (array) old('work_days', Availability::days($p)));
    $wStart = (string) old('work_start', Availability::start($p));
    $wEnd = (string) old('work_end', Availability::end($p));
  ?>
  <section class="card" id="hours">
    <div class="card-header"><h3><?= icon('clock') ?> Working hours</h3><span class="small subtle"><?= e(Availability::summary($p)) ?></span></div>
    <form method="post" action="<?= e(url('/provider/hours')) ?>">
      <?= csrf_field() ?>
      <div class="card-body">
        <p class="small muted mb-4">Customers can only request times inside your hours, and a time that's already booked is closed for everyone else.</p>
        <fieldset class="fieldset field<?= isset($err['work_days']) ? ' is-invalid' : '' ?>">
          <legend class="label">Days you work</legend>
          <div class="picks day-picks">
            <?php foreach (Availability::DAYS as $n => $l): ?>
              <label class="pick"><input type="checkbox" name="work_days[]" value="<?= $n ?>"<?= checked(in_array($n, $wDays, true)) ?>><span><?= $l ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= $fe('work_days') ?>
        </fieldset>
        <div class="form-row mt-5">
          <div class="field">
            <label class="label" for="work_start">Start of day</label>
            <div class="input-icon"><?= icon('sun') ?><select class="select" id="work_start" name="work_start">
              <?php foreach (Availability::HOURS as $h): ?><option value="<?= $h ?>"<?= selected($wStart, $h) ?>><?= Availability::label($h) ?></option><?php endforeach; ?>
            </select></div>
          </div>
          <div class="field<?= isset($err['work_end']) ? ' is-invalid' : '' ?>">
            <label class="label" for="work_end">End of day</label>
            <div class="input-icon"><?= icon('moon') ?><select class="select" id="work_end" name="work_end">
              <?php foreach (Availability::HOURS as $h): ?><option value="<?= $h ?>"<?= selected($wEnd, $h) ?>><?= Availability::label($h) ?></option><?php endforeach; ?>
            </select></div>
            <?= $fe('work_end') ?>
          </div>
        </div>
      </div>
      <div class="card-footer row row-between">
        <span class="small muted"><?= icon('calendar', 'icon-sm') ?> Weekly hours. Block single dates below.</span>
        <button class="btn btn-primary" type="submit">Save hours</button>
      </div>
    </form>
    <div class="card-body daysoff" id="days-off">
      <div class="head-row mb-3"><h4>Days off</h4><span class="small subtle">Holidays, trips or sick days</span></div>
      <?php if ($daysOff): ?>
        <div class="row row-wrap mb-4" style="--gap:8px">
          <?php foreach ($daysOff as $d): ?>
            <form method="post" action="<?= e(url('/provider/days-off/' . $d . '/delete')) ?>"><?= csrf_field() ?>
              <button class="chip chip-removable" type="submit" aria-label="Open <?= e(fmt_date($d, 'D, M j')) ?> again"><?= icon('calendar', 'icon-sm') ?><?= e(fmt_date($d, 'D, M j')) ?><?= icon('x') ?></button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="small muted mb-4">No dates blocked. Customers can book any of your working days.</p>
      <?php endif; ?>
      <form class="row row-wrap" method="post" action="<?= e(url('/provider/days-off')) ?>" style="--gap:10px;align-items:flex-start">
        <?= csrf_field() ?>
        <div class="field<?= isset($err['day']) ? ' is-invalid' : '' ?>" style="flex:1 1 200px;max-width:260px">
          <label class="sr-only" for="day-off">Date to block</label>
          <input class="input" id="day-off" name="day" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
          <?= $fe('day') ?>
        </div>
        <button class="btn" type="submit" style="height:48px"><?= icon('plus') ?> Block date</button>
      </form>
    </div>
  </section>

  <section class="card" id="services">
    <div class="card-header"><h3>Services &amp; prices</h3><span class="small subtle"><?= count($services) ?>/15</span></div>
    <div class="card-body">
      <?php if (!$services): ?><p class="muted small mb-4">Add the services you offer with a clear price. Clients book faster when they know what to expect.</p><?php endif; ?>
      <?php foreach ($services as $s): ?>
        <div class="service-row">
          <div class="grow"><div class="semibold"><?= e($s['title']) ?></div><?php if ($s['description']): ?><div class="small muted"><?= e($s['description']) ?></div><?php endif; ?></div>
          <div class="service-price"><?= money($s['price']) ?> <span class="small subtle"><?= e($units[$s['price_unit']]) ?><?= in_array($s['price_unit'], ['fixed', 'starting'], true) ? ' · ' . e(Availability::hoursText((int) $s['duration_hours'])) : '' ?></span></div>
          <form method="post" action="<?= e(url('/provider/services/' . $s['id'] . '/delete')) ?>" data-confirm="Remove “<?= e($s['title']) ?>”?"><?= csrf_field() ?><button class="btn btn-ghost btn-icon btn-sm" type="submit" aria-label="Remove service"><?= icon('trash') ?></button></form>
        </div>
      <?php endforeach; ?>
      <form class="add-service mt-4" method="post" action="<?= e(url('/provider/services')) ?>">
        <?= csrf_field() ?>
        <div class="add-service-head"><span class="choice-icon" style="width:34px;height:34px"><?= icon('plus') ?></span><div><strong>Add a service</strong><small>Clear, fixed prices get booked faster.</small></div></div>
        <div class="grid split-sm" style="--cols:minmax(0,2fr) minmax(0,1fr) minmax(0,1fr);--gap:12px">
          <div class="field"><label class="label" for="svc-title">Service name</label><input class="input" id="svc-title" name="title" placeholder="e.g. Drain unclogging" maxlength="120" required value="<?= e(old('title')) ?>"></div>
          <div class="field"><label class="label" for="svc-price">Price</label><div class="input-affix"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input" id="svc-price" name="price" type="number" min="50" step="50" inputmode="numeric" placeholder="0" required></div></div>
          <div class="field"><label class="label" for="svc-unit">Pricing</label><select class="select" id="svc-unit" name="price_unit"><?php foreach ($units as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="grid split-sm mt-4" style="--cols:minmax(0,2fr) minmax(0,1fr);--gap:12px">
          <div class="field"><label class="label" for="svc-desc">Short description <span class="opt">optional</span></label><input class="input" id="svc-desc" name="description" maxlength="255" placeholder="What's included, e.g. Sinks, showers and floor drains"></div>
          <div class="field" data-svc-duration><label class="label" for="svc-duration">Takes about</label><select class="select" id="svc-duration" name="duration_hours"><?php for ($h = 1; $h <= Availability::MAX_HOURS; $h++): ?><option value="<?= $h ?>"<?= $h === 2 ? ' selected' : '' ?>><?= Availability::hoursText($h) ?></option><?php endfor; ?></select></div>
        </div>
        <p class="help mt-2" data-svc-duration-help>Customers can only book start times where the whole job fits your hours.</p>
        <script>
          (function () {
            var u = document.getElementById('svc-unit'), d = document.querySelector('[data-svc-duration]'), h = document.querySelector('[data-svc-duration-help]');
            var sync = function () { var on = u.value === 'fixed' || u.value === 'starting'; d.hidden = !on; h.textContent = on ? 'Customers can only book start times where the whole job fits your hours.' : (u.value === 'hour' ? 'Customers choose how many hours they need.' : 'Per-day work takes your whole working day.'); };
            u.addEventListener('change', sync); sync();
          })();
        </script>
        <?php foreach (['title', 'price', 'price_unit', 'description', 'duration_hours'] as $k): ?><?= $fe($k) ?><?php endforeach; ?>
        <div class="row mt-4" style="justify-content:flex-end"><button class="btn btn-dark" type="submit"><?= icon('plus') ?> Add service</button></div>
      </form>
    </div>
  </section>

  <section class="card" id="portfolio">
    <div class="card-header"><h3>Portfolio</h3><span class="small subtle"><?= count($portfolio) ?>/24 photos</span></div>
    <div class="card-body">
      <?php if ($portfolio): ?>
        <div class="gallery mb-4">
          <?php foreach ($portfolio as $item): ?>
            <div style="position:relative">
              <a href="<?= e(upload_url($item['image'])) ?>" target="_blank" rel="noopener"><img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['caption'] ?: 'Portfolio photo') ?>" loading="lazy"></a>
              <form method="post" action="<?= e(url('/provider/portfolio/' . $item['id'] . '/delete')) ?>" data-confirm="Delete this photo?" style="position:absolute;top:8px;right:8px"><?= csrf_field() ?><button class="save-btn" type="submit" aria-label="Delete photo"><?= icon('trash', 'icon-sm') ?></button></form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('/provider/portfolio')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="dropzone">
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
          <?= icon('image') ?>
          <span class="dropzone-file">Drop a photo of your work here, or click to browse</span>
          <span class="help">JPG, PNG or WebP, up to <?= (int) config('uploads.max_image_mb', 5) ?> MB. Before and after shots work best.</span>
        </label>
        <?= $fe('image') ?>
        <div class="row mt-3"><div class="input-icon grow"><?= icon('pencil') ?><input class="input" name="caption" maxlength="160" placeholder="Add a caption" aria-label="Caption"></div><button class="btn btn-primary" type="submit" style="height:48px"><?= icon('upload') ?> Upload</button></div>
      </form>
    </div>
  </section>

  <section class="card" id="verification">
    <div class="card-header"><h3><?= icon('shield-check') ?> Verification</h3><?= badge($vs) ?></div>
    <div class="card-body">
      <?php if ($vs === 'verified'): ?>
        <div class="alert alert-success"><?= icon('badge-check') ?><div><strong>You're verified.</strong> Your profile shows the Verified badge, and verified pros rank higher in search.</div></div>
      <?php elseif ($vs === 'pending'): ?>
        <div class="alert alert-info"><?= icon('clock') ?><div><strong>Under review.</strong> Our team is checking your document. This usually takes 1–2 business days.</div></div>
      <?php else: ?>
        <?php if ($vs === 'rejected'): ?>
          <div class="alert alert-error mb-4"><?= icon('alert') ?><div><strong>Your last submission wasn't approved.</strong><?= $p['verification_note'] ? ' Reason: ' . e($p['verification_note']) : '' ?> Please upload a clearer document.</div></div>
        <?php endif; ?>
        <?php if (!$u['avatar']): ?>
          <div class="alert alert-warning mb-4"><?= icon('user') ?><div><strong>Add your photo first.</strong> <a href="#photo">Upload a clear photo of your face</a>. Our team checks it against your ID.</div></div>
        <?php endif; ?>
        <p class="muted mb-4">Upload a government-issued ID (UMID, driver's license, passport, PhilSys ID) or a trade license (e.g. TESDA certificate). Documents are stored privately and only reviewed by Servease admins.</p>
        <form method="post" action="<?= e(url('/provider/verification')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <label class="dropzone">
            <input type="file" name="document" accept="image/jpeg,image/png,image/webp,application/pdf" required>
            <?= icon('file-text') ?>
            <span class="dropzone-file">Choose your ID or license</span>
            <span class="help">JPG, PNG, WebP or PDF, up to <?= (int) config('uploads.max_doc_mb', 8) ?> MB</span>
          </label>
          <?= $fe('document') ?>
          <button class="btn btn-primary mt-4" type="submit"><?= icon('shield-check') ?> Submit for verification</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</div>
