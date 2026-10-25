<?php
/**
 * Top area of a card. Shows the pro's own latest work photo when there is one;
 * otherwise a clean tinted panel with the trade icon ($mode = 'trade') or the pro's
 * face or initials ($mode = 'person'). No stock pictures stand in for a real pro.
 * @var array $row  needs cover, category_slug, category_icon (+ id/first_name/last_name/avatar for 'person')
 */
$mode = $mode ?? 'trade';
$slug = $row['category_slug'] ?? null;
?>
<?php if (!empty($row['cover'])): ?>
  <img class="media-photo" src="<?= e(upload_url($row['cover'])) ?>" alt="<?= e($alt ?? '') ?>" loading="lazy" decoding="async">
<?php elseif ($mode === 'person'): ?>
  <span class="media-panel" style="--tint:<?= category_tint($slug) ?>">
    <?= avatar($row, 'xl', 'media-avatar') ?>
    <span class="media-trade"><?= category_art($slug, $row['category_icon'] ?? 'wrench', 'cat-art', 40) ?></span>
  </span>
<?php else: ?>
  <span class="media-panel" style="--tint:<?= category_tint($slug) ?>">
    <span class="media-tile"><?= category_art($slug, $row['category_icon'] ?? 'wrench', 'cat-art', 64) ?></span>
  </span>
<?php endif; ?>
