<?php
$u = user();
$hours = (int) old('hours', '2') ?: 2;
?>
<?php app_bar('Accept quote', '/jobs/' . $q['job_id']); ?>
<section class="page-header page-header-soft hide-md">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/my/jobs')) ?>">My jobs</a><?= icon('chevron-right') ?>
      <a href="<?= e(url('/jobs/' . $q['job_id'])) ?>" class="truncate"><?= e(str_limit($q['job_title'], 50)) ?></a><?= icon('chevron-right') ?><span>Accept quote</span>
    </nav>
    <h1>Book <?= e($p['first_name']) ?> for this job</h1>
    <p class="lead">Pick a time <?= e($p['first_name']) ?> is free and where the work is. Accepting confirms the booking at the quoted price.</p>
  </div>
</section>

<div class="container page">
  <div class="layout-aside" style="--aside:340px">
    <form class="form-card" method="post" action="<?= e(url('/quotes/' . $q['id'] . '/accept')) ?>" novalidate>
      <?= csrf_field() ?>
      <section class="form-section">
        <div class="form-step"><span class="form-step-num">1</span><div><h2>When?</h2><p><?= e($p['first_name']) ?> works <?= e(preg_replace('/^Every day/', 'every day', Availability::summary($p))) ?>.<?= $q['preferred_date'] ? ' You asked for ' . e(fmt_date($q['preferred_date'], 'l, M j')) . '.' : '' ?></p></div></div>
        <?= partial('slot-picker', ['p' => $p, 'dates' => $dates, 'busy' => $busy, 'date' => $date, 'time' => (string) old('scheduled_time', ''), 'duration' => $hours, 'withHours' => true]) ?>
      </section>

      <section class="form-section">
        <div class="form-step"><span class="form-step-num">2</span><div><h2>Where?</h2><p>Your address is only shared with <?= e($p['first_name']) ?> for this booking.</p></div></div>
        <div class="field<?= invalid('address') ?>">
          <label class="label" for="address">Address</label>
          <div class="input-icon"><?= icon('home') ?><input class="input" id="address" name="address" value="<?= e(old('address')) ?>" placeholder="House/unit no., street, barangay" autocomplete="street-address" required></div>
          <?= field_error('address') ?>
        </div>
        <div class="field mt-5<?= invalid('city') ?>">
          <label class="label" for="city">City</label>
          <div class="input-icon"><?= icon('map-pin') ?>
            <select class="select" id="city" name="city" required>
              <?php foreach (cities() as $c): ?><option<?= selected(old('city', $q['city'] ?: ($u['city'] ?? '')), $c) ?>><?= e($c) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
      </section>

      <div class="form-actions">
        <span class="note"><?= icon('shield-check') ?> Other quotes will be declined automatically</span>
        <button class="btn btn-primary btn-lg" type="submit"<?= $dates ? '' : ' disabled' ?>><?= icon('check') ?> Accept and book</button>
      </div>
    </form>

    <aside class="sticky stack" style="--gap:16px">
      <div class="card summary-card">
        <div class="summary-pro">
          <?= avatar($p, 'md') ?>
          <div class="grow" style="min-width:0">
            <div class="row" style="--gap:6px"><strong class="truncate"><?= e(full_name($p)) ?></strong><?php if ($p['verification_status'] === 'verified'): ?><span class="verified"><?= icon('badge-check', 'icon-sm') ?></span><?php endif; ?></div>
            <div class="small muted"><?= e($p['category_name'] ?? '') ?> · <?= $p['rating_count'] ? '★ ' . number_format((float) $p['rating_avg'], 1) . ' (' . (int) $p['rating_count'] . ')' : 'New pro' ?></div>
          </div>
        </div>
        <dl class="summary-list">
          <div><dt><?= icon('wrench') ?> Job</dt><dd class="clamp-2"><?= e($q['job_title']) ?></dd></div>
          <?php if ($q['days_needed']): ?><div><dt><?= icon('calendar') ?> Pro's estimate</dt><dd><?= (int) $q['days_needed'] ?> day<?= (int) $q['days_needed'] === 1 ? '' : 's' ?></dd></div><?php endif; ?>
        </dl>
        <div class="summary-total"><span>Quoted price</span><strong><?= money($q['amount']) ?></strong></div>
      </div>
      <div class="card card-body">
        <h3 class="mb-2" style="font-size:1rem"><?= e($p['first_name']) ?>'s message</h3>
        <p class="small muted clamp-6" style="white-space:pre-line"><?= e($q['message']) ?></p>
      </div>
    </aside>
  </div>
</div>
