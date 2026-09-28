<section class="page-header">
  <div class="container row row-between row-wrap" style="align-items:flex-end">
    <div>
      <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>">Home</a><?= icon('chevron-right') ?><span>Job board</span></nav>
      <h1>Job board</h1>
      <p class="lead">Open jobs posted by clients. <?= is_role('provider') ? 'Send a quote to win the work.' : 'Pros send quotes; the client picks the best one.' ?></p>
    </div>
    <?php if (!is_role('provider', 'admin')): ?>
      <a class="btn btn-primary" href="<?= e(url('/jobs/new')) ?>"><?= icon('plus') ?> Post a job</a>
    <?php endif; ?>
  </div>
</section>

<div class="container page">
  <div class="layout-filters">
    <aside>
      <button class="btn btn-block mb-4 filters-toggle" type="button" data-filters-toggle><?= icon('filter') ?> Filters</button>
      <form class="card filters filters-card" method="get" action="<?= e(url('/jobs')) ?>">
        <div class="filter-title"><?= icon('filter') ?> Filters</div>
        <div class="field">
          <label class="label" for="q">Keyword</label>
          <div class="input-icon"><?= icon('search') ?><input class="input" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="e.g. aircon, wiring"></div>
        </div>
        <div class="field">
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
        <div class="field">
          <label class="label" for="sort">Sort by</label>
          <select class="select" id="sort" name="sort">
            <option value="newest"<?= selected($f['sort'], 'newest') ?>>Newest first</option>
            <option value="urgent"<?= selected($f['sort'], 'urgent') ?>>Most urgent</option>
            <option value="budget"<?= selected($f['sort'], 'budget') ?>>Highest budget</option>
          </select>
        </div>
        <div class="row mt-6">
          <button class="btn btn-primary grow" type="submit">Apply</button>
          <a class="btn btn-ghost" href="<?= e(url('/jobs')) ?>">Reset</a>
        </div>
      </form>
    </aside>
    <section>
      <div class="results-bar"><div><strong><?= number_format($total) ?></strong> <span class="muted">open job<?= $total === 1 ? '' : 's' ?></span></div></div>
      <?php if (!$jobs): ?>
        <div class="card empty">
          <div class="empty-icon"><?= icon('briefcase') ?></div>
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
