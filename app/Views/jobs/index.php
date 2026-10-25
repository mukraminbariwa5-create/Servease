<?php
$activeFilters = (int) ($f['city'] !== '') + (int) ($f['sort'] !== 'newest' && $f['sort'] !== '');
$catName = '';
foreach (categories() as $c) if ($c['slug'] === $f['category']) $catName = $c['name'];
$stats = [['briefcase', '<strong>' . number_format($total) . '</strong> open ' . ($total === 1 ? 'job' : 'jobs')]];
if (!empty($summary['this_week'])) $stats[] = ['clock', '<strong>' . (int) $summary['this_week'] . '</strong> posted this week'];
if (!empty($summary['urgent'])) $stats[] = ['zap', '<strong>' . (int) $summary['urgent'] . '</strong> urgent'];
if (!empty($summary['top_budget'])) $stats[] = ['wallet', 'Budgets up to <strong>' . e(money($summary['top_budget'])) . '</strong>'];
$chipKeep = array_diff_key($f, ['category' => 1]);
if ($chipKeep['sort'] === 'newest') unset($chipKeep['sort']);
?>
<?= partial('listing-hero', [
    'crumbs'  => [['Home', '/'], ['Job board', $catName ? '/jobs' : null]] + ($catName ? [2 => [$catName, null]] : []),
    'title'   => $catName ? $catName . ' jobs' : 'Job board',
    'lead'    => is_role('provider') ? 'Open jobs posted by customers. Send a quote to win the work.' : 'Open jobs posted by customers. Pros send quotes and the customer picks the best one.',
    'stats'   => $stats,
    'sort'    => ['action' => url('/jobs'), 'filters' => $f, 'options' => ['newest' => 'Newest first', 'urgent' => 'Most urgent', 'budget' => 'Highest budget'], 'current' => $f['sort']],
    'actions' => !is_role('provider', 'admin') ? '<a class="btn btn-primary" href="' . e(url('/jobs/new')) . '">' . icon('plus') . ' Post a job</a>' : '',
    'chips'   => ['base' => '/jobs', 'current' => $f['category'], 'keep' => $chipKeep],
]) ?>
<div class="container page listing">
  <div class="listing-head">
    <nav class="breadcrumbs hide-md" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>">Home</a><?= icon('chevron-right') ?><span aria-current="page">Job board</span></nav>
    <div class="page-bar">
      <h1>Job board</h1>
      <?php if (!is_role('provider', 'admin')): ?>
        <a class="btn btn-primary hide-md" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
      <?php endif; ?>
      <button class="icon-btn icon-btn-outline show-md" type="button" data-filters-toggle aria-expanded="false" aria-label="Filters"><?= icon('sliders') ?><?php if ($activeFilters): ?><span class="count-dot"><?= $activeFilters ?></span><?php endif; ?></button>
    </div>
    <p class="muted hide-md">Open jobs posted by customers. <?= is_role('provider') ? 'Send a quote to win the work.' : 'Pros send quotes; the customer picks the best one.' ?></p>
    <form class="home-search show-md" action="<?= e(url('/jobs')) ?>" method="get" role="search">
      <?php foreach (['category', 'city', 'sort'] as $k): if (($f[$k] ?? '') !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= e($f[$k]) ?>"><?php endif; endforeach; ?>
      <label class="search-field"><?= icon('search') ?><span class="sr-only">Search jobs</span><input name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Search jobs"></label>
    </form>
    <div class="chip-scroller show-md" aria-label="Categories">
      <a class="chip<?= $f['category'] === '' ? ' is-active' : '' ?>" href="<?= e(qs(['category' => null, 'page' => null])) ?>">All</a>
      <?php foreach (categories() as $c): ?>
        <a class="chip<?= $f['category'] === $c['slug'] ? ' is-active' : '' ?>" href="<?= e(qs(['category' => $c['slug'], 'page' => null])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="layout-filters">
    <aside>
      <div class="sheet-backdrop" data-filters-close></div>
      <form class="filters-card" method="get" action="<?= e(url('/jobs')) ?>" aria-label="Filters">
        <div class="sheet-grip show-md" aria-hidden="true"></div>
        <div class="filter-top"><strong>Filters</strong><a class="btn-link small" href="<?= e(url('/jobs')) ?>">Clear all</a></div>
        <div class="field">
          <label class="label" for="q">Keyword</label>
          <div class="input-icon"><?= icon('search') ?><input class="input" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="e.g. aircon, wiring"></div>
        </div>
        <div class="field filter-cat">
          <label class="label" for="category">Category</label>
          <select class="select" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (categories() as $c): ?><option value="<?= e($c['slug']) ?>"<?= selected($f['category'], $c['slug']) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="city">City</label>
          <select class="select" id="city" name="city">
            <option value="">Any city</option>
            <?php foreach (cities() as $c): ?><option<?= selected($f['city'], $c) ?>><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field filter-cat">
          <label class="label" for="sort">Sort by</label>
          <select class="select" id="sort" name="sort">
            <option value="newest"<?= selected($f['sort'], 'newest') ?>>Newest first</option>
            <option value="urgent"<?= selected($f['sort'], 'urgent') ?>>Most urgent</option>
            <option value="budget"<?= selected($f['sort'], 'budget') ?>>Highest budget</option>
          </select>
        </div>
        <div class="row mt-6 filter-actions">
          <button class="btn btn-primary grow" type="submit">Apply</button>
          <a class="btn btn-ghost" href="<?= e(url('/jobs')) ?>">Reset</a>
        </div>
      </form>
    </aside>
    <section>
      <div class="results-bar"><div><strong><?= number_format($total) ?></strong> <span class="muted">open job<?= $total === 1 ? '' : 's' ?></span></div></div>
      <?php if (!$jobs): ?>
        <div class="card empty">
          <?= empty_art('briefcase') ?>
          <h3>No open jobs match</h3>
          <p>Try another category or city. New jobs are posted every day.</p>
          <a class="btn" href="<?= e(url('/jobs')) ?>">Clear filters</a>
        </div>
      <?php else: ?>
        <div class="stack" style="--gap:14px">
          <?php foreach ($jobs as $j): ?><?= View::partial('jobs/_card', ['j' => $j]) ?><?php endforeach; ?>
        </div>
        <?= pagination($total, $perPage, $page) ?>
      <?php endif; ?>
    </section>
  </div>
</div>
