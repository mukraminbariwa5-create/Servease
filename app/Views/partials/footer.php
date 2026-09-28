<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <?= partial('brand') ?>
        <p class="muted mt-3" style="max-width:320px">The easiest way to find, book and pay trusted local pros — from plumbing to tutoring.</p>
        <div class="row mt-4">
          <button class="btn btn-sm theme-toggle" type="button" data-theme-toggle>
            <?= icon('moon', 'when-light') ?><?= icon('sun', 'when-dark') ?>
            <span class="when-light">Dark mode</span><span class="when-dark">Light mode</span>
          </button>
        </div>
      </div>
      <div>
        <h4>For clients</h4>
        <ul>
          <li><a href="<?= e(url('/providers')) ?>">Find a pro</a></li>
          <li><a href="<?= e(url('/jobs/new')) ?>">Post a job</a></li>
          <li><a href="<?= e(url('/how-it-works')) ?>">How it works</a></li>
        </ul>
      </div>
      <div>
        <h4>For pros</h4>
        <ul>
          <li><a href="<?= e(url('/register', ['role' => 'provider'])) ?>">Become a pro</a></li>
          <li><a href="<?= e(url('/jobs')) ?>">Browse jobs</a></li>
          <li><a href="<?= e(url('/how-it-works')) ?>#pros">Getting verified</a></li>
        </ul>
      </div>
      <div>
        <h4>Popular</h4>
        <ul>
          <?php foreach (array_slice(categories(), 0, 4) as $c): ?>
            <li><a href="<?= e(url('/providers', ['category' => $c['slug']])) ?>"><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Fixoria. A portfolio project.</span>
      <span class="row" style="--gap:6px"><?= icon('shield-check', 'icon-sm') ?> Secure sign-in · Verified pros · Reviews from real bookings</span>
    </div>
  </div>
</footer>
