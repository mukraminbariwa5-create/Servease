<?php
/**
 * "Sort by [Recommended]" control that keeps the other filters.
 * @var string $action  form url
 * @var array  $filters current filters
 * @var array  $options key => label
 * @var string $id      unique id for the select
 */
?>
<form class="sort-select" method="get" action="<?= e($action) ?>">
  <?php foreach ($filters as $k => $v): if ($k === 'sort' || $v === '' || $v === null) continue; ?>
    <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
  <?php endforeach; ?>
  <label for="<?= e($id) ?>">Sort by</label>
  <select class="select select-sm" id="<?= e($id) ?>" name="sort" data-autosubmit>
    <?php foreach ($options as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($filters['sort'] ?? '', $k) ?>><?= e($label) ?></option><?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-sm">Go</button></noscript>
</form>
