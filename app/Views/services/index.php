<?php
$labels = [
    'q' => $filters['q'] ? '“' . $filters['q'] . '”' : null,
    'category' => $category['name'] ?? null,
    'city' => $filters['city'] ? 'In ' . $filters['city'] : null,
    'price' => $filters['price'] ? ServiceController::PRICE_RANGES[$filters['price']][0] : null,
    'price_type' => $filters['price_type'] ? ServiceController::PRICE_TYPES[$filters['price_type']] : null,
    'max_price' => $filters['max_price'] ? 'Up to ' . money($filters['max_price']) : null,
    'rating' => $filters['rating'] ? $filters['rating'] . '+ stars' : null,
    'when' => $filters['when'] ? ServiceController::WHEN[$filters['when']] : null,
    'verified' => $filters['verified'] ? 'Verified pros' : null,
    'available' => $filters['available'] ? 'Taking new jobs' : null,
];
$active = array_filter($labels);
$sheetCount = count(array_filter(array_diff_key($labels, ['q' => 1, 'category' => 1])));
$heading = $filters['q'] !== '' ? 'Results for “' . $filters['q'] . '”' : ($category ? $category['name'] : 'All services');
$heroTitle = $category ? $category['name'] : 'Services';
$heroLead = $category['description'] ?? 'Compare prices from pros near you. Each pro sets their own price, and you pay them after the job.';
// Category links keep the other filters (and the sort, unless it's the default)
$chipKeep = array_diff_key($filters, ['category' => 1]);
if ($chipKeep['sort'] === 'recommended') unset($chipKeep['sort']);
$keep = array_filter($chipKeep, fn($v) => $v !== '' && $v !== null);
?>
<?= partial('listing-hero', [
    'title'   => $heroTitle,
    'lead'    => $heroLead,
    'art'     => $category['slug'] ?? true,
    'chips'   => ['base' => '/services', 'current' => $filters['category'], 'keep' => $chipKeep],
]) ?>
<div class="container page listing">
  <!-- Phone: blue banner, round category buttons, then Filters and Sort -->
  <div class="listing-head show-md">
    <div class="phone-title">
      <h1><?= e($category ? $category['name'] : 'Services') ?></h1>
      <?php if ($category && !empty($category['description'])): ?><p><?= e($category['description']) ?></p><?php endif; ?>
    </div>

    <nav class="cat-circles" aria-label="Categories">
      <a class="cat-circle<?= !$category ? ' is-active' : '' ?>" href="<?= e(url('/services', $keep)) ?>"<?= !$category ? ' aria-current="page"' : '' ?>><span><?= icon('grid') ?></span>All</a>
      <?php foreach (categories() as $c): $on = $filters['category'] === $c['slug']; ?>
        <a class="cat-circle<?= $on ? ' is-active' : '' ?>" href="<?= e(url('/services', ['category' => $c['slug']] + $keep)) ?>"<?= $on ? ' aria-current="page"' : '' ?>><span><?= category_art($c['slug'], $c['icon']) ?></span><?= e(preg_replace('/ &.*$/', '', $c['name'])) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="phone-tools">
      <button class="btn phone-filter-btn" type="button" data-filters-toggle aria-controls="filters" aria-expanded="false"><?= icon('sliders') ?> Filters<?php if ($sheetCount): ?><span class="count-dot"><?= $sheetCount ?></span><?php endif; ?></button>
      <?= partial('sort-select', ['action' => url('/services'), 'filters' => $filters, 'options' => ServiceController::SORTS, 'id' => 'sort']) ?>
    </div>
  </div>

  <div class="layout-filters">
    <div class="sheet-backdrop" data-filters-close></div>
    <form class="filters-card" id="filters" method="get" action="<?= e(url('/services')) ?>" aria-label="Filters">
      <div class="sheet-grip show-md" aria-hidden="true"></div>
      <div class="filter-top">
        <strong>Filters</strong>
        <a class="btn-link small" href="<?= e(url('/services')) ?>">Clear all</a>
      </div>
      <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
      <?php if ($filters['category'] !== ''): ?><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><?php endif; ?>
      <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">

      <fieldset class="filter-group">
        <legend><label for="f-city">City</label></legend>
        <div class="input-icon"><?= icon('map-pin') ?>
          <select class="select" id="f-city" name="city">
            <option value="">Any city</option>
            <?php foreach (cities() as $c): ?><option<?= selected($filters['city'], $c) ?>><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Price range</legend>
        <div class="picks">
          <label class="pick"><input type="radio" name="price" value=""<?= checked($filters['price'] === '') ?>><span>Any</span></label>
          <?php foreach (ServiceController::PRICE_RANGES as $k => [$l]): ?>
            <label class="pick"><input type="radio" name="price" value="<?= e($k) ?>"<?= checked($filters['price'] === (string) $k) ?>><span><?= e($l) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Rating</legend>
        <div class="star-list">
          <label class="star-row"><input type="radio" name="rating" value=""<?= checked($filters['rating'] === '') ?>><span class="star-dot"></span><span>Any rating</span></label>
          <?php foreach (ServiceController::RATINGS as $v => $l): ?>
            <label class="star-row"><input type="radio" name="rating" value="<?= e($v) ?>"<?= checked($filters['rating'] === (string) $v) ?>><span class="star-dot"></span><?= icon('star') ?><span><?= number_format((float) $v, 1) ?>+ <span class="muted">(<?= e($l) ?>)</span></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Availability</legend>
        <div class="picks">
          <label class="pick"><input type="radio" name="when" value=""<?= checked($filters['when'] === '') ?>><span>Any time</span></label>
          <?php foreach (ServiceController::WHEN as $k => $l): ?>
            <label class="pick"><input type="radio" name="when" value="<?= e($k) ?>"<?= checked($filters['when'] === $k) ?>><span><?= e($l) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Pros</legend>
        <label class="check-row"><input type="checkbox" name="verified" value="1"<?= checked($filters['verified']) ?>><span class="check-box"><?= icon('check') ?></span>Verified pros only</label>
      </fieldset>

      <div class="filter-actions">
        <button class="btn btn-primary btn-block" type="submit">Show results</button>
      </div>
    </form>

    <section class="results" aria-label="Results">
      <div class="results-head">
        <div class="results-title">
          <h2><?= e($heading) ?></h2>
          <p class="results-count"><strong><?= number_format($total) ?></strong> <?= $total === 1 ? 'service' : 'services' ?> found<?= $filters['city'] ? ' in ' . e($filters['city']) : '' ?></p>
        </div>
        <div class="results-tools hide-md">
          <?= partial('browse-switch', ['on' => 'services']) ?>
          <?= partial('sort-select', ['action' => url('/services'), 'filters' => $filters, 'options' => ServiceController::SORTS, 'id' => 'sort-desk']) ?>
        </div>
      </div>
      <?php $chipsShown = array_diff_key($active, ['category' => 1]); if ($chipsShown): ?>
        <div class="active-filters">
          <?php foreach ($chipsShown as $key => $label): ?>
            <a class="chip is-active" href="<?= e(qs([$key => null, 'page' => null])) ?>" aria-label="Remove filter <?= e($label) ?>"><?= e($label) ?> <?= icon('x') ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!$rows): ?>
        <div class="card empty">
          <?= empty_art('search') ?>
          <h3>No services match</h3>
          <p>Try removing a filter or another city. <?= is_role('client') ? 'Or post a job and let pros send you quotes.' : '' ?></p>
          <div class="row" style="justify-content:center;flex-wrap:wrap">
            <a class="btn" href="<?= e(url('/services')) ?>">Clear filters</a>
            <?php if (is_role('client')): ?><a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>">Post a job</a><?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="svc-grid">
          <?php foreach ($rows as $s): ?><?= partial('service-card', ['s' => $s]) ?><?php endforeach; ?>
        </div>
        <?= pagination($total, $perPage, $page) ?>
      <?php endif; ?>
    </section>
  </div>
</div>
