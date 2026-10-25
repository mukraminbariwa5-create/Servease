<?php
/** Services | Pros switch shown on both browse pages. Keeps the category and city. */
$keep = array_filter(['category' => $_GET['category'] ?? '', 'city' => $_GET['city'] ?? '', 'q' => $_GET['q'] ?? ''], fn($v) => $v !== '');
?>
<nav class="segmented-nav" aria-label="Browse by">
  <a class="<?= $on === 'services' ? 'is-active' : '' ?>" href="<?= e(url('/services', $keep)) ?>"<?= $on === 'services' ? ' aria-current="page"' : '' ?>><?= icon('grid', 'icon-sm') ?> Services</a>
  <a class="<?= $on === 'pros' ? 'is-active' : '' ?>" href="<?= e(url('/providers', $keep)) ?>"<?= $on === 'pros' ? ' aria-current="page"' : '' ?>><?= icon('users', 'icon-sm') ?> Pros</a>
</nav>
