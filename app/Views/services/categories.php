<?php /** Category tab: pick a trade, then compare its services. */ ?>
<?php
$totalServices = array_sum(array_map(fn($r) => (int) $r['n'], $stats));
$totalPros = array_sum(array_map(fn($r) => (int) $r['pros'], $stats));
$heroStats = [['grid', '<strong>' . count(categories()) . '</strong> trades'], ['tag', '<strong>' . $totalServices . '</strong> services'], ['users', '<strong>' . $totalPros . '</strong> pros']];
$search = '<form class="hero-find" action="' . e(url('/services')) . '" method="get" role="search">'
    . '<label class="search-field">' . icon('search') . '<span class="sr-only">Search all services</span><input name="q" type="search" placeholder="Search all services" autocomplete="off"></label>'
    . '<button class="btn btn-primary" type="submit">Search</button></form>';
?>
<?= partial('listing-hero', [
    'crumbs'  => [['Home', '/'], ['Categories', null]],
    'title'   => 'Browse by category',
    'lead'    => 'Pick a trade to compare services, prices and pros in ' . current_city() . '.',
    'stats'   => $heroStats,
    'actions' => $search,
]) ?>
<div class="container page cats-page">
  <div class="show-md">
  <div class="page-bar cats-bar">
    <div class="grow">
      <h1>Categories</h1>
      <p class="muted hide-md">Pick a trade to compare services, prices and pros in <?= e(current_city()) ?>.</p>
    </div>
  </div>

  <form class="home-search cats-search" action="<?= e(url('/services')) ?>" method="get" role="search">
    <label class="search-field">
      <?= icon('search') ?><span class="sr-only">Search services</span>
      <input name="q" type="search" placeholder="Search all services" autocomplete="off">
    </label>
  </form>

  </div>
  <div class="cats-grid">
    <?php foreach (categories() as $i => $c): $st = $stats[$c['slug']] ?? null; ?>
      <a class="cat-card" href="<?= e(url('/services', ['category' => $c['slug']])) ?>" style="--i:<?= $i ?>">
        <span class="cat-card-art" style="--tint:<?= category_tint($c['slug']) ?>"><?= category_art($c['slug'], $c['icon'], 'cat-art', 56) ?></span>
        <span class="cat-card-body">
          <strong><?= e($c['name']) ?></strong>
          <?php if (!empty($c['description'])): ?><small class="cat-card-desc hide-sm"><?= e($c['description']) ?></small><?php endif; ?>
          <span class="cat-card-meta">
            <?php if ($st): ?>
              <span><?= (int) $st['n'] ?> service<?= (int) $st['n'] === 1 ? '' : 's' ?></span>
              <span class="cat-card-from">From <?= money($st['from_price']) ?></span>
            <?php else: ?><span>Coming soon</span><?php endif; ?>
          </span>
        </span>
        <span class="cat-card-go hide-sm" aria-hidden="true"><?= icon('arrow-up-right') ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (is_role('client')): ?>
    <a class="cats-cta" href="<?= e(url('/jobs/new')) ?>">
      <span class="reminder-ic"><?= icon('file-text') ?></span>
      <span class="grow"><strong>Can't find what you need?</strong><small>Post a job and pros will send you free quotes.</small></span>
      <?= icon('chevron-right', 'reminder-go') ?>
    </a>
  <?php endif; ?>
</div>
