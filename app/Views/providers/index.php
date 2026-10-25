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
$heading = $category ? $category['name'] . ' pros' : 'All pros';
$allCount = array_sum($catCounts);
$sheetCount = count($active) - ($filters['q'] ? 1 : 0) - ($category ? 1 : 0);
app_bar($heading, '/categories', '<button class="icon-btn" type="button" data-filters-toggle aria-controls="filters" aria-expanded="false" aria-label="Filters">'
    . icon('sliders') . ($sheetCount > 0 ? '<span class="count-dot">' . $sheetCount . '</span>' : '') . '</button>');
?>
<?php
$rating = (float) ($summary['rating'] ?? 0);
$stats = [['users', '<strong>' . number_format($total) . '</strong> ' . ($total === 1 ? 'pro' : 'pros')]];
if (!empty($summary['verified'])) $stats[] = ['badge-check', '<strong>' . (int) $summary['verified'] . '</strong> ID-verified'];
if (!empty($summary['from_rate'])) $stats[] = ['tag', 'From <strong>' . e(money($summary['from_rate'])) . '</strong> / hour'];
if ($rating > 0) $stats[] = ['star', '<strong>' . number_format($rating, 1) . '</strong> average rating'];
$chipKeep = array_diff_key($filters, ['category' => 1]);
if ($chipKeep['sort'] === 'recommended') unset($chipKeep['sort']);
?>
<?= partial('listing-hero', [
    'crumbs' => array_merge([['Home', '/'], ['Pros', $category ? '/providers' : null]], $category ? [[$category['name'], null]] : []),
    'title'  => $heading,
    'lead'   => $category ? 'ID-checked ' . strtolower(preg_replace('/ &.*$/', '', $category['name'])) . ' pros with real reviews from completed bookings.' : 'ID-checked pros with real reviews from completed bookings.',
    'stats'  => $stats,
    'switch' => 'pros',
    'sort'   => ['action' => url('/providers'), 'filters' => $filters, 'options' => ProviderController::SORTS, 'current' => $filters['sort']],
    'chips'  => ['base' => '/providers', 'current' => $filters['category'], 'keep' => $chipKeep],
]) ?>
<div class="container page listing">
  <div class="listing-head">
    <?= partial('browse-switch', ['on' => 'pros']) ?>

    <form class="home-search show-md" action="<?= e(url('/providers')) ?>" method="get" role="search">
      <?php foreach (['category', 'city', 'sort'] as $k): if ($filters[$k] !== '' && !($k === 'sort' && $filters[$k] === 'recommended')): ?><input type="hidden" name="<?= $k ?>" value="<?= e($filters[$k]) ?>"><?php endif; endforeach; ?>
      <label class="search-field">
        <?= icon('search') ?><span class="sr-only">Search</span>
        <input name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Search pros by name or skill">
      </label>
    </form>

    <div class="chip-scroller show-md" aria-label="Categories">
      <a class="chip<?= !$category ? ' is-active' : '' ?>" href="<?= e(qs(['category' => null, 'page' => null])) ?>">All</a>
      <?php foreach (categories() as $c): ?>
        <a class="chip<?= $filters['category'] === $c['slug'] ? ' is-active' : '' ?>" href="<?= e(qs(['category' => $c['slug'], 'page' => null])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="layout-filters">
    <div class="sheet-backdrop" data-filters-close></div>
    <form class="filters-card" id="filters" method="get" action="<?= e(url('/providers')) ?>" aria-label="Filters">
      <div class="sheet-grip show-md" aria-hidden="true"></div>
      <div class="filter-top">
        <strong>Filters</strong>
        <a class="btn-link small" href="<?= e(url('/providers')) ?>">Clear all</a>
      </div>
      <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
      <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">

      <fieldset class="filter-group filter-cat">
        <legend>Category</legend>
        <div class="radio-list">
          <label class="radio-row"><input type="radio" name="category" value=""<?= checked($filters['category'] === '') ?>><span class="radio-mark"></span><span class="grow">All trades</span><span class="n"><?= $allCount ?></span></label>
          <?php foreach (categories() as $c): ?>
            <label class="radio-row"><input type="radio" name="category" value="<?= e($c['slug']) ?>"<?= checked($filters['category'] === $c['slug']) ?>><span class="radio-mark"></span><span class="grow"><?= e($c['name']) ?></span><span class="n"><?= (int) ($catCounts[$c['slug']] ?? 0) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

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
        <legend><label for="f-rate">Max hourly rate</label></legend>
        <div class="input-affix"><span class="affix"><?= e(config('app.currency')) ?></span><input class="input" id="f-rate" name="max_rate" type="number" min="0" step="50" inputmode="numeric" value="<?= e($filters['max_rate']) ?>" placeholder="Any price"></div>
        <div class="picks mt-2">
          <?php foreach (['400', '600', '1000'] as $v): ?>
            <button class="pick pick-sm" type="button" data-fill="#f-rate" data-value="<?= $v ?>">≤ <?= money($v) ?></button>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Rating</legend>
        <div class="picks">
          <?php foreach (['' => 'Any', '4.5' => '4.5 and up', '4' => '4.0 and up'] as $v => $l): ?>
            <label class="pick"><input type="radio" name="rating" value="<?= $v ?>"<?= checked($filters['rating'] === (string) $v) ?>><span><?php if ($v): ?><?= icon('star') ?><?php endif; ?><?= $l ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="filter-group">
        <legend>Availability</legend>
        <label class="switch switch-row"><span class="grow">Verified pros only</span><input type="checkbox" name="verified" value="1"<?= checked($filters['verified']) ?>><span class="switch-track"></span></label>
        <label class="switch switch-row"><span class="grow">Available now</span><input type="checkbox" name="available" value="1"<?= checked($filters['available']) ?>><span class="switch-track"></span></label>
      </fieldset>

      <div class="filter-actions">
        <button class="btn btn-primary btn-block" type="submit">Show results</button>
      </div>
    </form>

    <section class="results" aria-label="Results">
      <div class="results-bar">
        <div class="results-count"><strong><?= number_format($total) ?></strong> <?= $total === 1 ? 'pro' : 'pros' ?><?= $filters['city'] ? ' in ' . e($filters['city']) : '' ?></div>
        <form method="get" action="<?= e(url('/providers')) ?>" class="sort-form show-md">
          <?php foreach ($filters as $k => $v): if ($k === 'sort' || $v === '') continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <label class="small muted nowrap" for="sort">Sort by</label>
          <select class="select select-sm" id="sort" name="sort" data-autosubmit>
            <?php foreach (ProviderController::SORTS as $k => $label): ?>
              <option value="<?= $k ?>"<?= selected($filters['sort'], $k) ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-sm">Go</button></noscript>
        </form>
      </div>
      <?php if ($active): ?>
        <div class="active-filters<?= count($active) === 1 && isset($active['category']) ? ' only-cat' : '' ?>">
          <?php foreach ($active as $key => $label): ?>
            <a class="chip is-active<?= $key === 'category' ? ' chip-cat' : '' ?>" href="<?= e(qs([$key => null, 'page' => null])) ?>" aria-label="Remove filter <?= e($label) ?>"><?= e($label) ?> <?= icon('x') ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!$rows): ?>
        <div class="card empty">
          <?= empty_art('search') ?>
          <h3>No pros match your filters</h3>
          <p>Try removing a filter or searching a nearby city. <?= is_role('client') ? 'You can also post a job and let pros come to you.' : '' ?></p>
          <div class="row" style="justify-content:center;flex-wrap:wrap">
            <a class="btn" href="<?= e(url('/providers')) ?>">Clear filters</a>
            <?php if (is_role('client')): ?><a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>">Post a job</a><?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="pro-grid pro-grid-3 show-md">
          <?php foreach ($rows as $p): ?><?= partial('provider-card', ['p' => $p]) ?><?php endforeach; ?>
        </div>
        <div class="pro-lines hide-md">
          <?php foreach ($rows as $p): ?><?= partial('pro-list-row', ['p' => $p, 'services' => $services[$p['id']] ?? []]) ?><?php endforeach; ?>
        </div>
        <?= pagination($total, $perPage, $page) ?>
      <?php endif; ?>
    </section>
  </div>
</div>
