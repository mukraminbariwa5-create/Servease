<div class="container container-sm" style="padding:64px 20px">
  <div class="text-center mb-6"><?= partial('brand') ?></div>
  <div class="card">
    <div class="card-body">
      <h1 style="font-size:1.6rem">Set up Servease</h1>
      <p class="muted mt-2">This creates the <code><?= e(config('db.name')) ?></code> database and its tables on <code><?= e(config('db.host')) ?></code>.</p>

      <?php if ($dbError): ?>
        <div class="alert alert-error mt-6"><?= icon('alert') ?><div>
          <strong>Can't connect to MySQL.</strong> Start MySQL in the XAMPP Control Panel, then check the database settings in <code>config/config.php</code>.
          <div class="tiny mt-2" style="opacity:.8"><?= e($dbError) ?></div>
        </div></div>
      <?php elseif ($installed): ?>
        <div class="alert alert-warning mt-6"><?= icon('alert') ?><div><strong>Servease is already installed.</strong> Running the installer again will <strong>erase all data</strong> and start fresh.</div></div>
      <?php endif; ?>

      <form class="mt-6" method="post" action="<?= e(url('/install')) ?>" <?= $installed ? 'data-confirm="This deletes ALL existing Servease data. Continue?"' : '' ?>>
        <?= csrf_field() ?>
        <label class="check"><input type="checkbox" name="seed" value="1" checked> Load demo data (pros, jobs, bookings, reviews and messages)</label>
        <p class="help mt-2">Recommended for trying things out and for portfolio demos. All demo accounts use the password <code>servease123</code>.</p>
        <button class="btn btn-primary btn-lg btn-block mt-6" type="submit" <?= $dbError ? 'disabled' : '' ?>><?= icon('zap') ?> <?= $installed ? 'Reinstall Servease' : 'Install Servease' ?></button>
      </form>
    </div>
    <div class="card-footer small muted">After installing, set <code>'env' => 'production'</code> in <code>config/config.php</code> before putting the site online. That turns this page off.</div>
  </div>
</div>
<?= partial('confirm') ?>
