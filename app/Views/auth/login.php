<div class="auth-head">
  <h1>Welcome back</h1>
  <p class="lead">Sign in to manage your bookings, quotes and messages.</p>
</div>

<form id="login-form" class="mt-8" method="post" action="<?= e(url('/login')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field<?= invalid('email') ?>">
    <label class="label" for="email">Email address</label>
    <div class="input-icon"><?= icon('mail') ?><input class="input" id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="you@example.com" autocomplete="email" required autofocus></div>
    <?= field_error('email') ?>
  </div>
  <div class="field">
    <div class="label-row"><label class="label" for="password">Password</label><a class="label-aside" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a></div>
    <div class="password-wrap input-icon">
      <?= icon('lock') ?>
      <input class="input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
      <button class="password-toggle" type="button" data-pw-toggle aria-label="Show password"><?= icon('eye') ?></button>
    </div>
  </div>
  <button class="btn btn-primary btn-lg btn-block mt-6" type="submit">Sign in <?= icon('arrow-right') ?></button>
</form>

<?php if (config('app.demo')): ?>
  <div class="or">or explore with a demo account</div>
  <div class="demo-list">
    <?php foreach ([
        ['client@fixoria.test', 'Client', 'Maria Santos', 'Book pros, post jobs, pay & review', 'MS', 150],
        ['provider@fixoria.test', 'Pro', 'Juan Dela Cruz', 'Quotes, bookings & earnings', 'JD', 215],
        ['admin@fixoria.test', 'Admin', 'Alex Reyes', 'Verify pros & manage the platform', 'AR', 330],
    ] as [$email, $role, $name, $desc, $ini, $h]): ?>
      <button class="demo-item" type="button" data-demo="<?= e($email) ?>" data-pw="fixoria123">
        <span class="avatar avatar-md" style="--h:<?= $h ?>"><?= $ini ?></span>
        <span class="grow">
          <span class="demo-name"><?= e($name) ?> <span class="demo-role"><?= e($role) ?></span></span>
          <span class="demo-desc"><?= e($desc) ?></span>
        </span>
        <?= icon('arrow-right') ?>
      </button>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<p class="auth-switch">New to Fixoria? <a href="<?= e(url('/register')) ?>">Create a free account</a></p>
