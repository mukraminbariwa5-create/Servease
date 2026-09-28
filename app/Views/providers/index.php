<?php
$active = array_filter([
    'q' => $filters['q'] ? '“' . $filters['q'] . '”' : null,
    'category' => $category['name'] ?? null,
    'city' => $filters['city'] ?: null,
    'rating' => $filters['rating'] ? $filters['rating'] . '+ stars' : null,
    'verified' => $filters['verified'] ? 'Verified only' : null,
    'available' => $filters['available'] ? 'Available now' : null,
    'max_rate' => $filters['max_rate'] ? 'Up to ' . money($filters['max_rate']) . '/hr' : null,
]);
?>
<section class="page-header">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="<?= e(url('/')) ?>">Home</a><?= icon('chevron-right') ?>
      <?php if ($category): ?>
        <a href="<?= e(url('/providers')) ?>">Pros</a><?= icon('chevron-right') ?><span><?= e($category['name']) ?></span>
      <?php else: ?><span>Pros</span><?php endif; ?>
    </nav>
    <h1><?= e($category ? $category['name'] . ' pros' : 'Find the right pro') ?></h1>
    <p class="lead"><?= e($category['description'] ?? 'Compare verified local pros by rating, price and experience.') ?></p>
  </div>
</section>

<div class="container page">
  <div class="layout-filters">
    <aside>
      <button class="btn btn-block mb-4 filters-toggle" type="button" data-filters-toggle><?= icon('filter') ?> Filters</button>
      <form class="card filters filters-card" method="get" action="<?= e(url('/providers')) ?>">
        <div class="filter-title"><?= icon('filter') ?> Filters</div>
        <div class="field">
          <label class="label" for="f-q">Keyword</label>
          <div class="input-icon"><?= icon('search') ?><input class="input" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="e.g. leak, CCTV, math"></div>
        </div>
        <div class="field">
          <label class="label" for="f-cat">Category</label>
          <select class="select" id="f-cat" name="category">
            <option value="">All categories</option>
            <?php foreach (categories() as $c): ?>
              <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="f-city">City</label>
          <select class="select" id="f-city" name="city">
            <option value="">Any city</option>
            <?php foreach (cities() as $c): ?><option <?= $filters['city'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="f-rating">Minimum rating</label>
          <select class="select" id="f-rating" name="rating">
            <option value="">Any rating</option>
            <?php foreach (['4.5' => '4.5 & up', '4' => '4.0 & up', '3' => '3.0 & up'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= $filters['rating'] === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="f-rate">Max hourly rate</label>
          <div class="input-affix"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input" id="f-rate" name="max_rate" type="number" min="0" step="50" value="<?= e($filters['max_rate']) ?>" placeholder="Any"></div>
        </div>
        <div class="field">
          <label class="check"><input type="checkbox" name="verified" value="1" <?= $filters['verified'] ? 'checked' : '' ?>> Verified pros only</label>
          <label class="check"><input type="checkbox" name="available" value="1" <?= $filters['available'] ? 'checked' : '' ?>> Available now</label>
        </div>
        <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
        <div class="row mt-6">
          <button class="btn btn-primary grow" type="submit">Apply</button>
          <a class="btn btn-ghost" href="<?= e(url('/providers')) ?>">Reset</a>
        </div>
      </form>
    </aside>

    <section>
      <div class="results-bar">
        <div>
          <strong><?= number_format($total) ?></strong> <span class="muted">pro<?= $total === 1 ? '' : 's' ?> found</span>
          <?php if ($active): ?>
            <div class="active-filters mt-2">
              <?php foreach ($active as $key => $label): ?>
                <a class="chip is-active" href="<?= e(qs([$key => null, 'page' => null])) ?>"><?= e($label) ?> <?= icon('x') ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <form method="get" action="<?= e(url('/providers')) ?>" class="row" style="--gap:8px">
          <?php foreach ($filters as $k => $v): if ($k === 'sort' || $v === '') continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <label class="small muted nowrap" for="sort">Sort by</label>
          <select class="select" id="sort" name="sort" data-autosubmit>
            <?php foreach (ProviderController::SORTS as $k => $label): ?>
              <option value="<?= $k ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-sm">Go</button></noscript>
        </form>
      </div>

      <?php if (!$rows): ?>
        <div class="card empty">
          <div class="empty-icon"><?= icon('search') ?></div>
          <h3>No pros match your filters</h3>
          <p>Try removing a filter or searching a nearby city. You can also post a job and let pros come to you.</p>
          <div class="row" style="justify-content:center">
            <a class="btn" href="<?= e(url('/providers')) ?>">Clear filters</a>
            <a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>">Post a job</a>
          </div>
        </div>
      <?php else: ?>
        <div class="grid grid-3">
          <?php foreach ($rows as $p): ?><?= partial('provider-card', ['p' => $p]) ?><?php endforeach; ?>
        </div>
        <?= pagination($total, $perPage, $page) ?>
      <?php endif; ?>
    </section>
  </div>
</div>
