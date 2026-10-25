<?php
$cats = categories();
$preselect = old('category_id', array_column($cats, 'id', 'slug')[$category] ?? '');
$cur = config('app.currency');
$d = fn(string $mod) => date('Y-m-d', strtotime($mod));
$weekend = date('N') >= 6 ? date('Y-m-d', strtotime('tomorrow')) : $d('next saturday');
?>
<?php app_bar('Post a job', '/my/jobs'); ?>
<section class="page-header page-header-soft hide-md">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('/my/jobs')) ?>">My jobs</a><?= icon('chevron-right') ?><span>Post a job</span></nav>
    <h1>Post a job</h1>
    <p class="lead">Tell pros what you need. Most jobs get their first quote within a few hours, and posting is free.</p>
  </div>
</section>

<div class="container page">
  <div class="layout-aside" style="--aside:360px">
    <form class="form-card" method="post" action="<?= e(url('/jobs')) ?>" novalidate data-live-preview>
      <?= csrf_field() ?>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">1</span><div><h2>What do you need help with?</h2><p>Pick the closest category. Pros in that trade get notified.</p></div></div>
        <fieldset class="fieldset field<?= invalid('category_id') ?>">
          <legend class="sr-only">Category</legend>
          <div class="tiles">
            <?php foreach ($cats as $c): ?>
              <label class="tile">
                <input type="radio" name="category_id" value="<?= (int) $c['id'] ?>" data-label="<?= e($c['name']) ?>" data-icon="<?= e($c['icon']) ?>"<?= checked((string) $preselect === (string) $c['id']) ?> required>
                <span class="tile-body"><span class="tile-icon" style="--tint:<?= category_tint($c['slug']) ?>"><?= category_art($c['slug'], $c['icon']) ?></span><span class="tile-name"><?= e($c['name']) ?></span></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?= field_error('category_id') ?>
        </fieldset>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">2</span><div><h2>Describe the job</h2><p>Clear details get you accurate quotes, and fewer surprises.</p></div></div>
        <div class="field<?= invalid('title') ?>">
          <div class="label-row"><label class="label" for="title">Job title</label><span class="counter"></span></div>
          <input class="input" id="title" name="title" maxlength="140" data-count data-min="8" value="<?= e(old('title')) ?>" placeholder="e.g. Fix leaking kitchen faucet" required>
          <?= field_error('title') ?>
        </div>
        <div class="field<?= invalid('description') ?>">
          <div class="label-row"><label class="label" for="description">Details</label><span class="counter"></span></div>
          <textarea class="textarea" id="description" name="description" rows="6" maxlength="3000" data-count data-min="20" placeholder="What's the problem, and where? Include sizes, brand or model, and anything the pro should bring…" required><?= e(old('description')) ?></textarea>
          <?= field_error('description') ?>
          <div class="tip-row"><?= icon('info', 'icon-sm') ?><span><strong>Tip:</strong> mention the size of the area, when it started, and parking or building access.</span></div>
        </div>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">3</span><div><h2>Where and when?</h2><p>Your exact address is only shared with the pro you hire.</p></div></div>
        <div class="form-row">
          <div class="field<?= invalid('city') ?>">
            <label class="label" for="city">City</label>
            <div class="input-icon"><?= icon('map-pin') ?>
              <select class="select" id="city" name="city" required>
                <option value="">Select city</option>
                <?php foreach (cities() as $c): ?><option<?= selected(old('city', user()['city'] ?? ''), $c) ?>><?= e($c) ?></option><?php endforeach; ?>
              </select>
            </div>
            <?= field_error('city') ?>
          </div>
          <div class="field<?= invalid('preferred_date') ?>">
            <label class="label" for="preferred_date">Preferred date <span class="opt">optional</span></label>
            <div class="input-icon"><?= icon('calendar') ?><input class="input" id="preferred_date" name="preferred_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= e(old('preferred_date')) ?>"></div>
            <?= field_error('preferred_date') ?>
          </div>
        </div>
        <div class="picks mt-3" aria-label="Quick dates">
          <button class="pick" type="button" data-fill="#preferred_date" data-value="<?= $d('tomorrow') ?>"><?= icon('clock') ?> Tomorrow</button>
          <button class="pick" type="button" data-fill="#preferred_date" data-value="<?= $weekend ?>"><?= icon('calendar') ?> This weekend</button>
          <button class="pick" type="button" data-fill="#preferred_date" data-value="<?= $d('next monday') ?>"><?= icon('calendar') ?> Next week</button>
          <button class="pick" type="button" data-fill="#preferred_date" data-value=""><?= icon('x') ?> No preference</button>
        </div>

        <fieldset class="fieldset mt-6">
          <legend class="label">How soon do you need it?</legend>
          <div class="choices choices-compact grid-3" style="display:grid">
            <?php foreach (['flexible' => ['calendar', 'Flexible', 'Any time works'], 'this_week' => ['clock', 'This week', 'Within 7 days'], 'urgent' => ['zap', 'Urgent', 'As soon as possible']] as $val => [$ic, $t, $desc]): ?>
              <label class="choice">
                <input type="radio" name="urgency" value="<?= $val ?>" data-label="<?= $t ?>"<?= checked(old('urgency', 'flexible') === $val) ?>>
                <span class="choice-body"><span class="choice-icon"><?= icon($ic) ?></span><span><span class="choice-title" style="display:block"><?= $t ?></span><span class="choice-desc"><?= $desc ?></span></span></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">4</span><div><h2>What's your budget? <span class="opt-tag">Optional</span></h2><p>A range helps pros send realistic quotes. You're never charged here.</p></div></div>
        <div class="picks mb-4" aria-label="Budget presets">
          <?php foreach ([['', '1000', 'Under ' . $cur . '1,000'], ['1000', '3000', $cur . '1k–3k'], ['3000', '8000', $cur . '3k–8k'], ['8000', '20000', $cur . '8k–20k'], ['20000', '', $cur . '20k+']] as [$lo, $hi, $label]): ?>
            <button class="pick" type="button" data-budget="<?= $lo ?>,<?= $hi ?>"><?= e($label) ?></button>
          <?php endforeach; ?>
        </div>
        <div class="form-row">
          <div class="field<?= invalid('budget_min') ?>">
            <label class="label" for="budget_min">Minimum</label>
            <div class="input-affix"><span class="affix"><?= e($cur) ?></span><input class="input" id="budget_min" name="budget_min" type="number" min="0" step="50" value="<?= e(old('budget_min')) ?>" placeholder="0" inputmode="numeric"></div>
            <?= field_error('budget_min') ?>
          </div>
          <div class="field<?= invalid('budget_max') ?>">
            <label class="label" for="budget_max">Maximum</label>
            <div class="input-affix"><span class="affix"><?= e($cur) ?></span><input class="input" id="budget_max" name="budget_max" type="number" min="0" step="50" value="<?= e(old('budget_max')) ?>" placeholder="Any" inputmode="numeric"></div>
            <?= field_error('budget_max') ?>
          </div>
        </div>
      </section>

      <div class="form-actions">
        <span class="note"><?= icon('shield-check') ?> Posting is free, and you choose who to hire.</span>
        <div class="row" style="--gap:8px">
          <a class="btn btn-ghost" href="<?= e(url('/my/jobs')) ?>">Cancel</a>
          <button class="btn btn-primary btn-lg" type="submit"><?= icon('send') ?> Post job</button>
        </div>
      </div>
    </form>

    <aside class="sticky stack" style="--gap:16px">
      <div>
        <div class="preview-label"><span class="live-dot"></span> What pros will see</div>
        <div class="card job-card preview-card">
          <div class="row row-top" style="--gap:14px">
            <span class="cat-icon cat-icon-md" data-preview="category_id_label" data-preview-icon data-empty=""><?= icon('briefcase') ?></span>
            <div class="grow">
              <div class="job-title" data-preview="title" data-empty="Your job title"></div>
              <p class="job-desc" data-preview="description" data-empty="Your description will appear here."></p>
            </div>
          </div>
          <div class="job-meta">
            <span><?= icon('tag') ?><b data-preview="category_id_label" data-empty="Category"></b></span>
            <span><?= icon('map-pin') ?><b data-preview="city" data-empty="City"></b></span>
            <span><?= icon('calendar') ?><b data-preview="preferred_date" data-format="date" data-empty="Any date"></b></span>
            <span><?= icon('zap') ?><b data-preview="urgency_label" data-empty="Flexible"></b></span>
          </div>
          <div class="preview-budget"><span>Budget</span><strong data-preview="budget" data-empty="Open to quotes"></strong></div>
        </div>
      </div>
      <div class="card card-body">
        <h3 class="mb-4" style="font-size:1rem">What happens next</h3>
        <ol class="next-steps">
          <li><span>1</span><div><strong>Pros send quotes</strong><small>With a price, timing and a message.</small></div></li>
          <li><span>2</span><div><strong>Compare &amp; chat</strong><small>Check reviews, ask questions first.</small></div></li>
          <li><span>3</span><div><strong>Accept one quote</strong><small>It becomes a booking. Pay the pro directly.</small></div></li>
        </ol>
      </div>
    </aside>
  </div>
  <div class="hidden" aria-hidden="true">
    <?php foreach ($cats as $c): ?><span data-icon-src="<?= e($c['icon']) ?>"><?= category_art($c['slug'], $c['icon']) ?></span><?php endforeach; ?>
  </div>
</div>
<script>
  document.querySelectorAll('[data-budget]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.dataset.budget.split(','), lo = document.getElementById('budget_min'), hi = document.getElementById('budget_max');
      lo.value = v[0]; hi.value = v[1];
      document.querySelectorAll('[data-budget]').forEach(function (o) { o.classList.toggle('is-active', o === b); });
      lo.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
</script>
