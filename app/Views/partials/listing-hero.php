<?php
/**
 * Laptop header band. Browse pages (services, pros, job board) pass an eyebrow and `art` for the
 * mockup look: eyebrow, big title, a line of text, the mascot picture on the right, and a row of
 * category pills underneath. Account pages pass stats and actions instead. Phones use their own header.
 *
 * @var array       $crumbs  [[label, href|null], ...]  (shown only when there is no eyebrow)
 * @var string|null $eyebrow small blue label above the title
 * @var string      $title
 * @var string      $lead    one line under the title
 * @var bool        $art     show the mascot picture on the right
 * @var array       $stats   [[icon, html], ...]
 * @var string|null $switch  'services' | 'pros' | null — the Services/Pros switch (account pages don't use it)
 * @var array|null  $sort    ['action' => url, 'filters' => [...], 'options' => [key => label], 'current' => key]
 * @var string      $actions extra buttons (HTML) on the right
 * @var array|null  $chips   ['base' => '/services', 'current' => slug, 'keep' => [query to keep]]
 * @var bool        $narrow  line up with a reading-width page (880px) instead of the full container
 */
$stats = $stats ?? [];
$actions = $actions ?? '';
$crumbs = $crumbs ?? [];
?>
<section class="list-hero hide-md<?= !empty($narrow) ? ' is-narrow' : '' ?><?= !empty($art) ? ' has-art' : '' ?>">
  <?php if (!empty($art)):
      // A category page shows that trade's photo; the full list shows a strip of trades at work
      $artPhoto = is_string($art) && is_file(ROOT . '/assets/img/photos/' . $art . '.jpg') ? $art : null; ?>
    <?php if ($artPhoto): ?>
      <img class="list-hero-art is-stock" src="<?= e(stock_photo($artPhoto)) ?>" alt="" width="760" height="262" decoding="async">
    <?php else: ?>
      <div class="list-hero-mosaic" aria-hidden="true">
        <?php foreach (['plumbing', 'cleaning', 'electrical'] as $ph): ?><img class="is-stock" src="<?= e(stock_photo($ph)) ?>" alt="" decoding="async"><?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <div class="container">
    <?php if (empty($eyebrow) && $crumbs): ?>
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($crumbs as $i => [$label, $href]): ?>
          <?php if ($i): ?><?= icon('chevron-right') ?><?php endif; ?>
          <?php if ($href): ?><a href="<?= e(url($href)) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <div class="list-hero-row">
      <div class="list-hero-copy">
        <?php if (!empty($eyebrow)): ?><span class="list-hero-eyebrow"><?= e($eyebrow) ?></span><?php endif; ?>
        <h1><?= e($title) ?></h1>
        <?php if (!empty($lead)): ?><p class="list-hero-lead"><?= e($lead) ?></p><?php endif; ?>
        <?php if ($stats): ?>
          <ul class="list-hero-stats">
            <?php foreach ($stats as [$ic, $text]): ?><li><?= icon($ic) ?><span><?= $text ?></span></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <?php if (!empty($switch) || !empty($sort) || $actions): ?>
      <div class="list-hero-controls">
        <?php if (!empty($switch)): ?><?= partial('browse-switch', ['on' => $switch]) ?><?php endif; ?>
        <?php if (!empty($sort)): ?>
          <form class="sort-pill" method="get" action="<?= e($sort['action']) ?>">
            <?php foreach ($sort['filters'] as $k => $v): if ($k === 'sort' || $v === '' || $v === null) continue; ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <label for="sort-desk"><?= icon('sliders', 'icon-sm') ?> Sort</label>
            <select id="sort-desk" name="sort" data-autosubmit>
              <?php foreach ($sort['options'] as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($sort['current'], $k) ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-sm">Go</button></noscript>
          </form>
        <?php endif; ?>
        <?= $actions ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($chips)): $keep = array_filter($chips['keep'] ?? [], fn($v) => $v !== '' && $v !== null); ?>
      <div class="cat-rail" data-rail>
        <button class="rail-nav rail-prev" type="button" data-rail-nav="-1" aria-label="Scroll categories left" hidden><?= icon('chevron-left') ?></button>
        <div class="cat-rail-track" data-rail-track>
          <a class="rail-chip<?= $chips['current'] === '' ? ' is-active' : '' ?>" href="<?= e(url($chips['base'], $keep)) ?>"<?= $chips['current'] === '' ? ' aria-current="page"' : '' ?>>
            <span class="rail-ic"><?= icon('grid') ?></span>All
          </a>
          <?php foreach (categories() as $c): $on = $chips['current'] === $c['slug']; ?>
            <a class="rail-chip<?= $on ? ' is-active' : '' ?>" href="<?= e(url($chips['base'], ['category' => $c['slug']] + $keep)) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
              <span class="rail-ic"><?= category_art($c['slug'], $c['icon'], 'cat-art', 22) ?></span><?= e(preg_replace('/ &.*$/', '', $c['name'])) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <button class="rail-nav rail-next" type="button" data-rail-nav="1" aria-label="Scroll categories right"><?= icon('chevron-right') ?></button>
      </div>
    <?php endif; ?>
  </div>
</section>
