<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-about">
        <?= partial('brand') ?>
        <p>Book trusted pros for every job at home, from aircon cleaning to plumbing. Verified IDs, real reviews and clear prices.</p>
        <button class="btn btn-sm theme-toggle" type="button" data-theme-toggle>
          <?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?>
          <span class="when-light">Dark mode</span><span class="when-dark">Light mode</span>
        </button>
      </div>
      <?php $fu = user(); $frole = $fu['role'] ?? 'client'; ?>
      <?php if ($frole === 'provider'): ?>
      <div>
        <h4>Your business</h4>
        <ul>
          <li><a href="<?= e(url('/jobs')) ?>">Find work</a></li>
          <li><a href="<?= e(url('/my/quotes')) ?>">My quotes</a></li>
          <li><a href="<?= e(url('/provider/profile')) ?>">Edit pro profile</a></li>
        </ul>
      </div>
      <?php elseif ($frole === 'admin'): ?>
      <div>
        <h4>Admin</h4>
        <ul>
          <li><a href="<?= e(url('/admin')) ?>">Admin panel</a></li>
          <li><a href="<?= e(url('/admin/verifications')) ?>">Verifications</a></li>
          <li><a href="<?= e(url('/admin/issues')) ?>">Reported problems</a></li>
        </ul>
      </div>
      <?php else: ?>
      <div>
        <h4>For customers</h4>
        <ul>
          <li><a href="<?= e(url('/services')) ?>">Browse services</a></li>
          <li><a href="<?= e(url('/jobs/new')) ?>">Post a job</a></li>
          <li><a href="<?= e(url('/saved')) ?>">Saved pros</a></li>
        </ul>
      </div>
      <?php endif; ?>
      <div>
        <h4>Help</h4>
        <ul>
          <li><a href="<?= e(url('/how-it-works')) ?>">How it works</a></li>
          <li><a href="<?= e(url('/how-it-works')) ?>#pros">Getting verified</a></li>
          <li><a href="<?= e(url('/settings')) ?>">Account settings</a></li>
        </ul>
      </div>
      <div>
        <h4>Popular</h4>
        <ul>
          <?php foreach (array_slice(categories(), 0, 4) as $c): ?>
            <li><a href="<?= e(url('/services', ['category' => $c['slug']])) ?>"><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Servease. A portfolio project by Mukramin Bariwa Ansao.</span>
      <span class="row" style="--gap:6px"><?= icon('shield-check', 'icon-sm') ?> Every pro is ID-checked. Reviews come only from finished bookings.</span>
    </div>
  </div>
</footer>
