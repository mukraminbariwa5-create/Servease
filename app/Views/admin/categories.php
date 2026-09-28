<?php $err = errors(); $e = $edit ?? null; ?>
<div class="page-title"><div><h1>Categories</h1><p class="muted">The services clients can browse and post jobs in.</p></div></div>
<div class="grid split" style="--cols:minmax(0,1.6fr) minmax(0,1fr);gap:20px;align-items:start">
  <div class="card table-wrap">
    <table class="table">
      <thead><tr><th>Category</th><th>Pros</th><th>Jobs</th><th>Order</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $c): ?>
          <tr style="<?= $c['is_active'] ? '' : 'opacity:.55' ?>">
            <td><div class="cell-user"><span class="cat-icon" style="width:34px;height:34px"><?= icon($c['icon']) ?></span><div><div class="semibold"><?= e($c['name']) ?></div><div class="tiny subtle">/<?= e($c['slug']) ?></div></div></div></td>
            <td><?= (int) $c['providers'] ?></td><td><?= (int) $c['jobs'] ?></td><td><?= (int) $c['sort_order'] ?></td>
            <td><?= $c['is_active'] ? badge('active') : '<span class="badge">Hidden</span>' ?></td>
            <td class="text-right nowrap">
              <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/categories', ['edit' => $c['id']])) ?>">Edit</a>
              <form method="post" action="<?= e(url('/admin/categories/' . $c['id'] . '/toggle')) ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-sm" type="submit"><?= $c['is_active'] ? 'Hide' : 'Show' ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <form class="card card-body sticky" method="post" action="<?= e(url('/admin/categories')) ?>">
    <?= csrf_field() ?>
    <h3 class="mb-4"><?= $e ? 'Edit “' . e($e['name']) . '”' : 'Add a category' ?></h3>
    <?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
    <div class="field <?= isset($err['name']) ? 'is-invalid' : '' ?>"><label class="label" for="c-name">Name</label><input class="input" id="c-name" name="name" required maxlength="80" value="<?= e(old('name', $e['name'] ?? '')) ?>"><?php if (isset($err['name'])): ?><span class="field-error"><?= e($err['name']) ?></span><?php endif; ?></div>
    <div class="field">
      <span class="label">Icon</span>
      <div class="grid" style="grid-template-columns:repeat(6,1fr);--gap:6px">
        <?php $cur = old('icon', $e['icon'] ?? 'wrench'); foreach (AdminController::ICONS as $ic): ?>
          <label class="choice choice-plain" title="<?= e($ic) ?>"><input type="radio" name="icon" value="<?= e($ic) ?>" <?= $cur === $ic ? 'checked' : '' ?>><span class="choice-body"><?= icon($ic) ?></span></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field"><label class="label" for="c-desc">Description <span class="opt">(optional)</span></label><input class="input" id="c-desc" name="description" maxlength="255" value="<?= e(old('description', $e['description'] ?? '')) ?>"></div>
    <div class="field"><label class="label" for="c-order">Sort order</label><input class="input" id="c-order" name="sort_order" type="number" min="0" max="999" value="<?= e(old('sort_order', $e['sort_order'] ?? 50)) ?>"></div>
    <div class="row mt-6"><button class="btn btn-primary" type="submit"><?= $e ? 'Save changes' : 'Add category' ?></button><?php if ($e): ?><a class="btn btn-ghost" href="<?= e(url('/admin/categories')) ?>">Cancel</a><?php endif; ?></div>
  </form>
</div>
